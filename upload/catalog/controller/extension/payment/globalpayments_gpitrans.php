<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class ControllerExtensionPaymentGlobalPaymentsUcp extends Controller {
	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(\GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId::TRANSACTION_API);
	}

	public function index() {
		$this->load->language('extension/payment/globalpayments_ucp');

		$this->setOrder();
		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['action'] = $this->url->link('extension/payment/globalpayments_ucp/confirm', '', true);

		$data['gateway'] = $this->globalpayments->gateway;

		$data['payment_tab_option'] = 'new';
		if ($this->customer->isLogged()) {
			$data['customer_is_logged'] = true;
			$this->load->model('extension/payment/globalpayments_gpitrans');
			$data['stored_payment_methods'] = $this->model_extension_payment_globalpayments_gpitrans->getCards(
				$this->customer->getId(),
				$this->globalpayments->gateway->gatewayId
			);
			if (!empty($data['stored_payment_methods']) && in_array(1, array_column($data['stored_payment_methods'], 'is_default'))) {
				$data['payment_tab_option'] = 'saved';
			}
		} else {
			$data['customer_is_logged']     = false;
			$data['stored_payment_methods'] = null;
		}

		$data['environment_indicator']                             = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields']                             = $this->globalpayments->gateway->getCreditCardFormatFields();
		$data['globalpayments_secure_payment_fields_params']       = $this->globalpayments->gateway->securePaymentFieldsParams();
		$data['globalpayments_secure_payment_threedsecure_params'] = $this->globalpayments->gateway->securePaymentFieldsThreeDSecureParams($this->order);

		return $this->load->view('extension/payment/globalpayments_gpitrans', $data);
	}

	public function threeDSecureCheckEnrollment() {
		$postRequestData = AbstractRequest::getPostRequestData();

		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);

		if ( ! empty($postRequestData->paymentTokenId) && 'new' !== $postRequestData->paymentTokenId) {
			$this->load->model('extension/payment/globalpayments_ucp');
			$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
		}

		$this->globalpayments->gateway->threeDSecureCheckEnrollment($requestData);
	}

	public function confirm() {
		$this->load->language( 'extension/payment/globalpayments_gpitrans' );
		try {
			$this->setOrder();
			if (empty($this->request->post[$this->globalpayments->gateway->gatewayId])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}

			$postRequestData                   = (object)$this->request->post[$this->globalpayments->gateway->gatewayId];
			$requestData                       = new RequestData();
			$requestData                       = RequestData::setDataObject($requestData, $postRequestData);
			$requestData->paymentTokenResponse = ! empty($postRequestData->paymentTokenResponse) ? htmlspecialchars_decode($postRequestData->paymentTokenResponse) : null;
			$requestData->dynamicDescriptor    = $this->config->get('payment_globalpayments_gpitrans_txn_descriptor');
			$requestData->order                = $this->order;
			$requestData->meta                 = (object) [
				'shared_text' => $this->load->language('extension/payment/globalpayments_shared_text'),
			];

			if (isset($postRequestData->paymentType)
			    && 'saved' === $postRequestData->paymentType
			    && isset($postRequestData->paymentTokenId)
			    && 'new' !== $postRequestData->paymentTokenId) {
				$this->load->model('extension/payment/globalpayments_gpitrans');
				$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
			}
			$requestData->requestType = AbstractGateway::getRequestType($this->globalpayments->gateway->paymentAction);

			$gatewayResponse = $this->globalpayments->gateway->processPayment($requestData);

			$this->load->model('checkout/order');
			$comment = [
				$this->language->get('text_comment_txn_id') . ' ' . $gatewayResponse->transactionReference->transactionId,
				$this->language->get('text_comment_response_code') . ' ' . $gatewayResponse->responseCode,
				$this->language->get('text_comment_response_status') . ' ' . $gatewayResponse->responseMessage,
				$this->language->get('text_comment_amount') . ' ' . $this->order->amount,
				$this->language->get('text_comment_currency') . ' ' . $this->order->currency,
				$this->language->get('text_comment_pmt_method') . ' ' . $gatewayResponse->cardType . ' ' . $gatewayResponse->cardLast4,
			];
			$comment = implode('<br/>', $comment);
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 2, $comment);

			$this->load->model('extension/payment/globalpayments_gpitrans');
			$this->model_extension_payment_globalpayments_gpitrans->addTransaction(
				$this->order->orderReference,
				$this->globalpayments->gateway->gatewayId,
				$this->globalpayments->gateway->paymentAction,
				$this->order->amount,
				$this->order->currency,
				$gatewayResponse
			);

			//succesfull response, store payment method
			if (isset($postRequestData->paymentType) && 'new' === $postRequestData->paymentType && $requestData->saveCard) {
				$payment_token = json_decode($requestData->paymentTokenResponse);
				$this->model_extension_payment_globalpayments_gpitrans->addCard(
					$this->globalpayments->gateway->gatewayId,
					$this->customer->getId(),
					$gatewayResponse->token,
					strtoupper($payment_token->details->cardType),
					$payment_token->details->cardLast4,
					$payment_token->details->expiryYear,
					$payment_token->details->expiryMonth
				);
			}

			$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link( 'checkout/checkout', '', true));
		}
	}

	private function setOrder() {
		if (empty($this->session->data['order_id'])) {
			throw new \Exception($this->language->get('error_order_processing'));
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order                 = new OrderData();
		$order->amount         = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$order->currency       = $order_info['currency_code'];
		$order->orderReference = $this->session->data['order_id'];

		$order->customerEmail = $order_info['email'];

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
}
