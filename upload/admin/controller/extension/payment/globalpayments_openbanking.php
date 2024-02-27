<?php

class ControllerExtensionPaymentGlobalPaymentsOpenBanking extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_openbanking');

		if (isset($error['error_openbanking_currencies'])) {
			$data['error_openbanking_currencies'] = $error['error_openbanking_currencies'];
		} else {
			$data['error_openbanking_currencies'] = '';
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_enabled'])) {
			$data['payment_globalpayments_openbanking_enabled'] = $this->request->post['payment_globalpayments_openbanking_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_openbanking_enabled'] = 0;
		} else {
			$data['payment_globalpayments_openbanking_enabled'] = $this->config->get('payment_globalpayments_openbanking_enabled');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_title'])) {
			$data['payment_globalpayments_openbanking_title'] = $this->request->post['payment_globalpayments_openbanking_title'];
		} else {
			$data['payment_globalpayments_openbanking_title'] = $this->config->get('payment_globalpayments_openbanking_title');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_payment_action'])) {
			$data['payment_globalpayments_openbanking_payment_action'] = $this->request->post['payment_globalpayments_openbanking_payment_action'];
		} else {
			$data['payment_globalpayments_openbanking_payment_action'] = $this->config->get('payment_globalpayments_openbanking_payment_action');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_account_number'])) {
			$data['payment_globalpayments_openbanking_account_number'] = $this->request->post['payment_globalpayments_openbanking_account_number'];
		} else {
			$data['payment_globalpayments_openbanking_account_number'] = $this->config->get('payment_globalpayments_openbanking_account_number');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_account_name'])) {
			$data['payment_globalpayments_openbanking_account_name'] = $this->request->post['payment_globalpayments_openbanking_account_name'];
		} else {
			$data['payment_globalpayments_openbanking_account_name'] = $this->config->get('payment_globalpayments_openbanking_account_name');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_sort_code'])) {
			$data['payment_globalpayments_openbanking_sort_code'] = $this->request->post['payment_globalpayments_openbanking_sort_code'];
		} else {
			$data['payment_globalpayments_openbanking_sort_code'] = $this->config->get('payment_globalpayments_openbanking_sort_code');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_countries'])) {
			$data['payment_globalpayments_openbanking_countries'] = $this->request->post['payment_globalpayments_openbanking_countries'];
		} else {
			$data['payment_globalpayments_openbanking_countries'] = $this->config->get('payment_globalpayments_openbanking_countries');
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_iban'])) {
			$data['payment_globalpayments_openbanking_iban'] = $this->request->post['payment_globalpayments_openbanking_iban'];
		} else {
			$data['payment_globalpayments_openbanking_iban'] = $this->config->get('payment_globalpayments_openbanking_iban');
		}

		if (isset($this->request->post) && isset($this->request->post['payment_globalpayments_openbanking_currencies'])) {
			$data['payment_globalpayments_openbanking_currencies'] = explode(',', $this->request->post['payment_globalpayments_openbanking_currencies']);
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_openbanking_currencies'] = '';
		} else {
			$data['payment_globalpayments_openbanking_currencies'] = explode(',', $this->config->get('payment_globalpayments_openbanking_currencies'));
		}

		if (isset($this->request->post['payment_globalpayments_openbanking_sort_order'])) {
			$data['payment_globalpayments_openbanking_sort_order'] = $this->request->post['payment_globalpayments_openbanking_sort_order'];
		} else {
			$data['payment_globalpayments_openbanking_sort_order'] = $this->config->get('payment_globalpayments_openbanking_sort_order') ?? '0';
		}

		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_openbanking', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_openbanking');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_openbanking')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}

		if (empty($this->request->post['payment_globalpayments_openbanking_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_openbanking'),
			);
			return true;
		}

		if (empty($this->request->post['payment_globalpayments_openbanking_currencies'])) {
			$this->error['error_openbanking_currencies'] = $this->language->get('error_openbanking_currencies');
		}

		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_openbanking'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_openbanking'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}

		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_openbanking_status'] = $this->request->post['payment_globalpayments_openbanking_enabled'] ?? null;

		if (isset($this->request->post['payment_globalpayments_openbanking_currencies'])) {
			$this->request->post['payment_globalpayments_openbanking_currencies'] = implode(',', $this->request->post['payment_globalpayments_openbanking_currencies']);
		} else {
			$this->request->post['payment_globalpayments_openbanking_currencies'] = null;
		}

		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_openbanking', $this->request->post);
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
		$data['payment_code'] = 'globalpayments_openbanking';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_openbanking');
		$this->model_extension_payment_globalpayments_openbanking->install();

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
