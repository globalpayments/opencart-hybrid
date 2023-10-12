<?php

class ControllerExtensionPaymentGlobalPaymentsTxnApi extends Controller
{
	const SETTING_CODE = 'payment_globalpayments_txnapi';

	const FIELD_NAMES = ['status', 'title', 'is_production', 'region',
		'public_key', 'sandbox_public_key',
		'api_key', 'sandbox_api_key',
		'api_secret', 'sandbox_api_secret',
		'account_credential', 'sandbox_account_credential',
		'debug', 'payment_action', 'card'];

	const MANDATORY_FIELDS = ['public_key', 'api_key', 'api_secret', 'account_credential'];

	private $error = array();
	private $alert = array();

	public function index()
	{
		$this->load->controller('extension/payment/globalpayments_gpitrans');
	}

	public function display($error)
	{
		$this->load->language('extension/payment/globalpayments_txnapi');

		$data = [];
		$this->fillErrors($data, $error);
		foreach (self::FIELD_NAMES as $fieldName) {
			$this->fillField($data, $fieldName);
		}
		$data['active_tab'] = str_replace(
			'extension/payment/globalpayments_',
			'',
			$this->request->get['route']
		);

		return $this->load->view('extension/payment/globalpayments_txnapi', $data);
	}

	private function fillErrors(&$data, $error)
	{
		foreach (self::MANDATORY_FIELDS as $fieldName) {
			$errorLiveName = 'error_live_credentials_txnapi_' . $fieldName;
			$errorSandboxName = 'error_sandbox_credentials_txnapi_' . $fieldName;
			$this->fillAnError($data, $error, $errorLiveName);
			$this->fillAnError($data, $error, $errorSandboxName);
		}
	}

	private function fillAnError(&$data, $error, $errorName)
	{
		if (isset($error[$errorName])) {
			$data[$errorName] = $error[$errorName];
		} else {
			$data[$errorName] = '';
		}
	}

	private function fillField(&$data, $fieldName)
	{
		$fullName = self::SETTING_CODE . '_' . $fieldName;
		if (isset($this->request->post[$fullName])) {
			$data[$fullName] = $this->request->post[$fullName];
		} else {
			$data[$fullName] = $this->config->get($fullName);
		}
	}

	public function validate()
	{
		$this->load->language('extension/payment/globalpayments_txnapi');
		if (!$this->user->hasPermission('modify', 'extension/payment/globalpayments_txnapi')) {
			$this->alert[] = array(
				'type' => 'danger',
				'message' => $this->language->get('error_permission'),
			);
			return false;
		}
		if (empty($this->request->post['payment_globalpayments_txnapi_status'])) {
			$this->alert[] = array(
				'type' => 'success',
				'message' => $this->language->get('success_settings_txnapi'),
			);
			return true;
		}
		$isProduction = !empty($this->request->post['payment_globalpayments_txnapi_is_production']);
		foreach (self::MANDATORY_FIELDS as $fieldName) {
			$this->checkMandatoryField($isProduction, $fieldName);
		}
		if ($this->error) {
			$this->alert[] = array(
				'type' => 'danger',
				'message' => $this->language->get('error_settings_txnapi'),
			);
		} else {
			$this->alert[] = array(
				'type' => 'success',
				'message' => $this->language->get('success_settings_txnapi'),
			);
		}

		return !$this->error;
	}

	private function checkMandatoryField($isProduction, $fieldName)
	{
		$fullName = self::SETTING_CODE . '_' . $fieldName;
		$fullSandboxName = self::SETTING_CODE . '_sandbox_' . $fieldName;
		$errorLiveName = 'error_live_credentials_txnapi_' . $fieldName;
		$errorSandboxName = 'error_sandbox_credentials_txnapi_' . $fieldName;
		if ($isProduction) {
			if (empty($this->request->post[$fullName])) {
				$this->error[$errorLiveName] = $this->language->get($errorLiveName);
			}
		} else {
			if (empty($this->request->post[$fullSandboxName])) {
				$this->error[$errorSandboxName] = $this->language->get($errorSandboxName);
			}
		}
	}

	public function save()
	{
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}
		$this->load->model('setting/setting');
		if ($this->validate()) {
			$this->model_setting_setting->editSetting('payment_globalpayments_txnapi', $this->request->post);
		}
		return array(
			'error' => $this->error,
			'alert' => $this->alert,
		);
	}

	public function order()
	{
		$this->load->language('extension/payment/globalpayments_gpitrans_order');

		$data['user_token'] = $this->session->data['user_token'];

		$data['order_id'] = (int)$this->request->get['order_id'];

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function install()
	{
		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');
		$globalpayments_gpitrans_installed = in_array('globalpayments_gpitrans', $extensions);
		if ($globalpayments_gpitrans_installed) {
			return;
		}

		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_gpitrans');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_gpitrans');

		$this->load->model('extension/payment/globalpayments_gpitrans');
		$this->model_extension_payment_globalpayments_gpitrans->install();
	}
}
