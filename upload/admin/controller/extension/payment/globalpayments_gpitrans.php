<?php

use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Gateways\TransactionApiGateway;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\AccessToken\GetAccessTokenRequest;

class ControllerExtensionPaymentGlobalPaymentsGpiTrans extends Controller
{
	private $error = array();
	private $alert = array();

	public function index()
	{
		$data = [];
		$this->load->language('extension/payment/globalpayments_gpitrans');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');

		//If needed fix missing payment actions
		$this->load->model('extension/payment/globalpayments_gpitrans');
		$this->model_extension_payment_globalpayments_gpitrans->fixColumns();

		$globalpayments_txnapi_installed = in_array('globalpayments_txnapi', $extensions);

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->model('setting/setting');

			// Gpi Transaction
			if ($globalpayments_txnapi_installed) {
				$globalpayments_txnapi_messages = $this->load->controller('extension/payment/globalpayments_txnapi/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_txnapi_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_txnapi_messages['error']);
			}

			if (empty($this->error)) {
				$this->session->data['success'] = $this->language->get('text_success');

				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
			}
		}

		// Unified Payments
		if (isset($this->error['error_live_credentials_app_id'])) {
			$data['error_live_credentials_app_id'] = $this->error['error_live_credentials_app_id'];
		} else {
			$data['error_live_credentials_app_id'] = '';
		}
		if (isset($this->error['error_live_credentials_app_key'])) {
			$data['error_live_credentials_app_key'] = $this->error['error_live_credentials_app_key'];
		} else {
			$data['error_live_credentials_app_key'] = '';
		}
		if (isset($this->error['error_sandbox_credentials_app_id'])) {
			$data['error_sandbox_credentials_app_id'] = $this->error['error_sandbox_credentials_app_id'];
		} else {
			$data['error_sandbox_credentials_app_id'] = '';
		}
		if (isset($this->error['error_sandbox_credentials_app_key'])) {
			$data['error_sandbox_credentials_app_key'] = $this->error['error_sandbox_credentials_app_key'];
		} else {
			$data['error_sandbox_credentials_app_key'] = '';
		}
		if (isset($this->error['error_contact_url'])) {
			$data['error_contact_url'] = $this->error['error_contact_url'];
		} else {
			$data['error_contact_url'] = '';
		}
		if (isset($this->error['error_txn_descriptor'])) {
			$data['error_txn_descriptor'] = $this->error['error_txn_descriptor'];
		} else {
			$data['error_txn_descriptor'] = '';
		}

		$this->load->library('globalpayments');
		$data['help_is_production'] = sprintf($this->language->get('help_is_production'), TransactionApiGateway::FIRST_LINE_SUPPORT_EMAIL);
		$data['help_allow_card_saving'] = sprintf($this->language->get('help_allow_card_saving'), TransactionApiGateway::FIRST_LINE_SUPPORT_EMAIL);
		$data['help_txn_descriptor_note'] = sprintf($this->language->get('help_txn_descriptor_note'), TransactionApiGateway::FIRST_LINE_SUPPORT_EMAIL);

		$data['alerts'] = $this->alert;

		$data['tabs'] = array();

		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		if ($globalpayments_txnapi_installed) {
			$data['display_txnapi_tab'] = $this->load->controller('extension/payment/globalpayments_txnapi/display', $this->error);
			$data['tabs'][] = array(
				'id' => 'txnapi',
				'name' => $this->language->get('tab_txnapi'),
			);
		} else {
			$data['display_txnapi_tab'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/globalpayments_txnapi', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link($this->request->get['route'], 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['user_token'] = $this->session->data['user_token'];

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_gpitrans', $data));
	}

	public function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/payment/globalpayments_gpitrans')) {
			$this->alert[] = array(
				'type' => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_gpitrans_enabled'])) {
			$this->alert[] = array(
				'type' => 'success',
				'message' => $this->language->get('success_settings_gpitrans'),
			);
			return true;
		}

		if ($this->error) {
			$this->alert[] = array(
				'type' => 'danger',
				'message' => $this->language->get('error_settings_ucp'),
			);
		} else {
			$this->alert[] = array(
				'type' => 'success',
				'message' => $this->language->get('success_settings_ucp'),
			);
		}

		return !$this->error;
	}

	public function order()
	{
		$this->load->language('extension/payment/globalpayments_gpitrans_order');

		$data['user_token'] = $this->session->data['user_token'];
		$data['order_id'] = (int)$this->request->get['order_id'];
		$data['payment_code'] = 'globalpayments_ucp';

		return $this->load->view('extension/payment/globalpayments_gpitrans_order', $data);
	}

	public function getTransaction()
	{
		if (!isset($this->request->get['order_id'])) {
			return;
		}

		$this->load->library('globalpayments');

		$this->load->language('extension/payment/globalpayments_gpitrans_order');

		$data['user_token'] = $this->session->data['user_token'];
		$data['order_id'] = (int)$this->request->get['order_id'];
		$data['payment_code'] = $this->request->get['payment_code'];

		$this->load->model('extension/payment/globalpayments_gpitrans');
		$transactions = $this->model_extension_payment_globalpayments_gpitrans->getTransactions($data['order_id']);

		$should_refund = true;
		foreach ($transactions as $key => $transaction) {
			if ($transaction['payment_action'] === AbstractGateway::REVERSE) {
				$should_refund = false;
				break;
			}
		}

		$transactions_count = count($transactions);

		foreach ($transactions as $key => $transaction) {
			$transaction_actions = [];

			if ($transactions_count === 1 && $transaction['payment_action'] === AbstractGateway::AUTHORIZE) {
				$transaction_actions[] = [
					'action' => AbstractGateway::CAPTURE,
					'button' => $this->language->get('button_capture'),
				];
			}
			if ($should_refund && ($transaction['payment_action'] === AbstractGateway::CAPTURE || $transaction['payment_action'] === AbstractGateway::CHARGE)) {
				$transaction_actions[] = [
					'action' => AbstractGateway::REFUND,
					'button' => $this->language->get('button_refund'),
				];
			}
			if (($transactions_count === 2 && $transaction['payment_action'] === AbstractGateway::CAPTURE)
				|| ($transactions_count === 1 && ($transaction['payment_action'] === AbstractGateway::CHARGE || $transaction['payment_action'] === AbstractGateway::AUTHORIZE))) {
				$transaction_actions[] = [
					'action' => AbstractGateway::REVERSE,
					'button' => $this->language->get('button_reverse'),
				];
			}

			$transactions[$key]['transaction_actions'] = $transaction_actions;
			$transactions[$key]['time_created'] = date($this->language->get('datetime_format'), strtotime($transaction['time_created']));
		}

		$data['transactions'] = $transactions;

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_ucp_order_ajax', $data));
	}

	public function checkApiCredentials()
	{
		$response = [];

		if (!isset($this->request->post['app_id']) || !isset($this->request->post['app_key'])) {
			$response['error'] = $this->language->get('error_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));

			return;
		}

		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(GatewayId::TRANSACTION_API);

		$environmentValue = (bool)$this->request->post['environment'];
		$environment = Environment::TEST;
		if ($environmentValue) {
			$environment = Environment::PRODUCTION;
		}

		$ajaxData['appId'] = $this->request->post['app_id'];
		$ajaxData['appKey'] = $this->request->post['app_key'];
		$ajaxData['environment'] = $environment;

		try {
			$gatewayResponse = $this->globalpayments->gateway->processRequest(GetAccessTokenRequest::class, null, $ajaxData);
			if (!empty($gatewayResponse->token)) {
				$response['success'] = $this->language->get('success_credentials_check');
				unset($response['error']);
			} else {
				$response['error'] = $this->language->get('error_request');
			}
		} catch (Exception $e) {
			unset($response['success']);
			$response['error'] = $this->language->get('error_request') . ' ' . $e->getMessage();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function transactionCommand()
	{
		$response = [];

		$this->load->language('extension/payment/globalpayments_gpitrans_order');

		if (!isset($this->request->post['order_id'])
			|| !isset($this->request->post['gateway_id'])
			|| !isset($this->request->post['transaction_id'])
			|| !isset($this->request->post['transaction_type'])
			|| !isset($this->request->post['transaction_amount'])
			|| !isset($this->request->post['currency'])) {
			$response['error'] = $this->language->get('error_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));

			return;
		}

		$this->load->library('globalpayments');
		if (AbstractGateway::REFUND === $this->request->post['transaction_type']) {
			$amount = (isset($this->request->post['amount']) && strlen($this->request->post['amount']) > 0) ? $this->request->post['amount'] : $this->request->post['transaction_amount'];
		} else {
			$amount = $this->request->post['transaction_amount'];
		}
		$amount = $this->validateRefundAmount($amount, $this->request->post['transaction_amount']);
		if (isset($this->error['refund_amount'])) {
			$response['error'] = $this->error['refund_amount'];
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));

			return;
		}
		$this->globalpayments->setGateway(GatewayId::GP_API);

		$requestData = new RequestData();
		$requestData->transactionId = $this->request->post['transaction_id'];
		$requestData->order = new OrderData();
		$requestData->order->amount = $amount;
		$requestData->order->currency = $this->request->post['currency'];

		try {
			switch ($this->request->post['transaction_type']) {
				case AbstractGateway::CAPTURE:
					$gatewayResponse = $this->globalpayments->gateway->processCapture($requestData);
					$response['success'] = $this->language->get('text_success_capture');
					break;
				case AbstractGateway::REFUND:
					$gatewayResponse = $this->globalpayments->gateway->processRefund($requestData);
					$response['success'] = (empty($requestData->order->amount)) ? $this->language->get('text_success_full_refund') : $this->language->get('text_success_partial_refund');
					break;
				case AbstractGateway::REVERSE:
					$gatewayResponse = $this->globalpayments->gateway->processReverse($requestData);
					$response['success'] = $this->language->get('text_success_reverse');
					break;
				case AbstractGateway::GET_TRANSACTIONS_DETAILS:
					$this->getBNPLTransactionDetails($requestData);
					break;
				default:
					throw new Exception($this->language->get('error_invalid_request'));
			}

			$this->load->model('extension/payment/globalpayments_gpitrans');
			$this->model_extension_payment_globalpayments_gpitrans->addTransaction($this->request->post['order_id'], $this->request->post['gateway_id'], $this->request->post['transaction_type'], $requestData->order->amount, $requestData->order->currency, $gatewayResponse);
			unset($response['error']);
		} catch (Exception $e) {
			unset($response['success']);
			$response['error'] = $this->language->get('error_request') . ' ' . $e->getMessage();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	private function validateRefundAmount($amount, $authAmount)
	{
		$amount = str_replace(',', '.', $amount);
		$amount = number_format((float)round($amount, 2, PHP_ROUND_HALF_UP), 2, '.', '');
		if (!is_numeric($amount)) {
			$this->error['refund_amount'] = $this->language->get('error_invalid_refund_amount_format');

			return null;
		}
		if ($amount <= 0 || $amount > $authAmount) {
			$this->error['refund_amount'] = $this->language->get('error_invalid_refund_amount');

			return null;
		}

		return $amount;
	}

	private function getBNPLTransactionDetails($requestData)
	{
		if ($this->cache->get('bnpl_get_transaction_details_response')) {
			$response = $this->cache->get('bnpl_get_transaction_details_response');
		} else {
			$gatewayResponse = $this->globalpayments->gateway->getTransactionDetails($requestData);
			$details = sprintf(
				"Transaction Id: %s
				Transaction Status: %s
				Transaction Type: %s
				Amount: %s
				Currency: %s
				BNPL Provider: %s",
				$gatewayResponse->transactionId,
				$gatewayResponse->transactionStatus,
				$gatewayResponse->transactionType,
				$gatewayResponse->amount,
				$gatewayResponse->currency,
				$gatewayResponse->bnplResponse->providerName,
			);
			$response['getTransactionDetails'] = nl2br($details);
			$response['success'] = $this->language->get('text_success_getTransactionDetails');
			$this->cache->set('bnpl_get_transaction_details_response', $response);
		}

		AbstractRequest::sendJsonResponse($response);
	}

	public function install()
	{
		$this->load->model('setting/extension');

		$this->load->model('user/user_group');

		$this->load->model('extension/payment/globalpayments_gpitrans');
		$this->model_extension_payment_globalpayments_gpitrans->install();
	}

	public function uninstall()
	{
		$this->load->model('setting/extension');
		$this->load->model('extension/payment/globalpayments_gpitrans');
		$this->model_extension_payment_globalpayments_gpitrans->uninstall();
	}

	private function handleBNPLTransactions($orderId, $transaction, $transactions_count, $should_refund)
	{
		$transaction_actions = [];
		if ($transactions_count === 1 && $transaction['payment_action'] === AbstractGateway::INITIATE) {
			if ($this->user->isLogged() && $this->user->getGroupId() == 1) {
				$this->load->model('sale/order');
				$order_status_info = $this->model_sale_order->getOrder($orderId);
				$order_status = $order_status_info['order_status'];
				if ($order_status == 'Pending') {
					$this->cache->delete('bnpl_get_transaction_details_response');
					$transaction_actions[] = [
						'action' => AbstractGateway::GET_TRANSACTIONS_DETAILS,
						'button' => $this->language->get('button_getTransactionDetails'),
					];
				}
			}
		}
		if ($transactions_count === 2 && $transaction['payment_action'] === AbstractGateway::AUTHORIZE) {
			$transaction_actions[] = [
				'action' => AbstractGateway::CAPTURE,
				'button' => $this->language->get('button_capture'),
			];
		}
		if ($should_refund && ($transaction['payment_action'] === AbstractGateway::CAPTURE || $transaction['payment_action'] === AbstractGateway::CHARGE)) {
			$transaction_actions[] = [
				'action' => AbstractGateway::REFUND,
				'button' => $this->language->get('button_refund'),
			];
		}
		if (($transactions_count === 3 && $transaction['payment_action'] === AbstractGateway::CAPTURE)
			|| ($transactions_count === 2 && ($transaction['payment_action'] === AbstractGateway::CHARGE || $transaction['payment_action'] === AbstractGateway::AUTHORIZE))) {
			$transaction_actions[] = [
				'action' => AbstractGateway::REVERSE,
				'button' => $this->language->get('button_reverse'),
			];
		}

		return $transaction_actions;
	}
}
