<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Gateways\GpApiGateway;

class ControllerExtensionPaymentGlobalPaymentsUcp extends Controller {
	private $error = array();
	private $alert = array();

	public function index() {
		$data = [];
		$this->load->language('extension/payment/globalpayments_ucp');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');

		$globalpayments_googlepay_installed = in_array('globalpayments_googlepay', $extensions);
		$globalpayments_applepay_installed  = in_array('globalpayments_applepay', $extensions);

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->model('setting/setting');

			// Unified Payments
			$this->request->post['payment_globalpayments_ucp_status'] = $this->request->post['payment_globalpayments_ucp_enabled'] ?? null;
			$this->request->post['payment_globalpayments_ucp_card']   = $this->request->post['payment_globalpayments_ucp_allow_card_saving'] ?? null;
			if ($this->validate()) {
				$this->model_setting_setting->editSetting('payment_globalpayments_ucp', $this->request->post);
			}

			// Google Pay
			if ($globalpayments_googlepay_installed) {
				$globalpayments_googlepay_messages = $this->load->controller('extension/payment/globalpayments_googlepay/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_googlepay_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_googlepay_messages['error']);
			}

			// Apple Pay
			if ($globalpayments_applepay_installed) {
				$globalpayments_applepay_messages = $this->load->controller('extension/payment/globalpayments_applepay/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_applepay_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_applepay_messages['error']);
			}

			if (empty($this->error)) {
				$this->session->data['success'] = $this->language->get('text_success');

				$this->response->redirect( $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
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

		// UCP API Tab
		if (isset($this->request->post['payment_globalpayments_ucp_enabled'])) {
			$data['payment_globalpayments_ucp_enabled'] = $this->request->post['payment_globalpayments_ucp_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_enabled'] = 0;
		} else {
			$data['payment_globalpayments_ucp_enabled'] = $this->config->get('payment_globalpayments_ucp_enabled');
		}
		if ( isset($this->request->post['payment_globalpayments_ucp_title']) ) {
			$data['payment_globalpayments_ucp_title'] = $this->request->post['payment_globalpayments_ucp_title'];
		} else {
			$data['payment_globalpayments_ucp_title'] = $this->config->get('payment_globalpayments_ucp_title');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_is_production'])) {
			$data['payment_globalpayments_ucp_is_production'] = $this->request->post['payment_globalpayments_ucp_is_production'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_is_production'] = 0;
		} else {
			$data['payment_globalpayments_ucp_is_production'] = $this->config->get('payment_globalpayments_ucp_is_production');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_app_id'])) {
			$data['payment_globalpayments_ucp_app_id'] = $this->request->post['payment_globalpayments_ucp_app_id'];
		} else {
			$data['payment_globalpayments_ucp_app_id'] = $this->config->get('payment_globalpayments_ucp_app_id');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_app_key'])) {
			$data['payment_globalpayments_ucp_app_key'] = $this->request->post['payment_globalpayments_ucp_app_key'];
		} else {
			$data['payment_globalpayments_ucp_app_key'] = $this->config->get('payment_globalpayments_ucp_app_key');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_sandbox_app_id'])) {
			$data['payment_globalpayments_ucp_sandbox_app_id'] = $this->request->post['payment_globalpayments_ucp_sandbox_app_id'];
		} else {
			$data['payment_globalpayments_ucp_sandbox_app_id'] = $this->config->get('payment_globalpayments_ucp_sandbox_app_id');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_sandbox_app_key'])) {
			$data['payment_globalpayments_ucp_sandbox_app_key'] = $this->request->post['payment_globalpayments_ucp_sandbox_app_key'];
		} else {
			$data['payment_globalpayments_ucp_sandbox_app_key'] = $this->config->get('payment_globalpayments_ucp_sandbox_app_key');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_debug'])) {
			$data['payment_globalpayments_ucp_debug'] = $this->request->post['payment_globalpayments_ucp_debug'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_debug'] = 0;
		} else {
			$data['payment_globalpayments_ucp_debug'] = $this->config->get('payment_globalpayments_ucp_debug');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_contact_url'])) {
			$data['payment_globalpayments_ucp_contact_url'] = $this->request->post['payment_globalpayments_ucp_contact_url'];
		} else {
			$data['payment_globalpayments_ucp_contact_url'] = $this->config->get('payment_globalpayments_ucp_contact_url');
		}

		// Payment Tab
		if (isset($this->request->post['payment_globalpayments_ucp_payment_action'])) {
			$data['payment_globalpayments_ucp_payment_action'] = $this->request->post['payment_globalpayments_ucp_payment_action'];
		} else {
			$data['payment_globalpayments_ucp_payment_action'] = $this->config->get('payment_globalpayments_ucp_payment_action');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_allow_card_saving'])) {
			$data['payment_globalpayments_ucp_allow_card_saving'] = $this->request->post['payment_globalpayments_ucp_allow_card_saving'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_allow_card_saving'] = 0;
		} else {
			$data['payment_globalpayments_ucp_allow_card_saving'] = $this->config->get('payment_globalpayments_ucp_allow_card_saving');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_txn_descriptor'])) {
			$data['payment_globalpayments_ucp_txn_descriptor'] = $this->request->post['payment_globalpayments_ucp_txn_descriptor'];
		} else {
			$data['payment_globalpayments_ucp_txn_descriptor'] = $this->config->get('payment_globalpayments_ucp_txn_descriptor');
		}

		$this->load->library('globalpayments');
		$data['help_is_production']       = sprintf($this->language->get('help_is_production'), GpApiGateway::FIRST_LINE_SUPPORT_EMAIL);
		$data['help_allow_card_saving']   = sprintf($this->language->get('help_allow_card_saving'), GpApiGateway::FIRST_LINE_SUPPORT_EMAIL);
		$data['help_txn_descriptor_note'] = sprintf($this->language->get('help_txn_descriptor_note'), GpApiGateway::FIRST_LINE_SUPPORT_EMAIL);

		$data['alerts'] = $this->alert;

		$data['tabs']   = array();
		$data['tabs'][] = array(
			'id'   => 'ucp',
			'name' => $this->language->get('tab_ucp'),
		);
		$data['tabs'][] = array(
			'id'   => 'payment',
			'name' => $this->language->get('tab_payment'),
		);
		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		if ($globalpayments_googlepay_installed) {
			$data['display_googlepay_tab'] = $this->load->controller('extension/payment/globalpayments_googlepay/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'googlepay',
				'name' => $this->language->get('tab_googlepay'),
			);
		} else {
			$data['display_googlepay_tab'] = '';
		}
		if ($globalpayments_applepay_installed) {
			$data['display_applepay_tab'] = $this->load->controller('extension/payment/globalpayments_applepay/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'applepay',
				'name' => $this->language->get('tab_applepay'),
			);
		} else {
			$data['display_applepay_tab'] = '';
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
			'href' => $this->url->link('extension/payment/globalpayments_ucp', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link($this->request->get['route'], 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_ucp', $data));
	}

	public function validate() {
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_ucp')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_ucp_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_ucp'),
			);
			return true;
		}
		if ( ! $this->request->post['payment_globalpayments_ucp_contact_url']
		     || strlen($this->request->post['payment_globalpayments_ucp_contact_url']) > 256) {
			$this->error['error_contact_url'] = $this->language->get('error_contact_url');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_txn_descriptor'])
		     && strlen($this->request->post['payment_globalpayments_ucp_txn_descriptor']) > 25) {
			$this->error['error_txn_descriptor'] = $this->language->get('error_txn_descriptor');
		}
		if ( ! empty($this->request->post['payment_globalpayments_ucp_is_production'])) {
			if (empty($this->request->post['payment_globalpayments_ucp_app_id'])) {
				$this->error['error_live_credentials_app_id'] = $this->language->get('error_live_credentials_app_id');
			}
			if (empty($this->request->post['payment_globalpayments_ucp_app_key'])) {
				$this->error['error_live_credentials_app_key'] = $this->language->get('error_live_credentials_app_key');
			}
		} else {
			if (empty($this->request->post['payment_globalpayments_ucp_sandbox_app_id'])) {
				$this->error['error_sandbox_credentials_app_id'] = $this->language->get('error_sandbox_credentials_app_id');
			}
			if (empty($this->request->post['payment_globalpayments_ucp_sandbox_app_key'])) {
				$this->error['error_sandbox_credentials_app_key'] = $this->language->get('error_sandbox_credentials_app_key');
			}
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_ucp'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_ucp'),
			);
		}

		return ! $this->error;
	}

	public function order() {
		$this->load->language('extension/payment/globalpayments_ucp_order');

		$data['user_token'] = $this->session->data['user_token'];

		$data['order_id'] = (int)$this->request->get['order_id'];

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function getTransaction() {
		if ( ! isset($this->request->get['order_id'])) {
			return;
		}

		$this->load->library('globalpayments');

		$this->load->language('extension/payment/globalpayments_ucp_order');

		$data['user_token'] = $this->session->data['user_token'];

		$data['order_id'] = (int)$this->request->get['order_id'];

		$this->load->model('extension/payment/globalpayments_ucp');
		$transactions  = $this->model_extension_payment_globalpayments_ucp->getTransactions($data['order_id']);

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
			$transactions[$key]['time_created']        = date($this->language->get('datetime_format'), strtotime($transaction['time_created']));
		}

		$data['transactions'] = $transactions;

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_ucp_order_ajax', $data));
	}

	public function transactionCommand() {
		$response = [];

		$this->load->language('extension/payment/globalpayments_ucp_order');

		if ( ! isset($this->request->post['order_id'])
		     || ! isset($this->request->post['gateway_id'])
		     || ! isset($this->request->post['transaction_id'])
		     || ! isset($this->request->post['transaction_type'])
		     || ! isset($this->request->post['transaction_amount'])
		     || ! isset($this->request->post['currency'])) {
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

		$requestData                  = new RequestData();
		$requestData->transactionId   = $this->request->post['transaction_id'];
		$requestData->order           = new OrderData();
		$requestData->order->amount   = $amount;
		$requestData->order->currency = $this->request->post['currency'];

		try {
			switch ($this->request->post['transaction_type']) {
				case AbstractGateway::CAPTURE:
					$gatewayResponse     = $this->globalpayments->gateway->processCapture($requestData);
					$response['success'] = $this->language->get('text_success_capture');
					break;
				case AbstractGateway::REFUND:
					$gatewayResponse     = $this->globalpayments->gateway->processRefund($requestData);
					$response['success'] = (empty($requestData->order->amount)) ? $this->language->get('text_success_full_refund') : $this->language->get('text_success_partial_refund');
					break;
				case AbstractGateway::REVERSE:
					$gatewayResponse     = $this->globalpayments->gateway->processReverse($requestData);
					$response['success'] = $this->language->get('text_success_reverse');
					break;
				default:
					throw new \Exception($this->language->get('error_invalid_request'));
			}

			$this->load->model('extension/payment/globalpayments_ucp');
			$this->model_extension_payment_globalpayments_ucp->addTransaction($this->request->post['order_id'], $this->request->post['gateway_id'], $this->request->post['transaction_type'], $requestData->order->amount, $requestData->order->currency, $gatewayResponse);
			unset($response['error']);
		} catch (\Exception $e) {
			unset($response['success']);
			$response['error'] = $this->language->get('error_request') . ' ' . $e->getMessage();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	private function validateRefundAmount($amount, $authAmount) {
		$amount = str_replace(',', '.', $amount);
		$amount = number_format((float)round($amount, 2, PHP_ROUND_HALF_UP), 2, '.', '');
		if ( ! is_numeric($amount)) {
			$this->error['refund_amount'] = $this->language->get('error_invalid_refund_amount_format');

			return null;
		}
		if ($amount <= 0 || $amount>$authAmount) {
			$this->error['refund_amount'] = $this->language->get('error_invalid_refund_amount');

			return null;
		}

		return $amount;
	}

	public function install() {
		$this->load->model('setting/extension');
		$this->model_setting_extension->install('payment', 'globalpayments_googlepay');
		$this->model_setting_extension->install('payment', 'globalpayments_applepay');

		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_googlepay');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_googlepay');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_applepay');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_applepay');

		$this->load->model('extension/payment/globalpayments_ucp');
		$this->model_extension_payment_globalpayments_ucp->install();
		$this->load->model('extension/payment/globalpayments_googlepay');
		$this->model_extension_payment_globalpayments_googlepay->install();
		$this->load->model('extension/payment/globalpayments_applepay');
		$this->model_extension_payment_globalpayments_applepay->install();
	}

	public function uninstall() {
		$this->load->model('setting/extension');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_googlepay');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_applepay');

		$this->load->model('extension/payment/globalpayments_ucp');
		$this->model_extension_payment_globalpayments_ucp->uninstall();
	}
}
