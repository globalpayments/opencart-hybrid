<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\Api\Entities\Transaction;

class ControllerExtensionPaymentGlobalPaymentsGenius extends Controller
{
	private $error = array();
	private const ACTION_AUTHORIZE = 'authorize';
	private const ACTION_CHARGE = 'charge';
	private const ACTION_CAPTURE = 'capture';
	private const ACTION_REFUND = 'refund';
	private const ACTION_REVERSE = 'reverse';
	private const GATEWAY_ID = 'globalpayments_genius';

	public function index()
	{
		$data = [];
		$this->load->language('extension/payment/globalpayments_genius');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->model('setting/setting');

			if ($this->validate()) {
				$this->model_setting_setting->editSetting('payment_globalpayments_genius', $this->request->post);

				$this->session->data['success'] = $this->language->get('text_success');

				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
			}
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
			'href' => $this->url->link('extension/payment/globalpayments_genius', 'user_token=' . $this->session->data['user_token'], true)
		);

		// Text strings
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_authorize'] = $this->language->get('text_authorize');
		$data['text_capture'] = $this->language->get('text_capture');

		// Entry labels
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_live_mode'] = $this->language->get('entry_live_mode');
		$data['entry_sandbox_merchant_name'] = $this->language->get('entry_sandbox_merchant_name');
		$data['entry_sandbox_merchant_site_id'] = $this->language->get('entry_sandbox_merchant_site_id');
		$data['entry_sandbox_merchant_key'] = $this->language->get('entry_sandbox_merchant_key');
		$data['entry_sandbox_web_api_key'] = $this->language->get('entry_sandbox_web_api_key');
		$data['entry_live_merchant_name'] = $this->language->get('entry_live_merchant_name');
		$data['entry_live_merchant_site_id'] = $this->language->get('entry_live_merchant_site_id');
		$data['entry_live_merchant_key'] = $this->language->get('entry_live_merchant_key');
		$data['entry_live_web_api_key'] = $this->language->get('entry_live_web_api_key');
		$data['entry_payment_action'] = $this->language->get('entry_payment_action');
		$data['entry_allow_card_saving'] = $this->language->get('entry_allow_card_saving');
		$data['entry_txn_descriptor'] = $this->language->get('entry_txn_descriptor');
		$data['entry_check_avs_cvn'] = $this->language->get('entry_check_avs_cvn');
		$data['entry_avs_reject_conditions'] = $this->language->get('entry_avs_reject_conditions');
		$data['entry_cvn_reject_conditions'] = $this->language->get('entry_cvn_reject_conditions');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		// Help text
		$data['help_title'] = $this->language->get('help_title');
		$data['help_live_mode'] = $this->language->get('help_live_mode');
		$data['help_payment_action'] = $this->language->get('help_payment_action');
		$data['help_allow_card_saving'] = $this->language->get('help_allow_card_saving');
		$data['help_txn_descriptor'] = $this->language->get('help_txn_descriptor');
		$data['help_check_avs_cvn'] = $this->language->get('help_check_avs_cvn');
		$data['help_avs_reject_conditions'] = $this->language->get('help_avs_reject_conditions');
		$data['help_cvn_reject_conditions'] = $this->language->get('help_cvn_reject_conditions');

		// Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		// Error handling
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['error_sandbox_merchant_name'] = $this->error['sandbox_merchant_name'] ?? '';
		$data['error_sandbox_merchant_site_id'] = $this->error['sandbox_merchant_site_id'] ?? '';
		$data['error_sandbox_merchant_key'] = $this->error['sandbox_merchant_key'] ?? '';
		$data['error_sandbox_web_api_key'] = $this->error['sandbox_web_api_key'] ?? '';
		$data['error_live_merchant_name'] = $this->error['live_merchant_name'] ?? '';
		$data['error_live_merchant_site_id'] = $this->error['live_merchant_site_id'] ?? '';
		$data['error_live_merchant_key'] = $this->error['live_merchant_key'] ?? '';
		$data['error_live_web_api_key'] = $this->error['live_web_api_key'] ?? '';

