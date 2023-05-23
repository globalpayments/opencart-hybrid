<?php

class ControllerExtensionPaymentGlobalPaymentsGooglePay extends Controller {
	private $error = array();
	private $alert = array();
	public function index() {
		$this->load->controller('extension/payment/globalpayments_ucp');
	}

	public function display($error) {
		$this->load->language('extension/payment/globalpayments_googlepay');
		if (isset($error['error_gp_merchant_id'])) {
			$data['error_gp_merchant_id'] = $error['error_gp_merchant_id'];
		} else {
			$data['error_gp_merchant_id'] = '';
		}
		if (isset($error['error_googlepay_accepted_cards'])) {
			$data['error_googlepay_accepted_cards'] = $error['error_googlepay_accepted_cards'];
		} else {
			$data['error_googlepay_accepted_cards'] = '';
		}

		if (isset($this->request->post['payment_globalpayments_googlepay_enabled'])) {
			$data['payment_globalpayments_googlepay_enabled'] = $this->request->post['payment_globalpayments_googlepay_enabled'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_googlepay_enabled'] = 0;
		} else {
			$data['payment_globalpayments_googlepay_enabled'] = $this->config->get( 'payment_globalpayments_googlepay_enabled' );
		}
		if ( isset($this->request->post['payment_globalpayments_googlepay_title'])) {
			$data['payment_globalpayments_googlepay_title'] = $this->request->post['payment_globalpayments_googlepay_title'];
		} else {
			$data['payment_globalpayments_googlepay_title'] = $this->config->get('payment_globalpayments_googlepay_title');
		}
		if (isset($this->request->post['payment_globalpayments_googlepay_gp_merchant_id'])) {
			$data['payment_globalpayments_googlepay_gp_merchant_id'] = $this->request->post['payment_globalpayments_googlepay_gp_merchant_id'];
		} else {
			$data['payment_globalpayments_googlepay_gp_merchant_id'] = $this->config->get('payment_globalpayments_googlepay_gp_merchant_id');
		}
		if (isset($this->request->post['payment_globalpayments_googlepay_merchant_id'])) {
			$data['payment_globalpayments_googlepay_merchant_id'] = $this->request->post['payment_globalpayments_googlepay_merchant_id'];
		} else {
			$data['payment_globalpayments_googlepay_merchant_id'] = $this->config->get('payment_globalpayments_googlepay_merchant_id');
		}
		if (isset($this->request->post['payment_globalpayments_googlepay_merchant_name'])) {
			$data['payment_globalpayments_googlepay_merchant_name'] = $this->request->post['payment_globalpayments_googlepay_merchant_name'];
		} else {
			$data['payment_globalpayments_googlepay_merchant_name'] = $this->config->get('payment_globalpayments_googlepay_merchant_name');
		}
		if (isset($this->request->post) && isset($this->request->post['payment_globalpayments_googlepay_accepted_cards'])) {
			$data['payment_globalpayments_googlepay_accepted_cards'] = explode(',', $this->request->post['payment_globalpayments_googlepay_accepted_cards']);
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_googlepay_accepted_cards'] = '';
		} else {
			$data['payment_globalpayments_googlepay_accepted_cards'] = explode(',', $this->config->get('payment_globalpayments_googlepay_accepted_cards'));
		}
		if (isset($this->request->post['payment_globalpayments_googlepay_button_color'])) {
			$data['payment_globalpayments_googlepay_button_color'] = $this->request->post['payment_globalpayments_googlepay_button_color'];
		} else {
			$data['payment_globalpayments_googlepay_button_color'] = $this->config->get('payment_globalpayments_googlepay_button_color');
		}
		if (isset($this->request->post['payment_globalpayments_googlepay_payment_action'])) {
			$data['payment_globalpayments_googlepay_payment_action'] = $this->request->post['payment_globalpayments_googlepay_payment_action'];
		} else {
			$data['payment_globalpayments_googlepay_payment_action'] = $this->config->get('payment_globalpayments_googlepay_payment_action');
		}
		$data['active_tab'] = str_replace('extension/payment/globalpayments_', '', $this->request->get['route']);

		return $this->load->view('extension/payment/globalpayments_googlepay', $data);
	}

	public function validate() {
		$this->load->language('extension/payment/globalpayments_googlepay');
		if ( ! $this->user->hasPermission('modify', 'extension/payment/globalpayments_googlepay')) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_googlepay_enabled'])) {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_googlepay'),
			);
			return true;
		}
		if (empty($this->request->post['payment_globalpayments_googlepay_gp_merchant_id'])) {
			$this->error['error_gp_merchant_id'] = $this->language->get('error_gp_merchant_id');
		}
		if (empty($this->request->post['payment_globalpayments_googlepay_accepted_cards'])) {
			$this->error['error_googlepay_accepted_cards'] = $this->language->get('error_googlepay_accepted_cards');
		}
		if ($this->error) {
			$this->alert[] = array(
				'type'    => 'danger',
				'message' => $this->language->get('error_settings_googlepay'),
			);
		} else {
			$this->alert[] = array(
				'type'    => 'success',
				'message' => $this->language->get('success_settings_googlepay'),
			);
		}

		return ! $this->error;
	}

	public function save() {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		$this->request->post['payment_globalpayments_googlepay_status'] = $this->request->post['payment_globalpayments_googlepay_enabled'] ?? null;
		if (isset($this->request->post['payment_globalpayments_googlepay_accepted_cards'])) {
			$this->request->post['payment_globalpayments_googlepay_accepted_cards'] = implode(',', $this->request->post['payment_globalpayments_googlepay_accepted_cards']);
		} else {
			$this->request->post['payment_globalpayments_googlepay_accepted_cards'] = null;
		}
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_googlepay', $this->request->post);
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
		$data['payment_code'] = 'globalpayments_googlepay';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install() {
		$this->load->model('extension/payment/globalpayments_googlepay');
		$this->model_extension_payment_globalpayments_googlepay->install();

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
