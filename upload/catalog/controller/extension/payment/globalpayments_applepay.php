<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class ControllerExtensionPaymentGlobalPaymentsApplePay extends Controller {
	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(GatewayId::APPLE_PAY);
	}

	public function index() {
		$this->load->language('extension/payment/globalpayments_applepay');

		$this->setOrder();

		$data['action'] = $this->url->link('extension/payment/globalpayments_applepay/confirm', '', true);

		$data['gateway'] = $this->globalpayments->gateway;

		if ($this->customer->isLogged()) {
			$data['customer_is_logged'] = true;
		} else {
			$data['customer_is_logged'] = false;
		}

		$data['globalpayments_applepay_params'] = $this->globalpayments->gateway->paymentFieldsParams();
		$data['globalpayments_order']           = json_encode($this->order);

		return $this->load->view('extension/payment/globalpayments_applepay', $data);
	}

	public function validateMerchant() {
		$postRequestData = AbstractRequest::getPostRequestData();

		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);

		$this->globalpayments->gateway->validateMerchant($requestData);
	}

	public function confirm() {
		$this->load->language('extension/payment/globalpayments_ucp');
		try {
			$this->setOrder();
			if (empty($this->request->post[$this->globalpayments->gateway->gatewayId])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}

			$postRequestData                   = (object)$this->request->post[$this->globalpayments->gateway->gatewayId];
			$requestData                       = new RequestData();
			$requestData                       = RequestData::setDataObject($requestData, $postRequestData);
			$requestData->order                = $this->order;

			$requestData->gatewayId                         = $this->globalpayments->gateway->gatewayId;
			$requestData->digitalWalletPaymentTokenResponse = htmlspecialchars_decode($postRequestData->digitalWalletTokenResponse);
			$requestData->mobileType                        = $this->globalpayments->gateway->mobileType;
			$requestData->dynamicDescriptor                 = $this->config->get('payment_globalpayments_ucp_txn_descriptor');

			$gatewayResponse = $this->globalpayments->gateway->processPayment($requestData);

			$this->load->model('checkout/order');
			$comment = [
				$this->language->get('text_comment_txn_id') . ' ' . $gatewayResponse->transactionReference->transactionId,
				$this->language->get('text_comment_response_code') . ' ' . $gatewayResponse->responseCode,
				$this->language->get('text_comment_response_status') . ' ' . $gatewayResponse->responseMessage,
				$this->language->get('text_comment_amount') . ' ' . $this->order->amount,
				$this->language->get('text_comment_currency') . ' ' . $this->order->currency,
			];
			$comment = implode('<br/>', $comment);
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 2, $comment);

			$this->load->model('extension/payment/globalpayments_ucp');
			$this->model_extension_payment_globalpayments_ucp->addTransaction($this->order->orderReference, $this->globalpayments->gateway->gatewayId, $this->globalpayments->gateway->paymentAction, $this->order->amount, $this->order->currency, $gatewayResponse);

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

		$this->load->model( 'checkout/order' );
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order                 = new OrderData();
		$order->amount         = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$order->currency       = $order_info['currency_code'];
		$order->orderReference = $this->session->data['order_id'];

		$order->customerEmail = $order_info['email'];

		$this->order = $order;
	}
}