		$data['action'] = $this->url->link('extension/payment/globalpayments_genius', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		// Form values - Status
		if (isset($this->request->post['payment_globalpayments_genius_status'])) {
			$data['payment_globalpayments_genius_status'] = !empty($this->request->post['payment_globalpayments_genius_status']) ? '1' : '0';
		} else {
			$data['payment_globalpayments_genius_status'] = $this->config->get('payment_globalpayments_genius_status');
		}

		// Title
		if (isset($this->request->post['payment_globalpayments_genius_title'])) {
			$data['payment_globalpayments_genius_title'] = $this->request->post['payment_globalpayments_genius_title'];
		} else {
			$data['payment_globalpayments_genius_title'] = $this->config->get('payment_globalpayments_genius_title') ?: 'Credit Card';
		}

		// Live Mode
		if (isset($this->request->post['payment_globalpayments_genius_live_mode'])) {
			$data['payment_globalpayments_genius_live_mode'] = !empty($this->request->post['payment_globalpayments_genius_live_mode']) ? '1' : '0';
		} else {
			$data['payment_globalpayments_genius_live_mode'] = $this->config->get('payment_globalpayments_genius_live_mode');
		}

		// Sandbox credentials
		if (isset($this->request->post['payment_globalpayments_genius_sandbox_merchant_name'])) {
			$data['payment_globalpayments_genius_sandbox_merchant_name'] = $this->request->post['payment_globalpayments_genius_sandbox_merchant_name'];
		} else {
			$data['payment_globalpayments_genius_sandbox_merchant_name'] = $this->config->get('payment_globalpayments_genius_sandbox_merchant_name');
		}

		if (isset($this->request->post['payment_globalpayments_genius_sandbox_merchant_site_id'])) {
			$data['payment_globalpayments_genius_sandbox_merchant_site_id'] = $this->request->post['payment_globalpayments_genius_sandbox_merchant_site_id'];
		} else {
			$data['payment_globalpayments_genius_sandbox_merchant_site_id'] = $this->config->get('payment_globalpayments_genius_sandbox_merchant_site_id');
		}

		if (isset($this->request->post['payment_globalpayments_genius_sandbox_merchant_key'])) {
			$data['payment_globalpayments_genius_sandbox_merchant_key'] = $this->request->post['payment_globalpayments_genius_sandbox_merchant_key'];
		} else {
			$data['payment_globalpayments_genius_sandbox_merchant_key'] = $this->config->get('payment_globalpayments_genius_sandbox_merchant_key');
		}

		if (isset($this->request->post['payment_globalpayments_genius_sandbox_web_api_key'])) {
			$data['payment_globalpayments_genius_sandbox_web_api_key'] = $this->request->post['payment_globalpayments_genius_sandbox_web_api_key'];
		} else {
			$data['payment_globalpayments_genius_sandbox_web_api_key'] = $this->config->get('payment_globalpayments_genius_sandbox_web_api_key');
		}

		// Live credentials
		if (isset($this->request->post['payment_globalpayments_genius_live_merchant_name'])) {
			$data['payment_globalpayments_genius_live_merchant_name'] = $this->request->post['payment_globalpayments_genius_live_merchant_name'];
		} else {
			$data['payment_globalpayments_genius_live_merchant_name'] = $this->config->get('payment_globalpayments_genius_live_merchant_name');
		}

		if (isset($this->request->post['payment_globalpayments_genius_live_merchant_site_id'])) {
			$data['payment_globalpayments_genius_live_merchant_site_id'] = $this->request->post['payment_globalpayments_genius_live_merchant_site_id'];
		} else {
			$data['payment_globalpayments_genius_live_merchant_site_id'] = $this->config->get('payment_globalpayments_genius_live_merchant_site_id');
		}

		if (isset($this->request->post['payment_globalpayments_genius_live_merchant_key'])) {
			$data['payment_globalpayments_genius_live_merchant_key'] = $this->request->post['payment_globalpayments_genius_live_merchant_key'];
		} else {
			$data['payment_globalpayments_genius_live_merchant_key'] = $this->config->get('payment_globalpayments_genius_live_merchant_key');
		}

		if (isset($this->request->post['payment_globalpayments_genius_live_web_api_key'])) {
			$data['payment_globalpayments_genius_live_web_api_key'] = $this->request->post['payment_globalpayments_genius_live_web_api_key'];
		} else {
			$data['payment_globalpayments_genius_live_web_api_key'] = $this->config->get('payment_globalpayments_genius_live_web_api_key');
		}

		// Payment Action
		if (isset($this->request->post['payment_globalpayments_genius_payment_action'])) {
			$data['payment_globalpayments_genius_payment_action'] = $this->request->post['payment_globalpayments_genius_payment_action'];
		} else {
			$data['payment_globalpayments_genius_payment_action'] = $this->config->get('payment_globalpayments_genius_payment_action') ?: 'authorize';
		}

		// Allow Card Saving
		if (isset($this->request->post['payment_globalpayments_genius_card'])) {
			$data['payment_globalpayments_genius_card'] = !empty($this->request->post['payment_globalpayments_genius_card']) ? '1' : '0';
		} else {
			$data['payment_globalpayments_genius_card'] = $this->config->get('payment_globalpayments_genius_card');
		}

		// Transaction Descriptor
		if (isset($this->request->post['payment_globalpayments_genius_order_transaction_descriptor'])) {
			$data['payment_globalpayments_genius_order_transaction_descriptor'] = $this->request->post['payment_globalpayments_genius_order_transaction_descriptor'];
		} else {
			$data['payment_globalpayments_genius_order_transaction_descriptor'] = $this->config->get('payment_globalpayments_genius_order_transaction_descriptor');
		}

		// Check AVS/CVN
		if (isset($this->request->post['payment_globalpayments_genius_check_avs_cvn'])) {
			$data['payment_globalpayments_genius_check_avs_cvn'] = !empty($this->request->post['payment_globalpayments_genius_check_avs_cvn']) ? '1' : '0';
		} else {
			$data['payment_globalpayments_genius_check_avs_cvn'] = $this->config->get('payment_globalpayments_genius_check_avs_cvn');
		}

		// AVS Reject Conditions
		if (isset($this->request->post['payment_globalpayments_genius_avs_reject_conditions'])) {
			$data['payment_globalpayments_genius_avs_reject_conditions'] = $this->request->post['payment_globalpayments_genius_avs_reject_conditions'];
		} else {
			$avs_conditions = $this->config->get('payment_globalpayments_genius_avs_reject_conditions');
			$data['payment_globalpayments_genius_avs_reject_conditions'] = is_array($avs_conditions) ? $avs_conditions : array();
		}

		// CVN Reject Conditions
		if (isset($this->request->post['payment_globalpayments_genius_cvn_reject_conditions'])) {
			$data['payment_globalpayments_genius_cvn_reject_conditions'] = $this->request->post['payment_globalpayments_genius_cvn_reject_conditions'];
		} else {
			$cvn_conditions = $this->config->get('payment_globalpayments_genius_cvn_reject_conditions');
			$data['payment_globalpayments_genius_cvn_reject_conditions'] = is_array($cvn_conditions) ? $cvn_conditions : array();
		}

		// Sort Order
		if (isset($this->request->post['payment_globalpayments_genius_sort_order'])) {
			$data['payment_globalpayments_genius_sort_order'] = $this->request->post['payment_globalpayments_genius_sort_order'];
		} else {
			$data['payment_globalpayments_genius_sort_order'] = $this->config->get('payment_globalpayments_genius_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_genius', $data));
	}

