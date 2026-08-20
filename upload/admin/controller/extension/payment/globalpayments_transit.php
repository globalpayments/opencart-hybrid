<?php

use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\ServiceConfigs\Gateways\TransitConfig;
use GlobalPayments\Api\ServiceConfigs\AcceptorConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\Api\Utils\Logging\SampleRequestLogger;

class ControllerExtensionPaymentGlobalPaymentsTransit extends Controller
{
	private $error = array();
	private const DEVELOPER_ID = '003226G001';

	public function index()
	{
		$data = [];
		$this->load->language('extension/payment/globalpayments_transit');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->model('setting/setting');

			// Normalize checkbox values so settings are always saved as explicit 1/0.
			$this->request->post['payment_globalpayments_transit_status'] =
				(isset($this->request->post['payment_globalpayments_transit_status']) && (string)$this->request->post['payment_globalpayments_transit_status'] === '1') ? '1' : '0';
			$this->request->post['payment_globalpayments_transit_debug'] =
				(isset($this->request->post['payment_globalpayments_transit_debug']) && (string)$this->request->post['payment_globalpayments_transit_debug'] === '1') ? '1' : '0';
			$this->request->post['payment_globalpayments_transit_card'] =
				(isset($this->request->post['payment_globalpayments_transit_card']) && (string)$this->request->post['payment_globalpayments_transit_card'] === '1') ? '1' : '0';
			$this->request->post['payment_globalpayments_transit_check_avs_cvn'] =
				(isset($this->request->post['payment_globalpayments_transit_check_avs_cvn']) && (string)$this->request->post['payment_globalpayments_transit_check_avs_cvn'] === '1') ? '1' : '0';

			if ($this->validate()) {
				$this->model_setting_setting->editSetting('payment_globalpayments_transit', $this->request->post);

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
			'href' => $this->url->link('extension/payment/globalpayments_transit', 'user_token=' . $this->session->data['user_token'], true)
		);

		// Text strings
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		// Labels
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_is_production'] = $this->language->get('entry_is_production');
		$data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
		$data['entry_user_id'] = $this->language->get('entry_user_id');
		$data['entry_password'] = $this->language->get('entry_password');
		$data['entry_device_id'] = $this->language->get('entry_device_id');
		$data['entry_tsep_device_id'] = $this->language->get('entry_tsep_device_id');
		$data['entry_transaction_key'] = $this->language->get('entry_transaction_key');
		$data['entry_sandbox_merchant_id'] = $this->language->get('entry_sandbox_merchant_id');
		$data['entry_sandbox_user_id'] = $this->language->get('entry_sandbox_user_id');
		$data['entry_sandbox_password'] = $this->language->get('entry_sandbox_password');
		$data['entry_sandbox_device_id'] = $this->language->get('entry_sandbox_device_id');
		$data['entry_sandbox_tsep_device_id'] = $this->language->get('entry_sandbox_tsep_device_id');
		$data['entry_sandbox_transaction_key'] = $this->language->get('entry_sandbox_transaction_key');
		$data['entry_allow_card_saving'] = $this->language->get('entry_allow_card_saving');
		$data['entry_payment_action'] = $this->language->get('entry_payment_action');
		$data['entry_payment_action_authorize'] = $this->language->get('entry_payment_action_authorize');
		$data['entry_payment_action_charge'] = $this->language->get('entry_payment_action_charge');
		$data['entry_transaction_descriptor'] = $this->language->get('entry_transaction_descriptor');
		$data['entry_debug'] = $this->language->get('entry_debug');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		// Help text
		$data['help_title'] = $this->language->get('help_title');
		$data['help_is_production'] = $this->language->get('help_is_production');
		$data['help_payment_action'] = $this->language->get('help_payment_action');
		$data['help_transaction_descriptor'] = $this->language->get('help_transaction_descriptor');
		$data['help_allow_card_saving'] = $this->language->get('help_allow_card_saving');
		$data['help_debug'] = $this->language->get('help_debug');

		// Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		// Error handling
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['device_id'])) {
			$data['error_device_id'] = $this->error['device_id'];
		} else {
			$data['error_device_id'] = '';
		}

		if (isset($this->error['sandbox_merchant_id'])) {
			$data['error_sandbox_merchant_id'] = $this->error['sandbox_merchant_id'];
		} else {
			$data['error_sandbox_merchant_id'] = '';
		}

		if (isset($this->error['sandbox_device_id'])) {
			$data['error_sandbox_device_id'] = $this->error['sandbox_device_id'];
		} else {
			$data['error_sandbox_device_id'] = '';
		}

		$data['action'] = $this->url->link('extension/payment/globalpayments_transit', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		// Form values
		if (isset($this->request->post['payment_globalpayments_transit_status'])) {
			$data['payment_globalpayments_transit_status'] = $this->request->post['payment_globalpayments_transit_status'];
		} else {
			$data['payment_globalpayments_transit_status'] = $this->config->get('payment_globalpayments_transit_status');
		}

		if (isset($this->request->post['payment_globalpayments_transit_title'])) {
			$data['payment_globalpayments_transit_title'] = $this->request->post['payment_globalpayments_transit_title'];
		} else {
			$data['payment_globalpayments_transit_title'] = $this->config->get('payment_globalpayments_transit_title');
		}

		if (isset($this->request->post['payment_globalpayments_transit_is_production'])) {
			$data['payment_globalpayments_transit_is_production'] = $this->request->post['payment_globalpayments_transit_is_production'];
		} else {
			$data['payment_globalpayments_transit_is_production'] = $this->config->get('payment_globalpayments_transit_is_production');
		}

		if (isset($this->request->post['payment_globalpayments_transit_merchant_id'])) {
			$data['payment_globalpayments_transit_merchant_id'] = $this->request->post['payment_globalpayments_transit_merchant_id'];
		} else {
			$data['payment_globalpayments_transit_merchant_id'] = $this->config->get('payment_globalpayments_transit_merchant_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_user_id'])) {
			$data['payment_globalpayments_transit_user_id'] = $this->request->post['payment_globalpayments_transit_user_id'];
		} else {
			$data['payment_globalpayments_transit_user_id'] = $this->config->get('payment_globalpayments_transit_user_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_password'])) {
			$data['payment_globalpayments_transit_password'] = $this->request->post['payment_globalpayments_transit_password'];
		} else {
			$data['payment_globalpayments_transit_password'] = $this->config->get('payment_globalpayments_transit_password');
		}

		if (isset($this->request->post['payment_globalpayments_transit_device_id'])) {
			$data['payment_globalpayments_transit_device_id'] = $this->request->post['payment_globalpayments_transit_device_id'];
		} else {
			$data['payment_globalpayments_transit_device_id'] = $this->config->get('payment_globalpayments_transit_device_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_tsep_device_id'])) {
			$data['payment_globalpayments_transit_tsep_device_id'] = $this->request->post['payment_globalpayments_transit_tsep_device_id'];
		} else {
			$data['payment_globalpayments_transit_tsep_device_id'] = $this->config->get('payment_globalpayments_transit_tsep_device_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_transaction_key'])) {
			$data['payment_globalpayments_transit_transaction_key'] = $this->request->post['payment_globalpayments_transit_transaction_key'];
		} else {
			$data['payment_globalpayments_transit_transaction_key'] = $this->config->get('payment_globalpayments_transit_transaction_key');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sandbox_merchant_id'])) {
			$data['payment_globalpayments_transit_sandbox_merchant_id'] = $this->request->post['payment_globalpayments_transit_sandbox_merchant_id'];
		} else {
			$data['payment_globalpayments_transit_sandbox_merchant_id'] = $this->config->get('payment_globalpayments_transit_sandbox_merchant_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sandbox_user_id'])) {
			$data['payment_globalpayments_transit_sandbox_user_id'] = $this->request->post['payment_globalpayments_transit_sandbox_user_id'];
		} else {
			$data['payment_globalpayments_transit_sandbox_user_id'] = $this->config->get('payment_globalpayments_transit_sandbox_user_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sandbox_password'])) {
			$data['payment_globalpayments_transit_sandbox_password'] = $this->request->post['payment_globalpayments_transit_sandbox_password'];
		} else {
			$data['payment_globalpayments_transit_sandbox_password'] = $this->config->get('payment_globalpayments_transit_sandbox_password');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sandbox_device_id'])) {
			$data['payment_globalpayments_transit_sandbox_device_id'] = $this->request->post['payment_globalpayments_transit_sandbox_device_id'];
		} else {
			$data['payment_globalpayments_transit_sandbox_device_id'] = $this->config->get('payment_globalpayments_transit_sandbox_device_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sandbox_tsep_device_id'])) {
			$data['payment_globalpayments_transit_sandbox_tsep_device_id'] = $this->request->post['payment_globalpayments_transit_sandbox_tsep_device_id'];
		} else {
			$data['payment_globalpayments_transit_sandbox_tsep_device_id'] = $this->config->get('payment_globalpayments_transit_sandbox_tsep_device_id');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sandbox_transaction_key'])) {
			$data['payment_globalpayments_transit_sandbox_transaction_key'] = $this->request->post['payment_globalpayments_transit_sandbox_transaction_key'];
		} else {
			$data['payment_globalpayments_transit_sandbox_transaction_key'] = $this->config->get('payment_globalpayments_transit_sandbox_transaction_key');
		}

		if (isset($this->request->post['payment_globalpayments_transit_debug'])) {
			$data['payment_globalpayments_transit_debug'] = $this->request->post['payment_globalpayments_transit_debug'];
		} else {
			$data['payment_globalpayments_transit_debug'] = $this->config->get('payment_globalpayments_transit_debug');
		}

		if (isset($this->request->post['payment_globalpayments_transit_card'])) {
			$data['payment_globalpayments_transit_card'] = $this->request->post['payment_globalpayments_transit_card'];
		} else {
			$data['payment_globalpayments_transit_card'] = $this->config->get('payment_globalpayments_transit_card');
		}

		if (isset($this->request->post['payment_globalpayments_transit_payment_action'])) {
			$data['payment_globalpayments_transit_payment_action'] = $this->request->post['payment_globalpayments_transit_payment_action'];
		} else {
			$data['payment_globalpayments_transit_payment_action'] = $this->config->get('payment_globalpayments_transit_payment_action') ?: 'charge';
		}

		if (isset($this->request->post['payment_globalpayments_transit_transaction_descriptor'])) {
			$data['payment_globalpayments_transit_transaction_descriptor'] = $this->request->post['payment_globalpayments_transit_transaction_descriptor'];
		} else {
			$data['payment_globalpayments_transit_transaction_descriptor'] = $this->config->get('payment_globalpayments_transit_transaction_descriptor');
		}

		if (isset($this->request->post['payment_globalpayments_transit_sort_order'])) {
			$data['payment_globalpayments_transit_sort_order'] = $this->request->post['payment_globalpayments_transit_sort_order'];
		} else {
			$data['payment_globalpayments_transit_sort_order'] = $this->config->get('payment_globalpayments_transit_sort_order');
		}

		// AVS Settings
		if (isset($this->request->post['payment_globalpayments_transit_check_avs_cvn'])) {
			$data['payment_globalpayments_transit_check_avs_cvn'] = $this->request->post['payment_globalpayments_transit_check_avs_cvn'];
		} else {
			$data['payment_globalpayments_transit_check_avs_cvn'] = $this->config->get('payment_globalpayments_transit_check_avs_cvn');
		}

		if (isset($this->request->post['payment_globalpayments_transit_avs_reject_conditions'])) {
			$data['payment_globalpayments_transit_avs_reject_conditions'] = $this->request->post['payment_globalpayments_transit_avs_reject_conditions'];
		} else {
			$avs_conditions = $this->config->get('payment_globalpayments_transit_avs_reject_conditions');
			$data['payment_globalpayments_transit_avs_reject_conditions'] = $avs_conditions ?: array('N', 'S', 'U', 'P', 'R', 'G', 'C', 'I');
		}

		if (isset($this->request->post['payment_globalpayments_transit_cvn_reject_conditions'])) {
			$data['payment_globalpayments_transit_cvn_reject_conditions'] = $this->request->post['payment_globalpayments_transit_cvn_reject_conditions'];
		} else {
			$cvn_conditions = $this->config->get('payment_globalpayments_transit_cvn_reject_conditions');
			$data['payment_globalpayments_transit_cvn_reject_conditions'] = $cvn_conditions ?: array('P', '?', 'N');
		}

		// Language strings for AVS codes
		$data['entry_check_avs_cvn'] = $this->language->get('entry_check_avs_cvn');
		$data['entry_avs_reject_conditions'] = $this->language->get('entry_avs_reject_conditions');
		$data['entry_cvn_reject_conditions'] = $this->language->get('entry_cvn_reject_conditions');
		$data['help_check_avs_cvn'] = $this->language->get('help_check_avs_cvn');
		$data['help_avs_reject_conditions'] = $this->language->get('help_avs_reject_conditions');
		$data['help_cvn_reject_conditions'] = $this->language->get('help_cvn_reject_conditions');

		// AVS Code labels
		$data['avs_code_a'] = $this->language->get('avs_code_a');
		$data['avs_code_b'] = $this->language->get('avs_code_b');
		$data['avs_code_c'] = $this->language->get('avs_code_c');
		$data['avs_code_d'] = $this->language->get('avs_code_d');
		$data['avs_code_g'] = $this->language->get('avs_code_g');
		$data['avs_code_i'] = $this->language->get('avs_code_i');
		$data['avs_code_m'] = $this->language->get('avs_code_m');
		$data['avs_code_n'] = $this->language->get('avs_code_n');
		$data['avs_code_p'] = $this->language->get('avs_code_p');
		$data['avs_code_r'] = $this->language->get('avs_code_r');
		$data['avs_code_s'] = $this->language->get('avs_code_s');
		$data['avs_code_u'] = $this->language->get('avs_code_u');
		$data['avs_code_w'] = $this->language->get('avs_code_w');
		$data['avs_code_x'] = $this->language->get('avs_code_x');
		$data['avs_code_y'] = $this->language->get('avs_code_y');
		$data['avs_code_z'] = $this->language->get('avs_code_z');

		// CVN Code labels
		$data['cvn_code_n'] = $this->language->get('cvn_code_n');
		$data['cvn_code_p'] = $this->language->get('cvn_code_p');
		$data['cvn_code_s'] = $this->language->get('cvn_code_s');
		$data['cvn_code_u'] = $this->language->get('cvn_code_u');
		$data['cvn_code_question'] = $this->language->get('cvn_code_question');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_transit', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/payment/globalpayments_transit')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Check if the payment method is enabled
		$is_enabled = isset($this->request->post['payment_globalpayments_transit_status']) &&
			(string) $this->request->post['payment_globalpayments_transit_status'] === '1';

		if ($is_enabled) {
			// Get is_production value (0 for sandbox, 1 for live)
			$is_production = isset($this->request->post['payment_globalpayments_transit_is_production']) &&
							  $this->request->post['payment_globalpayments_transit_is_production'] === '1';
			
			if ($is_production) {
				// Live mode validation
				$merchant_id = isset($this->request->post['payment_globalpayments_transit_merchant_id'])
					? trim($this->request->post['payment_globalpayments_transit_merchant_id'])
					: '';
				if (!$merchant_id) {
					$this->error['merchant_id'] = $this->language->get('error_merchant_id');
				}
				
				$device_id = isset($this->request->post['payment_globalpayments_transit_device_id'])
					? trim($this->request->post['payment_globalpayments_transit_device_id'])
					: '';
				if (!$device_id) {
					$this->error['device_id'] = $this->language->get('error_device_id');
				}
			} else {
				// Sandbox mode validation
				$sandbox_merchant_id = isset($this->request->post['payment_globalpayments_transit_sandbox_merchant_id'])
					? trim($this->request->post['payment_globalpayments_transit_sandbox_merchant_id'])
					: '';
				if (!$sandbox_merchant_id) {
					$this->error['sandbox_merchant_id'] = $this->language->get('error_sandbox_merchant_id');
				}
				
				$sandbox_device_id = isset($this->request->post['payment_globalpayments_transit_sandbox_device_id'])
					? trim($this->request->post['payment_globalpayments_transit_sandbox_device_id'])
					: '';
				if (!$sandbox_device_id) {
					$this->error['sandbox_device_id'] = $this->language->get('error_sandbox_device_id');
				}
			}
		}

		return !$this->error;
	}

	public function install()
	{
		$this->load->model('extension/payment/globalpayments_transit');
		$this->model_extension_payment_globalpayments_transit->install();
		
		// Set default enabled status to 0 (disabled)
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('payment_globalpayments_transit', [
			'payment_globalpayments_transit_status' => '0',
			'payment_globalpayments_transit_title' => 'GlobalPayments TransIT',
			'payment_globalpayments_transit_card' => '0',
			'payment_globalpayments_transit_payment_action' => 'charge',
			'payment_globalpayments_transit_sort_order' => '1',
			'payment_globalpayments_transit_is_production' => '0'
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
		$this->load->model('extension/payment/globalpayments_transit');
		$this->model_extension_payment_globalpayments_transit->uninstall();

		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('gp_account_credit_cards');
		
		// Remove all settings
		$this->load->model('setting/setting');
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code = 'payment_globalpayments_transit'");
	}

	public function order() {
		$this->load->language('extension/payment/globalpayments_transit_order');

		$data['user_token']   = $this->session->data['user_token'];
		$data['order_id']     = (int)$this->request->get['order_id'];
		$data['payment_code'] = 'globalpayments_transit';

		return $this->load->view('extension/payment/globalpayments_transit_order', $data);
	}

	public function getTransaction() {
		if (!isset($this->request->get['order_id'])) {
			return;
		}

		$this->load->language('extension/payment/globalpayments_transit_order');

		$data['user_token']   = $this->session->data['user_token'];
		$data['order_id']     = (int)$this->request->get['order_id'];
		$data['payment_code'] = $this->request->get['payment_code'];

		$this->load->model('extension/payment/globalpayments_transit');
		$transactions = $this->model_extension_payment_globalpayments_transit->getTransactions($data['order_id']);

		$refundableAmount      = $this->getRefundableAmount($transactions);
		$should_refund         = $refundableAmount > 0.00001;
		$has_refund_or_reverse = false;

		foreach ($transactions as $transaction) {
			if ($transaction['payment_action'] === 'reverse' && $this->isSuccessfulTransaction($transaction)) {
				$should_refund         = false;
				$has_refund_or_reverse = true;
				break;
			}
		}

		$transactions_count = count($transactions);

		foreach ($transactions as $key => $transaction) {
			$transaction_actions = array();

			if ($transactions_count === 1 && $transaction['payment_action'] === 'authorize') {
				$transaction_actions[] = array(
					'action' => 'capture',
					'button' => $this->language->get('button_capture'),
				);
			}

			if ($should_refund
				&& $this->isSuccessfulTransaction($transaction)
				&& ($transaction['payment_action'] === 'capture' || $transaction['payment_action'] === 'charge')) {
				$transaction_actions[] = array(
					'action' => 'refund',
					'button' => $this->language->get('button_refund'),
				);
			}

			if (!$has_refund_or_reverse && (
				($transactions_count === 2 && $transaction['payment_action'] === 'capture')
				|| ($transactions_count === 1 && $transaction['payment_action'] === 'authorize')
			)) {
				$transaction_actions[] = array(
					'action' => 'reverse',
					'button' => $this->language->get('button_reverse'),
				);
			}

			$transactions[$key]['gateway_id']         = 'globalpayments_transit';
			$transactions[$key]['transaction_actions'] = $transaction_actions;
			$transactions[$key]['refund_amount']       = number_format($refundableAmount, 2, '.', '');
			$transactions[$key]['time_created']        = date($this->language->get('datetime_format'), strtotime($transaction['time_created']));
		}

		$data['transactions'] = $transactions;

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_transit_order_ajax', $data));
	}

	public function transactionCommand() {
		$response = array();

		if (!$this->user->hasPermission('modify', 'extension/payment/globalpayments_transit')) {
			$response['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));
			return;
		}

		$this->load->language('extension/payment/globalpayments_transit_order');

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

		$transaction_id = $this->request->post['transaction_id'];
		$currency       = $this->request->post['currency'];

		$maxRefundableAmount = null;
		if ('refund' === $this->request->post['transaction_type']) {
			$amount = (isset($this->request->post['amount']) && strlen($this->request->post['amount']) > 0)
				? $this->request->post['amount']
				: $this->request->post['transaction_amount'];

			$this->load->model('extension/payment/globalpayments_transit');
			$transactions        = $this->model_extension_payment_globalpayments_transit->getTransactions((int)$this->request->post['order_id']);
			$maxRefundableAmount = $this->getRefundableAmount($transactions);

			if ($maxRefundableAmount <= 0.00001) {
				$response['error'] = $this->language->get('error_already_fully_refunded') ?: 'This transaction has already been fully refunded.';
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				return;
			}
		} else {
			$amount = $this->request->post['transaction_amount'];
		}

		$amount = $this->validateRefundAmount(
			$amount,
			'refund' === $this->request->post['transaction_type'] ? $maxRefundableAmount : $this->request->post['transaction_amount']
		);
		if (isset($this->error['refund_amount'])) {
			$response['error'] = $this->error['refund_amount'];
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));
			return;
		}

		$this->load->library('globalpayments');
		$this->configureGateway();

		try {
			switch ($this->request->post['transaction_type']) {
				case 'capture':
					$gatewayResponse = Transaction::fromId($transaction_id)->capture(floatval($amount))->execute();
					$response['success'] = $this->language->get('text_success_capture');
					$new_order_status_id = 2; // Processing
					$order_comment = sprintf('TransIT Capture Successful - Transaction ID: %s', $transaction_id);
					break;
				case 'refund':
					$gatewayResponse = Transaction::fromId($transaction_id)
						->refund(floatval($amount))
						->withCurrency($currency)
						->execute();
					$response['success'] = ($maxRefundableAmount !== null && (float)$amount >= (float)$maxRefundableAmount)
						? $this->language->get('text_success_full_refund')
						: $this->language->get('text_success_partial_refund');
					$new_order_status_id = 11; // Refunded
					$order_comment = sprintf('TransIT Refund Successful - Amount: %s %s, Transaction ID: %s', $amount, $currency, $transaction_id);
					break;
				case 'reverse':
					$gatewayResponse = Transaction::fromId($transaction_id)->void()->execute();
					$response['success'] = $this->language->get('text_success_reverse');
					$new_order_status_id = 12; // Reversed
					$order_comment = sprintf('TransIT Reverse Successful - Transaction ID: %s', $transaction_id);
					break;
				default:
					throw new Exception($this->language->get('error_invalid_request'));
			}

			$this->load->model('extension/payment/globalpayments_transit');
			$this->model_extension_payment_globalpayments_transit->addTransaction(
				$this->request->post['order_id'],
				$this->request->post['transaction_type'],
				$amount,
				$currency,
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

	private function configureGateway() {
		$transitConfig = new TransitConfig();
		$transitConfig->environment = $this->config->get('payment_globalpayments_transit_is_production') ? Environment::PRODUCTION : Environment::TEST;
		$transitConfig->developerId = self::DEVELOPER_ID;

		$acceptorConfig = new AcceptorConfig();
		$transitConfig->acceptorConfig = $acceptorConfig;

		if ($this->config->get('payment_globalpayments_transit_is_production')) {
			$transitConfig->merchantId    = $this->config->get('payment_globalpayments_transit_merchant_id');
			$transitConfig->deviceId      = $this->config->get('payment_globalpayments_transit_device_id');
			$transitConfig->transactionKey = $this->config->get('payment_globalpayments_transit_transaction_key');
			$transitConfig->username      = $this->config->get('payment_globalpayments_transit_user_id');
			$transitConfig->password      = $this->config->get('payment_globalpayments_transit_password');
		} else {
			$transitConfig->merchantId    = $this->config->get('payment_globalpayments_transit_sandbox_merchant_id');
			$transitConfig->deviceId      = $this->config->get('payment_globalpayments_transit_sandbox_device_id');
			$transitConfig->transactionKey = $this->config->get('payment_globalpayments_transit_sandbox_transaction_key');
			$transitConfig->username      = $this->config->get('payment_globalpayments_transit_sandbox_user_id');
			$transitConfig->password      = $this->config->get('payment_globalpayments_transit_sandbox_password');
		}

		if ($this->config->get('payment_globalpayments_transit_debug')) {
			$transitConfig->requestLogger = new SampleRequestLogger(new Logger(DIR_LOGS));
		}

		ServicesContainer::configureService($transitConfig);
	}

	private function validateRefundAmount($amount, $authAmount) {
		$amountValue           = $this->normalizeAmount($amount);
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

			if ($transaction['payment_action'] === 'capture' || $transaction['payment_action'] === 'charge') {
				$capturedAmount += $amount;
			}

			if ($transaction['payment_action'] === 'refund') {
				$refundedAmount += $amount;
			}

			if ($transaction['payment_action'] === 'reverse') {
				return 0.0;
			}
		}

		$remaining = $capturedAmount - $refundedAmount;

		return $remaining > 0 ? (float)number_format($remaining, 2, '.', '') : 0.0;
	}

	private function isSuccessfulTransaction(array $transaction) {
		$responseCode    = isset($transaction['response_code'])    ? strtoupper((string)$transaction['response_code'])    : '';
		$responseMessage = isset($transaction['response_message']) ? strtoupper((string)$transaction['response_message']) : '';

		return in_array($responseCode, array('A0000', '00', 'SUCCESS'), true) || $responseMessage === 'SUCCESS';
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
}