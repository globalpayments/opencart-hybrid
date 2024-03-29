<?php

class ControllerExtensionPaymentGlobalPaymentsAffirm extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_affirm');

		if (isset($this->request->post['payment_globalpayments_affirm_enabled'])) {
			$data['payment_globalpayments_affirm_enabled'] = $this->request->post['payment_globalpayments_affirm_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_affirm_enabled'] = 0;
		} else {
			$data['payment_globalpayments_affirm_enabled'] = $this->config->get( 'payment_globalpayments_affirm_enabled' );
		}
		if ( isset($this->request->post['payment_globalpayments_affirm_title'])) {
			$data['payment_globalpayments_affirm_title'] = $this->request->post['payment_globalpayments_affirm_title'];
		} else {
			$data['payment_globalpayments_affirm_title'] = $this->config->get('payment_globalpayments_affirm_title');
		}
		if (isset($this->request->post['payment_globalpayments_affirm_payment_action'])) {
			$data['payment_globalpayments_affirm_payment_action'] = $this->request->post['payment_globalpayments_affirm_payment_action'];
		} else {
			$data['payment_globalpayments_affirm_payment_action'] = $this->config->get('payment_globalpayments_affirm_payment_action');
		}
		if (isset($this->request->post['payment_globalpayments_affirm_sort_order'])) {
			$data['payment_globalpayments_affirm_sort_order'] = $this->request->post['payment_globalpayments_affirm_sort_order'];
		} else {
			$data['payment_globalpayments_affirm_sort_order'] = $this->config->get('payment_globalpayments_affirm_sort_order') ?? 0;
		}

		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_affirm', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_affirm');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_affirm')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_affirm_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_affirm'),
			);
			return true;
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_affirm'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_affirm'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_affirm_status'] = $this->request->post['payment_globalpayments_affirm_enabled'] ?? null;
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_affirm', $this->request->post);
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
		$data['payment_code'] = 'globalpayments_affirm';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_affirm');
		$this->model_extension_payment_globalpayments_affirm->install();

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
