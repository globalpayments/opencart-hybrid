<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;

class ControllerExtensionPaymentGlobalPaymentsTxnApi extends Controller
{
	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(GatewayId::TRANSACTION_API);
	}

	public function index()
	{
		$this->load->language('extension/payment/globalpayments_txnapi');

		$this->setOrder();
		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['action'] = $this->url->link('extension/payment/globalpayments_txnapi/confirm', '', true);

		$data['gateway'] = $this->globalpayments->gateway;

		$data['payment_tab_option'] = 'new';
		if ($this->customer->isLogged()) {
			$data['customer_is_logged'] = true;
			$this->load->model('extension/payment/globalpayments_txnapi');
			$data['stored_payment_methods'] = $this->model_extension_payment_globalpayments_txnapi->getCards(
				$this->customer->getId(),
				$this->globalpayments->gateway->gatewayId
			);
			if (!empty($data['stored_payment_methods'])
				&& in_array(1, array_column($data['stored_payment_methods'], 'is_default'))) {
				$data['payment_tab_option'] = 'saved';
			}
		} else {
			$data['customer_is_logged'] = false;
			$data['stored_payment_methods'] = null;
		}

		$data['environment_indicator'] = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields'] = $this->globalpayments->gateway->getCreditCardFormatFields();
		$data['globalpayments_secure_payment_fields_params'] = $this->globalpayments->gateway->securePaymentFieldsParams();

		$data['globalpayments_pay_form'] = $this->load->view('extension/payment/globalpayments_pay_form', $data);
		return $this->load->view('extension/payment/globalpayments_txnapi', $data);
	}

	public function confirm()
	{
		$this->load->language('extension/payment/globalpayments_txnapi');
		try {
			$this->setOrder();
			if (empty($this->request->post[$this->globalpayments->gateway->gatewayId])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}

			$this->load->model('extension/payment/globalpayments_txnapi');
			$postRequestData = (object)$this->request->post[$this->globalpayments->gateway->gatewayId];

			$requestData = $this->buildRequestData($postRequestData);
			$gatewayResponse = $this->globalpayments->gateway->processPayment($requestData);
			$this->addToOrderHistory($gatewayResponse);
			$this->storeTransaction($gatewayResponse);
			$this->storePaymentCard($postRequestData, $requestData, $gatewayResponse);
			$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}
	}

	private function setOrder()
	{
		if (empty($this->session->data['order_id'])) {
			throw new \Exception($this->language->get('error_order_processing'));
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order = new OrderData();
		$order->amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$order->currency = $order_info['currency_code'];
		$order->orderReference = $this->session->data['order_id'];

		$order->customerEmail = $order_info['email'];

		$order->billingAddress = array(
			'streetAddress1' => $order_info['payment_address_1'],
			'streetAddress2' => $order_info['payment_address_2'],
			'city' => $order_info['payment_city'],
			'state' => $order_info['payment_zone_code'],
			'postalCode' => $order_info['payment_postcode'],
			'country' => $order_info['payment_iso_code_2'],
		);

		$order->shippingAddress = array(
			'streetAddress1' => $order_info['shipping_address_1'],
			'streetAddress2' => $order_info['shipping_address_2'],
			'city' => $order_info['shipping_city'],
			'state' => $order_info['shipping_zone_code'],
			'postalCode' => $order_info['shipping_postcode'],
			'country' => $order_info['shipping_iso_code_2'],
		);

		$order->addressMatchIndicator = $order->billingAddress == $order->shippingAddress;

		$this->order = $order;
	}

	/**
	 * @param $postRequestData
	 * @return mixed
	 */
	private function buildRequestData($postRequestData)
	{
		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);
		$requestData->paymentTokenResponse = !empty($postRequestData->paymentTokenResponse) ? htmlspecialchars_decode($postRequestData->paymentTokenResponse) : null;
		$requestData->order = $this->order;
		if (isset($postRequestData->paymentType)
			&& 'saved' === $postRequestData->paymentType
			&& isset($postRequestData->paymentTokenId)
			&& 'new' !== $postRequestData->paymentTokenId) {
			$requestData->paymentToken = $this->model_extension_payment_globalpayments_txnapi->getCard($postRequestData->paymentTokenId);
		}
		return $requestData;
	}

	/**
	 * @param $gatewayResponse
	 * @return void
	 */
	private function addToOrderHistory($gatewayResponse): void
	{
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
	}

	/**
	 * @param $gatewayResponse
	 * @return void
	 */
	private function storeTransaction($gatewayResponse): void
	{
		$this->model_extension_payment_globalpayments_txnapi->addTransaction(
			$this->order->orderReference,
			$this->globalpayments->gateway->gatewayId,
			$this->globalpayments->gateway->paymentAction,
			$this->order->amount,
			$this->order->currency,
			$gatewayResponse
		);
	}

	/**
	 * @param $postRequestData
	 * @param $requestData
	 * @param $gatewayResponse
	 * @return void
	 */
	private function storePaymentCard($postRequestData, $requestData, $gatewayResponse): void
	{
		if (isset($postRequestData->paymentType) && 'new' === $postRequestData->paymentType && $requestData->saveCard) {
			$payment_token = json_decode($requestData->paymentTokenResponse);
			$this->model_extension_payment_globalpayments_txnapi->addCard(
				$this->globalpayments->gateway->gatewayId,
				$this->customer->getId(),
				$gatewayResponse->token,
				strtoupper($payment_token->details->cardType),
				$payment_token->details->cardLast4,
				$payment_token->details->expiryYear,
				$payment_token->details->expiryMonth
			);
		}
	}
}
