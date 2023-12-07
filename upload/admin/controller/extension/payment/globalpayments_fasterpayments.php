<?php

class ControllerExtensionPaymentGlobalPaymentsFasterPayments extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_fasterpayments');

		if (isset($this->request->post['payment_globalpayments_fasterpayments_enabled'])) {
			$data['payment_globalpayments_fasterpayments_enabled'] = $this->request->post['payment_globalpayments_fasterpayments_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_fasterpayments_enabled'] = 0;
		} else {
			$data['payment_globalpayments_fasterpayments_enabled'] = $this->config->get( 'payment_globalpayments_fasterpayments_enabled' );
		}

		if (isset($this->request->post['payment_globalpayments_fasterpayments_title'])) {
			$data['payment_globalpayments_fasterpayments_title'] = $this->request->post['payment_globalpayments_fasterpayments_title'];
		} else {
			$data['payment_globalpayments_fasterpayments_title'] = $this->config->get('payment_globalpayments_fasterpayments_title');
		}

		if (isset($this->request->post['payment_globalpayments_fasterpayments_payment_action'])) {
			$data['payment_globalpayments_fasterpayments_payment_action'] = $this->request->post['payment_globalpayments_fasterpayments_payment_action'];
		} else {
			$data['payment_globalpayments_fasterpayments_payment_action'] = $this->config->get('payment_globalpayments_fasterpayments_payment_action');
		}

		if (isset($this->request->post['payment_globalpayments_fasterpayments_account_number'])) {
			$data['payment_globalpayments_fasterpayments_account_number'] = $this->request->post['payment_globalpayments_fasterpayments_account_number'];
		} else {
			$data['payment_globalpayments_fasterpayments_account_number'] = $this->config->get('payment_globalpayments_fasterpayments_account_number');
		}

		if (isset($this->request->post['payment_globalpayments_fasterpayments_account_name'])) {
			$data['payment_globalpayments_fasterpayments_account_name'] = $this->request->post['payment_globalpayments_fasterpayments_account_name'];
		} else {
			$data['payment_globalpayments_fasterpayments_account_name'] = $this->config->get('payment_globalpayments_fasterpayments_account_name');
		}

		if (isset($this->request->post['payment_globalpayments_fasterpayments_sort_code'])) {
			$data['payment_globalpayments_fasterpayments_sort_code'] = $this->request->post['payment_globalpayments_fasterpayments_sort_code'];
		} else {
			$data['payment_globalpayments_fasterpayments_sort_code'] = $this->config->get('payment_globalpayments_fasterpayments_sort_code');
		}

		if (isset($this->request->post['payment_globalpayments_fasterpayments_countries'])) {
			$data['payment_globalpayments_fasterpayments_countries'] = $this->request->post['payment_globalpayments_fasterpayments_countries'];
		} else {
			$data['payment_globalpayments_fasterpayments_countries'] = $this->config->get('payment_globalpayments_fasterpayments_countries');
		}

		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_fasterpayments', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_fasterpayments');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_fasterpayments')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_fasterpayments_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_fasterpayments'),
			);
			return true;
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_fasterpayments'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_fasterpayments'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_fasterpayments_status'] = $this->request->post['payment_globalpayments_fasterpayments_enabled'] ?? null;
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_fasterpayments', $this->request->post);
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
		$data['payment_code'] = 'globalpayments_fasterpayments';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_fasterpayments');
		$this->model_extension_payment_globalpayments_fasterpayments->install();

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
