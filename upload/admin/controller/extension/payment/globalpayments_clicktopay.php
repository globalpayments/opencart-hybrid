<?php

use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;

class ControllerExtensionPaymentGlobalPaymentsClickToPay extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_clicktopay');
		if (isset($error['error_ctp_client_id'])) {
			$data['error_ctp_client_id'] = $error['error_ctp_client_id'];
		} else {
			$data['error_ctp_client_id'] = '';
		}
		if (isset($error['error_clicktopay_accepted_cards'])) {
			$data['error_clicktopay_accepted_cards'] = $error['error_clicktopay_accepted_cards'];
		} else {
			$data['error_clicktopay_accepted_cards'] = '';
		}

		if (isset($this->request->post['payment_globalpayments_clicktopay_enabled'])) {
			$data['payment_globalpayments_clicktopay_enabled'] = $this->request->post['payment_globalpayments_clicktopay_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_clicktopay_enabled'] = 0;
		} else {
			$data['payment_globalpayments_clicktopay_enabled'] = $this->config->get( 'payment_globalpayments_clicktopay_enabled' );
		}
		if (isset($this->request->post['payment_globalpayments_clicktopay_title'])) {
			$data['payment_globalpayments_clicktopay_title'] = $this->request->post['payment_globalpayments_clicktopay_title'];
		} else {
			$data['payment_globalpayments_clicktopay_title'] = $this->config->get('payment_globalpayments_clicktopay_title');
		}
		if (isset($this->request->post['payment_globalpayments_clicktopay_ctp_client_id'])) {
			$data['payment_globalpayments_clicktopay_ctp_client_id'] = $this->request->post['payment_globalpayments_clicktopay_ctp_client_id'];
		} else {
			$data['payment_globalpayments_clicktopay_ctp_client_id'] = $this->config->get('payment_globalpayments_clicktopay_ctp_client_id');
		}
		if (isset($this->request->post['payment_globalpayments_clicktopay_buttonless'])) {
			$data['payment_globalpayments_clicktopay_buttonless'] = $this->request->post['payment_globalpayments_clicktopay_buttonless'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_clicktopay_buttonless'] = 0;
		} else {
			$data['payment_globalpayments_clicktopay_buttonless'] = $this->config->get('payment_globalpayments_clicktopay_buttonless');
		}
		if (isset($this->request->post['payment_globalpayments_clicktopay_canadian_debit'])) {
			$data['payment_globalpayments_clicktopay_canadian_debit'] = $this->request->post['payment_globalpayments_clicktopay_canadian_debit'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_clicktopay_canadian_debit'] = 0;
		} else {
			$data['payment_globalpayments_clicktopay_canadian_debit'] = $this->config->get('payment_globalpayments_clicktopay_canadian_debit');
		}
		if (isset($this->request->post['payment_globalpayments_clicktopay_wrapper'])) {
			$data['payment_globalpayments_clicktopay_wrapper'] = $this->request->post['payment_globalpayments_clicktopay_wrapper'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_clicktopay_wrapper'] = 0;
		} else {
			$data['payment_globalpayments_clicktopay_wrapper'] = $this->config->get('payment_globalpayments_clicktopay_wrapper');
		}
		if (isset($this->request->post) && isset($this->request->post['payment_globalpayments_clicktopay_accepted_cards'])) {
			$data['payment_globalpayments_clicktopay_accepted_cards'] = explode(',', $this->request->post['payment_globalpayments_clicktopay_accepted_cards']);
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_clicktopay_accepted_cards'] = '';
		} else {
			$data['payment_globalpayments_clicktopay_accepted_cards'] = explode(',', $this->config->get('payment_globalpayments_clicktopay_accepted_cards'));
		}
		if (!empty($this->request->post)) {
			$data['payment_globalpayments_clicktopay_payment_action'] = AbstractGateway::CHARGE;
		} else {
			$data['payment_globalpayments_clicktopay_payment_action'] = $this->config->get('payment_globalpayments_clicktopay_payment_action');
		}
		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_clicktopay', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_clicktopay');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_clicktopay')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_clicktopay_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_clicktopay'),
			);
			return true;
		}
		if (empty($this->request->post['payment_globalpayments_clicktopay_ctp_client_id'])) {
			$this->error['error_ctp_client_id'] = $this->language->get('error_ctp_client_id');
		}
		if (empty($this->request->post['payment_globalpayments_clicktopay_accepted_cards'])) {
			$this->error['error_clicktopay_accepted_cards'] = $this->language->get('error_clicktopay_accepted_cards');
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_clicktopay'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_clicktopay'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_clicktopay_status'] = $this->request->post['payment_globalpayments_clicktopay_enabled'] ?? null;
		if (isset($this->request->post['payment_globalpayments_clicktopay_accepted_cards'])) {
			$this->request->post['payment_globalpayments_clicktopay_accepted_cards'] = implode(',', $this->request->post['payment_globalpayments_clicktopay_accepted_cards']);
		} else {
			$this->request->post['payment_globalpayments_clicktopay_accepted_cards'] = null;
		}
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_clicktopay', $this->request->post);
		}
		return array(
			'error' => $this->error,
			'alert' => $this->alert,
		);
	}

	public function order() {
		$this->load->language('extension/payment/globalpayments_ucp_order');

		$data['user_token']   = $this->session->data['user_token'];
		$data['order_id']     = (int)$this->request->get['order_id'];
		$data['payment_code'] = 'globalpayments_clicktopay';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_clicktopay');
		$this->model_extension_payment_globalpayments_clicktopay->install();

		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');
		$globalpayments_ucp_installed = in_array('globalpayments_ucp', $extensions);
		if ($globalpayments_ucp_installed) {
			return;
		}

		$this->model_setting_extension->install('payment', 'globalpayments_ucp');

		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_ucp');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_ucp');

		$this->load->model('extension/payment/globalpayments_ucp');
		$this->model_extension_payment_globalpayments_ucp->install();
	}
}
