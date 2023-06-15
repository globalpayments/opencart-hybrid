<?php

class ControllerExtensionPaymentGlobalPaymentsClearpay extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_clearpay');

		if (isset($this->request->post['payment_globalpayments_clearpay_enabled'])) {
			$data['payment_globalpayments_clearpay_enabled'] = $this->request->post['payment_globalpayments_clearpay_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_clearpay_enabled'] = 0;
		} else {
			$data['payment_globalpayments_clearpay_enabled'] = $this->config->get( 'payment_globalpayments_clearpay_enabled' );
		}
		if ( isset($this->request->post['payment_globalpayments_clearpay_title'])) {
			$data['payment_globalpayments_clearpay_title'] = $this->request->post['payment_globalpayments_clearpay_title'];
		} else {
			$data['payment_globalpayments_clearpay_title'] = $this->config->get('payment_globalpayments_clearpay_title');
		}

		if (isset($this->request->post['payment_globalpayments_clearpay_payment_action'])) {
			$data['payment_globalpayments_clearpay_payment_action'] = $this->request->post['payment_globalpayments_clearpay_payment_action'];
		} else {
			$data['payment_globalpayments_clearpay_payment_action'] = $this->config->get('payment_globalpayments_clearpay_payment_action');
		}
		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_clearpay', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_clearpay');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_clearpay')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_clearpay_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_clearpay'),
			);
			return true;
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_clearpay'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_clearpay'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_clearpay_status'] = $this->request->post['payment_globalpayments_clearpay_enabled'] ?? null;
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_clearpay', $this->request->post);
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
		$data['payment_code'] = 'globalpayments_clearpay';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_clearpay');
		$this->model_extension_payment_globalpayments_clearpay->install();

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
