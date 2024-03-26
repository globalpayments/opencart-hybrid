<?php

use GlobalPayments\PaymentGatewayProvider\PaymentMethods\Apm\Paypal;
use GlobalPayments\Api\Entities\Enums\BankPaymentType;
use GlobalPayments\Api\Entities\Enums\BankPaymentStatus;
use GlobalPayments\Api\Entities\Enums\PaymentMethodType;
use GlobalPayments\Api\Entities\Enums\TransactionStatus;
use GlobalPayments\Api\Entities\Reporting\TransactionSummary;
use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;
use Psr\Log\LogLevel;


class ControllerExtensionPaymentGlobalPaymentsPaypal extends Controller {
	private $order;

	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setPaymentMethod(Paypal::PAYMENT_METHOD_ID);
	}

	public function index() {
		$this->load->language('extension/payment/globalpayments_paypal');

		$this->setOrder();

		$data['action'] = $this->url->link('extension/payment/globalpayments_paypal/confirm', '', true);
		$data['paymentMethod'] = $this->globalpayments->paymentMethod;
		if ($this->customer->isLogged()) {
			$data['customer_is_logged'] = true;
		} else {
			$data['customer_is_logged'] = false;
		}

		$data['globalpayments_order'] = json_encode($this->order);

		return $this->load->view('extension/payment/globalpayments_paypal', $data);
	}

	public function confirm() {
		$this->load->language('extension/payment/globalpayments_ucp');

		try {
			$this->setOrder();

			$requestData                    = new RequestData();
			$requestData->order             = $this->order;
			$requestData->gatewayId         = $this->globalpayments->gateway->gatewayId;
			$requestData->dynamicDescriptor = $this->config->get('payment_globalpayments_ucp_txn_descriptor');
			$requestData->requestType       = AbstractGateway::getRequestType($this->globalpayments->paymentMethod->paymentAction);
			$requestData->meta              = (object) [
				'callbackUrls' => $this->globalpayments->paymentMethod->getCallbackUrls(),
				'paymentAction' => $this->globalpayments->paymentMethod->paymentAction,
				'provider' => $this->globalpayments->paymentMethod->provider,
			];

			$gatewayResponse = $this->globalpayments->gateway->processInitiatePaymentAPM($requestData);

			//Create order in Pending status before the redirect to the 3rd party payment screen
			$this->load->model('extension/payment/globalpayments_ucp');
			$this->model_extension_payment_globalpayments_ucp->addTransaction(
				$this->session->data['order_id'],
				$this->globalpayments->paymentMethod->paymentMethodId,
				AbstractGateway::INITIATE,
				$gatewayResponse->balanceAmount,
				$this->order->currency,
				$gatewayResponse
			);
			$this->load->model('checkout/order');
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);

			$redirectUrl = $gatewayResponse->alternativePaymentResponse->redirectUrl;
			AbstractRequest::sendJsonResponse([$redirectUrl]);
		} catch (\Exception $e) {
			$message = sprintf('[%1$s] %2$s - %3$s', LogLevel::ERROR, Utils::mapResponseCodeToFriendlyMessage(), $e->getMessage());
			$this->log->write($message);
			AbstractRequest::sendJsonResponse(['error' => true, 'message' => 'default_error'], 500);
		}
	}

	public function paypalReturn() {
		$this->globalpayments->paymentMethod->paypalReturn();
	}

	public function processPaypalReturn() {

		try {
			$this->globalpayments->paymentMethod->validateRequest($this->request);

			$requestData = new RequestData();
			$requestData->transactionId = $this->request->get['id'];
			$gatewayResponse = $this->globalpayments->gateway->getTransactionDetails($requestData);
			$orderId = $gatewayResponse->orderId ?? 0;
			switch($gatewayResponse->transactionStatus) {
				case TransactionStatus::INITIATED:
				case TransactionStatus::PREAUTHORIZED:
				case TransactionStatus::CAPTURED:
					$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
					break;

				case TransactionStatus::PENDING:
					$transaction = Transaction::fromId($this->request->get['id'], $orderId, PaymentMethodType::APM);
					$transaction->alternativePaymentResponse = $gatewayResponse->alternativePaymentResponse;
					$transaction->confirm()->execute();

					$paymentAction = $this->globalpayments->paymentMethod->paymentAction;

					$this->load->model('extension/payment/globalpayments_ucp');
					$this->model_extension_payment_globalpayments_ucp->addTransaction(
						$orderId,
						$this->globalpayments->paymentMethod->paymentMethodId,
						$paymentAction === AbstractGateway::CHARGE ? AbstractGateway::CAPTURE : AbstractGateway::AUTHORIZE,
						$gatewayResponse->amount,
						$gatewayResponse->currency,
						$gatewayResponse
					);
					$this->addOrderHistory($orderId, 2, $gatewayResponse);

					$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
					break;
				case TransactionStatus::DECLINED:
				case 'FAILED':
					$this->load->model('extension/payment/globalpayments_ucp');
					$this->model_extension_payment_globalpayments_ucp->addTransaction(
						$this->session->data['order_id'],
						$this->globalpayments->paymentMethod->paymentMethodId,
						AbstractGateway::CANCEL,
						$gatewayResponse->amount,
						$gatewayResponse->currency,
						$gatewayResponse
					);

					$this->load->model('checkout/order');
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 7);

					$errorMessage = Utils::mapResponseCodeToFriendlyMessage('FAILED');
					$message = sprintf('[%1$s] %2$s', LogLevel::ERROR, $errorMessage);
					$this->log->write($message);
					$this->response->redirect($this->url->link( 'checkout/checkout', '', true));
					break;
				default:
					throw new \Exception(
						'Order ID: ' . $gatewayResponse->orderId . '. Unexpected transaction status on returnUrl: ' . $gatewayResponse->transactionStatus
					);
			}
		} catch (\Exception $e) {
			$message = sprintf('[%1$s] Error completing order return. %2$s', LogLevel::ERROR, $e->getMessage());
			$this->log->write($message);
			$this->load->language('extension/payment/globalpayments_ucp');
			$customerMessage = $this->language->get('text_order_notification_return');
			$this->session->data['error'] = $customerMessage;
			$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
		}
	}

	public function paypalCancel() {
		$this->globalpayments->paymentMethod->paypalCancel();
	}

	public function processPaypalCancel() {
		try {
			$this->globalpayments->paymentMethod->validateRequest($this->request);
			$requestData = new RequestData();
			$requestData->transactionId = $this->request->get['id'];
			$gatewayResponse = $this->globalpayments->gateway->getTransactionDetails($requestData);
			$this->load->model('extension/payment/globalpayments_ucp');
			$this->model_extension_payment_globalpayments_ucp->addTransaction(
				$gatewayResponse->orderId,
				$this->globalpayments->paymentMethod->paymentMethodId,
				AbstractGateway::CANCEL,
				$gatewayResponse->amount,
				$gatewayResponse->currency,
				$gatewayResponse
			);
			$this->addOrderHistory($gatewayResponse->orderId, 7, $gatewayResponse);

			$this->response->redirect($this->url->link( 'checkout/checkout', '', true));
		} catch (\Exception $e) {
			$message = sprintf('[%1$s] Error completing order cancel. %2$s', LogLevel::ERROR, $e->getMessage());
			$this->log->write($message);
		}

		$this->response->redirect($this->url->link( 'checkout/checkout', '', true));
	}

	private function setOrder() {
		if (empty($this->session->data['order_id'])) {
			throw new \Exception($this->language->get('error_order_processing'));
		}

		$this->load->model( 'checkout/order' );
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order = new OrderData();
		$order->amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$order->currency = $order_info['currency_code'];
		$order->orderReference = $this->session->data['order_id'];
		$order->storeUrl = $order_info['store_url'];
		$order->customerEmail = $order_info['email'];
		$order->paymentCountry = $order_info['payment_iso_code_2'];
		$order->cart = $this->cart->getProducts();
		$order->textProductInitiateOrder = $this->language->get('text_product_initiate_order');

		$order->customer = array(
			'id' => $order_info['customer_id'],
			'email' => $order_info['email'],
			'telephone' => $order_info['telephone'],
			'firstname' => $order_info['firstname'],
			'lastname' => $order_info['lastname'],
		);

		$order->billingAddress = array(
			'streetAddress1' => $order_info['payment_address_1'],
			'streetAddress2' => $order_info['payment_address_2'],
			'city'           => $order_info['payment_city'],
			'state'          => $order_info['payment_zone_code'],
			'postalCode'     => $order_info['payment_postcode'],
			'country'        => $order_info['payment_iso_code_2'],
		);

		$order->shippingAddress = array(
			'streetAddress1' => $order_info['shipping_address_1'],
			'streetAddress2' => $order_info['shipping_address_2'],
			'city'           => $order_info['shipping_city'],
			'state'          => $order_info['shipping_zone_code'],
			'postalCode'     => $order_info['shipping_postcode'],
			'country'        => $order_info['shipping_iso_code_2'],
		);

		$order->addressMatchIndicator = $order->billingAddress == $order->shippingAddress;
		$this->order = $order;
	}

	private function addOrderHistory($orderId, $status, $response) {
		$this->load->language('extension/payment/globalpayments_ucp');
		$responseCode = BankPaymentStatus::UNKNOWN;
		if ($response instanceof TransactionSummary && $response->gatewayResponseMessage == 'REQUEST_SUCCESS') {
			$responseCode = BankPaymentStatus::SUCCESS;
		}

		$this->load->model('checkout/order');
		$comment = [
			$this->language->get('text_comment_txn_id') . ' ' . $response->transactionId,
			$this->language->get('text_comment_response_code') . ' ' . $responseCode,
			$this->language->get('text_comment_response_status') . ' ' . $response->transactionStatus,
			$this->language->get('text_comment_amount') . ' ' . $response->amount,
			$this->language->get('text_comment_currency') . ' ' . $response->currency,
		];
		$comment = implode('<br/>', $comment);
		$this->model_checkout_order->addOrderHistory($orderId, $status, $comment);
	}
}