	public function order() {
		$this->load->language('extension/payment/globalpayments_genius_order');

		$data['user_token'] = $this->session->data['user_token'];
		$data['order_id'] = (int)$this->request->get['order_id'];
		$data['payment_code'] = 'globalpayments_genius';

		return $this->load->view('extension/payment/globalpayments_genius_order', $data);
	}

	public function getTransaction() {
		if (!isset($this->request->get['order_id'])) {
			return;
		}

		$this->load->language('extension/payment/globalpayments_genius_order');

		$data['user_token'] = $this->session->data['user_token'];
		$data['order_id'] = (int)$this->request->get['order_id'];
		$data['payment_code'] = $this->request->get['payment_code'];

		$this->load->model('extension/payment/globalpayments_genius');
		$transactions = $this->model_extension_payment_globalpayments_genius->getTransactions($data['order_id']);

		$refundableAmount = $this->getRefundableAmount($transactions);
		$should_refund = $refundableAmount > 0.00001;
		$has_refund_or_reverse = false;
		foreach ($transactions as $transaction) {
			if ($transaction['payment_action'] === self::ACTION_REVERSE && $this->isSuccessfulTransaction($transaction)) {
				$should_refund = false;
				$has_refund_or_reverse = true;
				break;
			}
		}

		$transactions_count = count($transactions);

		foreach ($transactions as $key => $transaction) {
			$transaction_actions = array();

			if ($transactions_count === 1 && $transaction['payment_action'] === self::ACTION_AUTHORIZE) {
				$transaction_actions[] = array(
					'action' => self::ACTION_CAPTURE,
					'button' => $this->language->get('button_capture'),
				);
			}

			if ($should_refund
				&& $this->isSuccessfulTransaction($transaction)
				&& ($transaction['payment_action'] === self::ACTION_CAPTURE || $transaction['payment_action'] === self::ACTION_CHARGE)) {
				$transaction_actions[] = array(
					'action' => self::ACTION_REFUND,
					'button' => $this->language->get('button_refund'),
				);
			}

			if (!$has_refund_or_reverse && (
				($transactions_count === 2 && $transaction['payment_action'] === self::ACTION_CAPTURE)
				|| ($transactions_count === 1 && $transaction['payment_action'] === self::ACTION_AUTHORIZE)
			)) {
				$transaction_actions[] = array(
					'action' => self::ACTION_REVERSE,
					'button' => $this->language->get('button_reverse'),
				);
			}

			$transactions[$key]['gateway_id'] = self::GATEWAY_ID;
			$transactions[$key]['transaction_actions'] = $transaction_actions;
			$transactions[$key]['refund_amount'] = number_format($refundableAmount, 2, '.', '');
			$transactions[$key]['time_created'] = date($this->language->get('datetime_format'), strtotime($transaction['time_created']));
		}

		$data['transactions'] = $transactions;

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_genius_order_ajax', $data));
	}

