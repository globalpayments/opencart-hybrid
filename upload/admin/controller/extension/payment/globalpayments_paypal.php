<?php

class ControllerExtensionPaymentGlobalPaymentsPaypal extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_paypal');

		if (isset($this->request->post['payment_globalpayments_paypal_enabled'])) {
			$data['payment_globalpayments_paypal_enabled'] = $this->request->post['payment_globalpayments_paypal_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_paypal_enabled'] = 0;
		} else {
			$data['payment_globalpayments_paypal_enabled'] = $this->config->get( 'payment_globalpayments_paypal_enabled' );
		}
		if (isset($this->request->post['payment_globalpayments_paypal_title'])) {
			$data['payment_globalpayments_paypal_title'] = $this->request->post['payment_globalpayments_paypal_title'];
		} else {
			$data['payment_globalpayments_paypal_title'] = $this->config->get('payment_globalpayments_paypal_title');
		}
		if (isset($this->request->post['payment_globalpayments_paypal_payment_action'])) {
			$data['payment_globalpayments_paypal_payment_action'] = $this->request->post['payment_globalpayments_paypal_payment_action'];
		} else {
			$data['payment_globalpayments_paypal_payment_action'] = $this->config->get('payment_globalpayments_paypal_payment_action');
		}

		if (isset($this->request->post['payment_globalpayments_paypal_sort_order'])) {
			$data['payment_globalpayments_paypal_sort_order'] = $this->request->post['payment_globalpayments_paypal_sort_order'];
		} else {
			$data['payment_globalpayments_paypal_sort_order'] = $this->config->get('payment_globalpayments_paypal_sort_order') ?? 0;
		}

		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_paypal', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_paypal');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_paypal')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_paypal_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_paypal'),
			);
			return true;
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_paypal'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_paypal'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_paypal_status'] = $this->request->post['payment_globalpayments_paypal_enabled'] ?? null;
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_paypal', $this->request->post);
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
		$data['payment_code'] = 'globalpayments_paypal';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_paypal');
		$this->model_extension_payment_globalpayments_paypal->install();

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