	public function transactionCommand() {
		$response = array();

		if (!$this->user->hasPermission('modify', 'extension/payment/globalpayments_genius')) {
			$response['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));
			return;
		}

		$this->load->language('extension/payment/globalpayments_genius_order');

		if (!isset($this->request->post['order_id'])
			|| !isset($this->request->post['transaction_id'])
			|| !isset($this->request->post['transaction_type'])
			|| !isset($this->request->post['transaction_amount'])
			|| !isset($this->request->post['currency'])) {
			$response['error'] = $this->language->get('error_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));
			return;
		}

		$maxRefundableAmount = null;
		if (self::ACTION_REFUND === $this->request->post['transaction_type']) {
			$amount = (isset($this->request->post['amount']) && strlen($this->request->post['amount']) > 0) ? $this->request->post['amount'] : $this->request->post['transaction_amount'];

			$this->load->model('extension/payment/globalpayments_genius');
			$transactions = $this->model_extension_payment_globalpayments_genius->getTransactions((int)$this->request->post['order_id']);
			$maxRefundableAmount = $this->getRefundableAmount($transactions);

			if ($maxRefundableAmount <= 0.00001) {
				$response['error'] = $this->language->get('error_already_fully_refunded');
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				return;
			}
		} else {
			$amount = $this->request->post['transaction_amount'];
		}

		$amount = $this->validateRefundAmount($amount, self::ACTION_REFUND === $this->request->post['transaction_type'] ? $maxRefundableAmount : $this->request->post['transaction_amount']);
		if (isset($this->error['refund_amount'])) {
			$response['error'] = $this->error['refund_amount'];
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));
			return;
		}

		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(self::GATEWAY_ID);

		$requestData = new RequestData();
		$requestData->transactionId = $this->request->post['transaction_id'];
		$requestData->amount = $amount;
		$requestData->currency = $this->request->post['currency'];
		$requestData->order = new OrderData();
		$requestData->order->amount = $amount;
		$requestData->order->currency = $this->request->post['currency'];

		try {
			switch ($this->request->post['transaction_type']) {
				case self::ACTION_CAPTURE:
					$gatewayResponse = $this->globalpayments->gateway->processCapture($requestData);
					$response['success'] = $this->language->get('text_success_capture');
					$new_order_status_id = 2; // Processing
					$order_comment = sprintf('Genius Capture Successful - Transaction ID: %s', $this->request->post['transaction_id']);
					break;
				case self::ACTION_REFUND:
					$gatewayResponse = $this->globalpayments->gateway->processRefund($requestData);
					$response['success'] = ($maxRefundableAmount !== null && (float)$amount >= (float)$maxRefundableAmount)
						? $this->language->get('text_success_full_refund')
						: $this->language->get('text_success_partial_refund');
					$new_order_status_id = 11; // Refunded
					$order_comment = sprintf('Genius Refund Successful - Amount: %s %s, Transaction ID: %s', $amount, $this->request->post['currency'], $this->request->post['transaction_id']);
					break;
				case self::ACTION_REVERSE:
					$gatewayResponse = Transaction::fromId($this->request->post['transaction_id'])->void()->execute();
					$response['success'] = $this->language->get('text_success_reverse');
					$new_order_status_id = 12;
					$order_comment = sprintf('Genius Reverse Successful - Transaction ID: %s', $this->request->post['transaction_id']);
					break;
				default:
					throw new Exception($this->language->get('error_invalid_request'));
			}

			$this->load->model('extension/payment/globalpayments_genius');
			$this->model_extension_payment_globalpayments_genius->addTransaction(
				$this->request->post['order_id'],
				$this->request->post['transaction_type'],
				$requestData->order->amount,
				$requestData->order->currency,
				$gatewayResponse
			);

			$order_id_int = (int)$this->request->post['order_id'];
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$new_order_status_id . "', date_modified = NOW() WHERE order_id = '" . $order_id_int . "'");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET order_id = '" . $order_id_int . "', order_status_id = '" . (int)$new_order_status_id . "', notify = '0', comment = '" . $this->db->escape($order_comment) . "', date_added = NOW()");

			unset($response['error']);
		} catch (\Throwable $e) {
			unset($response['success']);
			$message = trim($e->getMessage());
			$response['error'] = $message ? $this->language->get('error_request') . ' ' . $message : $this->language->get('error_request');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	private function validateRefundAmount($amount, $authAmount) {
		$amountValue = $this->normalizeAmount($amount);
		$authorizedAmountValue = $this->normalizeAmount($authAmount);

		if ($amountValue === null || $authorizedAmountValue === null) {
			$this->error['refund_amount'] = $this->language->get('error_invalid_refund_amount_format');
			return null;
		}

		if ($amountValue <= 0 || ($amountValue - $authorizedAmountValue) > 0.00001) {
			$this->error['refund_amount'] = $this->language->get('error_invalid_refund_amount');
			return null;
		}

		return number_format($amountValue, 2, '.', '');
	}

	private function getRefundableAmount(array $transactions) {
		$capturedAmount = 0.0;
		$refundedAmount = 0.0;

		foreach ($transactions as $transaction) {
			if (!$this->isSuccessfulTransaction($transaction)) {
				continue;
			}

			$amount = isset($transaction['amount']) ? (float)$transaction['amount'] : 0.0;
			if ($transaction['payment_action'] === self::ACTION_CAPTURE || $transaction['payment_action'] === self::ACTION_CHARGE) {
				$capturedAmount += $amount;
			}

			if ($transaction['payment_action'] === self::ACTION_REFUND) {
				$refundedAmount += $amount;
			}

			if ($transaction['payment_action'] === self::ACTION_REVERSE) {
				return 0.0;
			}
		}

		$remaining = $capturedAmount - $refundedAmount;

		return $remaining > 0 ? (float)number_format($remaining, 2, '.', '') : 0.0;
	}

	private function isSuccessfulTransaction(array $transaction) {
		$responseCode = isset($transaction['response_code']) ? strtoupper((string)$transaction['response_code']) : '';
		$responseMessage = isset($transaction['response_message']) ? strtoupper((string)$transaction['response_message']) : '';

		return in_array($responseCode, array('SUCCESS', '00'), true) || $responseMessage === 'APPROVED';
	}

	private function normalizeAmount($value) {
		$value = trim((string)$value);
		if ($value === '') {
			return null;
		}

		$value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
		if ($value === '' || $value === false || $value === '-' || $value === '.' || $value === ',') {
			return null;
		}

		if (strpos($value, ',') !== false && strpos($value, '.') === false) {
			$value = str_replace(',', '.', $value);
		} else {
			$value = str_replace(',', '', $value);
		}

		if (!is_numeric($value)) {
			return null;
		}

		return (float)number_format((float)$value, 2, '.', '');
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/payment/globalpayments_genius')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$is_enabled = !empty($this->request->post['payment_globalpayments_genius_status']);

		if ($is_enabled) {
			$live_mode = !empty($this->request->post['payment_globalpayments_genius_live_mode']);
			
			if ($live_mode) {
				// Live mode validation
				if (empty($this->request->post['payment_globalpayments_genius_live_merchant_name'])) {
					$this->error['live_merchant_name'] = $this->language->get('error_live_merchant_name');
				}
				
				if (empty($this->request->post['payment_globalpayments_genius_live_merchant_site_id'])) {
					$this->error['live_merchant_site_id'] = $this->language->get('error_live_merchant_site_id');
				}
				
				if (empty($this->request->post['payment_globalpayments_genius_live_merchant_key'])) {
					$this->error['live_merchant_key'] = $this->language->get('error_live_merchant_key');
				}

				if (empty($this->request->post['payment_globalpayments_genius_live_web_api_key'])) {
					$this->error['live_web_api_key'] = $this->language->get('error_live_web_api_key');
				}
			} else {
				// Sandbox mode validation
				if (empty($this->request->post['payment_globalpayments_genius_sandbox_merchant_name'])) {
					$this->error['sandbox_merchant_name'] = $this->language->get('error_sandbox_merchant_name');
				}
				
				if (empty($this->request->post['payment_globalpayments_genius_sandbox_merchant_site_id'])) {
					$this->error['sandbox_merchant_site_id'] = $this->language->get('error_sandbox_merchant_site_id');
				}
				
				if (empty($this->request->post['payment_globalpayments_genius_sandbox_merchant_key'])) {
					$this->error['sandbox_merchant_key'] = $this->language->get('error_sandbox_merchant_key');
				}

				if (empty($this->request->post['payment_globalpayments_genius_sandbox_web_api_key'])) {
					$this->error['sandbox_web_api_key'] = $this->language->get('error_sandbox_web_api_key');
				}
			}
		}

		if (!$this->error) {
			return true;
		}

		if (!isset($this->error['warning'])) {
			$this->error['warning'] = 'Please fill all required Genius credentials.';
		}

		return false;
	}

	public function install()
	{
		// Create database tables
		$this->load->model('extension/payment/globalpayments_genius');
		$this->model_extension_payment_globalpayments_genius->install();
		
		// Set default settings
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('payment_globalpayments_genius', [
			'payment_globalpayments_genius_status' => '0',
			'payment_globalpayments_genius_title' => 'Credit Card',
			'payment_globalpayments_genius_sort_order' => '1',
			'payment_globalpayments_genius_live_mode' => '0',
			'payment_globalpayments_genius_payment_action' => 'authorize',
			'payment_globalpayments_genius_card' => '0',
			'payment_globalpayments_genius_check_avs_cvn' => '0'
		]);

		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('gp_account_credit_cards');
		$this->model_setting_event->addEvent(
			'gp_account_credit_cards',
			'catalog/view/account/account/after',
			'extension/payment/globalpayments_account/injectCreditCardsHTML',
			true,
			0
		);
	}

	public function uninstall()
	{
		// Drop database tables
		$this->load->model('extension/payment/globalpayments_genius');
		$this->model_extension_payment_globalpayments_genius->uninstall();
		
		// Remove all settings
		$this->load->model('setting/setting');
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code = 'payment_globalpayments_genius'");

		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('gp_account_credit_cards');
	}
}
