<?php

use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\PaymentMethodName;
use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Gateways\GpApiGateway;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\Apm\Paypal;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\OpenBanking\OpenBanking;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Affirm;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Clearpay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Klarna;
use GlobalPayments\PaymentGatewayProvider\Requests\AccessToken\GetAccessTokenRequest;

class ControllerExtensionPaymentGlobalPaymentsUcp extends Controller {
	private $error = array();
	private $alert = array();

	public function index() {
		$data = [];
		$this->load->language('extension/payment/globalpayments_ucp');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');

		//If needed fix missing payment actions
		$this->load->model('extension/payment/globalpayments_ucp');
		$this->model_extension_payment_globalpayments_ucp->fixColumns();

		$globalpayments_googlepay_installed      = in_array('globalpayments_googlepay', $extensions);
		$globalpayments_applepay_installed       = in_array('globalpayments_applepay', $extensions);
		$globalpayments_clicktopay_installed     = in_array('globalpayments_clicktopay', $extensions);
		$globalpayments_affirm_installed         = in_array('globalpayments_affirm', $extensions);
		$globalpayments_klarna_installed         = in_array('globalpayments_klarna', $extensions);
		$globalpayments_clearpay_installed       = in_array('globalpayments_clearpay', $extensions);
		$globalpayments_openbanking_installed    = in_array('globalpayments_openbanking', $extensions);
		$globalpayments_paypal_installed         = in_array('globalpayments_paypal', $extensions);

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

			// Click To Pay
			if ($globalpayments_clicktopay_installed) {
				$globalpayments_clicktopay_messages = $this->load->controller('extension/payment/globalpayments_clicktopay/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_clicktopay_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_clicktopay_messages['error']);
			}

			//Affirm
			if ($globalpayments_affirm_installed) {
				$globalpayments_affirm_messages = $this->load->controller('extension/payment/globalpayments_affirm/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_affirm_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_affirm_messages['error']);
			}

			//Klarna
			if ($globalpayments_klarna_installed) {
				$globalpayments_klarna_messages = $this->load->controller('extension/payment/globalpayments_klarna/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_klarna_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_klarna_messages['error']);
			}

			//Clearpay
			if ($globalpayments_clearpay_installed) {
				$globalpayments_clearpay_messages = $this->load->controller('extension/payment/globalpayments_clearpay/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_clearpay_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_clearpay_messages['error']);
			}

			//OpenBanking
			if ($globalpayments_openbanking_installed) {
				$globalpayments_openbanking_messages = $this->load->controller('extension/payment/globalpayments_openbanking/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_openbanking_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_openbanking_messages['error']);
			}

			//Paypal
			if ($globalpayments_paypal_installed) {
				$globalpayments_paypal_messages = $this->load->controller('extension/payment/globalpayments_paypal/save');
				$this->alert = array_merge_recursive($this->alert, $globalpayments_paypal_messages['alert']);
				$this->error = array_merge_recursive($this->error, $globalpayments_paypal_messages['error']);
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
		if (isset($this->error['error_live_credentials_account_name'])) {
			$data['error_live_credentials_account_name'] = $this->error['error_live_credentials_account_name'];
		} else {
			$data['error_live_credentials_account_name'] = '';
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
		if (isset($this->error['error_sandbox_credentials_account_name'])) {
			$data['error_sandbox_credentials_account_name'] = $this->error['error_sandbox_credentials_account_name'];
		} else {
			$data['error_sandbox_credentials_account_name'] = '';
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
		if (isset($this->request->post['payment_globalpayments_ucp_enabled_blik'])) {
			$data['payment_globalpayments_ucp_enabled_blik'] = $this->request->post['payment_globalpayments_ucp_enabled_blik'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_enabled_blik'] = 0;
		} else {
			$data['payment_globalpayments_ucp_enabled_blik'] = $this->config->get('payment_globalpayments_ucp_enabled_blik');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_enabled_openbanking'])) {
			$data['payment_globalpayments_ucp_enabled_openbanking'] = $this->request->post['payment_globalpayments_ucp_enabled_openbanking'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_enabled_openbanking'] = 0;
		} else {
			$data['payment_globalpayments_ucp_enabled_openbanking'] = $this->config->get('payment_globalpayments_ucp_enabled_openbanking');
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
		if (isset($this->request->post['payment_globalpayments_ucp_account_name'])) {
			$data['payment_globalpayments_ucp_account_name'] = $this->request->post['payment_globalpayments_ucp_account_name'];
		} else {
			$data['payment_globalpayments_ucp_account_name'] = $this->config->get('payment_globalpayments_ucp_account_name');
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
		if (isset($this->request->post['payment_globalpayments_ucp_sandbox_account_name'])) {
			$data['payment_globalpayments_ucp_sandbox_account_name'] = $this->request->post['payment_globalpayments_ucp_sandbox_account_name'];
		} else {
			$data['payment_globalpayments_ucp_sandbox_account_name'] = $this->config->get('payment_globalpayments_ucp_sandbox_account_name');
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
		if (isset($this->request->post['payment_globalpayments_ucp_enable_three_d_secure'])) {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = $this->request->post['payment_globalpayments_ucp_enable_three_d_secure'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = 0;
		} else {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = $this->config->get('payment_globalpayments_ucp_enable_three_d_secure');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_sort_order'])) {
			$data['payment_globalpayments_ucp_sort_order'] = $this->request->post['payment_globalpayments_ucp_sort_order'];
		} else {
			$data['payment_globalpayments_ucp_sort_order'] = $this->config->get('payment_globalpayments_ucp_sort_order') ?? 0;
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
		if (isset($this->request->post['payment_globalpayments_ucp_enable_three_d_secure'])) {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = $this->request->post['payment_globalpayments_ucp_enable_three_d_secure'];
		} else {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = $this->config->get('payment_globalpayments_ucp_enable_three_d_secure');
		}
		if (isset($this->request->post['payment_globalpayments_ucp_enable_three_d_secure'])) {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = $this->request->post['payment_globalpayments_ucp_enable_three_d_secure'];
		} elseif (!empty($this->request->post)) {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = 1;
		} else {
			$data['payment_globalpayments_ucp_enable_three_d_secure'] = $this->config->get('payment_globalpayments_ucp_enable_three_d_secure');
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

		if ($globalpayments_clicktopay_installed) {
			$data['display_clicktopay_tab'] = $this->load->controller('extension/payment/globalpayments_clicktopay/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'clicktopay',
				'name' => $this->language->get('tab_clicktopay'),
			);
		} else {
			$data['display_clicktopay_tab'] = '';
		}

		if ($globalpayments_affirm_installed) {
			$data['display_affirm_tab'] = $this->load->controller('extension/payment/globalpayments_affirm/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'affirm',
				'name' => $this->language->get('tab_affirm'),
			);
		} else {
			$data['display_affirm_tab'] = '';
		}

		if ($globalpayments_klarna_installed) {
			$data['display_klarna_tab'] = $this->load->controller('extension/payment/globalpayments_klarna/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'klarna',
				'name' => $this->language->get('tab_klarna'),
			);
		} else {
			$data['display_klarna_tab'] = '';
		}

		if ($globalpayments_clearpay_installed) {
			$data['display_clearpay_tab'] = $this->load->controller('extension/payment/globalpayments_clearpay/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'clearpay',
				'name' => $this->language->get('tab_clearpay'),
			);
		} else {
			$data['display_clearpay_tab'] = '';
		}

		if ($globalpayments_openbanking_installed) {
			$data['display_openbanking_tab'] = $this->load->controller('extension/payment/globalpayments_openbanking/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'openbanking',
				'name' => $this->language->get('tab_openbanking'),
			);
		} else {
			$data['display_openbanking_tab'] = '';
		}

		if ($globalpayments_paypal_installed) {
			$data['display_paypal_tab'] = $this->load->controller('extension/payment/globalpayments_paypal/display', $this->error);
			$data['tabs'][] = array(
				'id'   => 'paypal',
				'name' => $this->language->get('tab_paypal'),
			);
		} else {
			$data['display_paypal_tab'] = '';
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
		$data['user_token']  = $this->session->data['user_token'];

		// Add base country and currency for BLIK and Open Banking conditions
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting('config');
		$data['base_country'] = isset($settings['config_country_id']) ? $this->getCountryIsoCode($settings['config_country_id']) : '';
		$data['base_currency'] = $this->config->get('config_currency');

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_ucp', $data));
	}

	/**
	 * Get country ISO code from country ID
	 *
	 * @param int $country_id
	 * @return string
	 */
	private function getCountryIsoCode($country_id) {
		if (empty($country_id)) {
			return '';
		}

		$this->load->model('localisation/country');
		$country = $this->model_localisation_country->getCountry($country_id);

		return isset($country['iso_code_2']) ? $country['iso_code_2'] : '';
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
			if (empty($this->request->post['payment_globalpayments_ucp_account_name'])) {
				$this->error['error_live_credentials_account_name'] = $this->language->get('error_live_credentials_account_name');
			}
		} else {
			if (empty($this->request->post['payment_globalpayments_ucp_sandbox_app_id'])) {
				$this->error['error_sandbox_credentials_app_id'] = $this->language->get('error_sandbox_credentials_app_id');
			}
			if (empty($this->request->post['payment_globalpayments_ucp_sandbox_app_key'])) {
				$this->error['error_sandbox_credentials_app_key'] = $this->language->get('error_sandbox_credentials_app_key');
			}
			if (empty($this->request->post['payment_globalpayments_ucp_sandbox_account_name'])) {
				$this->error['error_sandbox_credentials_account_name'] = $this->language->get('error_sandbox_credentials_account_name');
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

		$data['user_token']   = $this->session->data['user_token'];
		$data['order_id']     = (int)$this->request->get['order_id'];
		$data['payment_code'] = 'globalpayments_ucp';

		return $this->load->view('extension/payment/globalpayments_ucp_order', $data);
	}

	public function getTransaction() {
		if ( ! isset($this->request->get['order_id'])) {
			return;
		}

		$this->load->library('globalpayments');

		$this->load->language('extension/payment/globalpayments_ucp_order');

		$data['user_token']   = $this->session->data['user_token'];
		$data['order_id']     = (int)$this->request->get['order_id'];
		$data['payment_code'] = $this->request->get['payment_code'];

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
			$paymentMethods = [
				Affirm::PAYMENT_METHOD_ID,
				Clearpay::PAYMENT_METHOD_ID,
				Klarna::PAYMENT_METHOD_ID,
				OpenBanking::PAYMENT_METHOD_ID,
				Paypal::PAYMENT_METHOD_ID,
			];
			// we handle the code like this because only for the above payment methods we add a transaction entry on the INITIALIZE step
			if (in_array($data['payment_code'], $paymentMethods)) {
				$transaction_actions = $this->handleInitializedTransactions($data['order_id'], $transaction, $transactions_count, $should_refund);
			} else {
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
			}

			$transactions[$key]['transaction_actions'] = $transaction_actions;
			$transactions[$key]['time_created']        = date($this->language->get('datetime_format'), strtotime($transaction['time_created']));
		}

		$data['transactions'] = $transactions;

		if ($data['payment_code'] === 'globalpayments_clicktopay') {
			$this->load->model('setting/extension');
			$extensions = $this->model_setting_extension->getInstalled('payment');
			if (in_array('globalpayments_clicktopay', $extensions)) {
				$this->load->model('extension/payment/globalpayments_clicktopay');
				$order_meta = $this->model_extension_payment_globalpayments_clicktopay->getOrderMeta($data['order_id']);
				if ($order_meta) {
					$dw_billing_address = json_decode($order_meta['payment_address']);
					$data_dw_billing_address  = $dw_billing_address->streetAddress1 . ( ! empty( $dw_billing_address->streetAddress2 ) ? ', ' . $dw_billing_address->streetAddress2 : '' ) . "</br>";
					$data_dw_billing_address .= ( ! empty( $dw_billing_address->streetAddress2 ) ? $dw_billing_address->streetAddress3 . '</br>' : '' );
					$data_dw_billing_address .= $dw_billing_address->city . ', ' . $dw_billing_address->state . ' ' . $dw_billing_address->postalCode;

					$dw_shipping_address = json_decode($order_meta['shipping_address']);
					$data_dw_shipping_address  = $dw_shipping_address->streetAddress1 . ( ! empty( $dw_shipping_address->streetAddress2 ) ? ', ' . $dw_shipping_address->streetAddress2 : '' ) . '</br>';
					$data_dw_shipping_address .= ( ! empty( $dw_shipping_address->streetAddress2 ) ? $dw_shipping_address->streetAddress3 . '</br>' : '' );
					$data_dw_shipping_address .= $dw_shipping_address->city . ', ' . $dw_shipping_address->state . ' ' . $dw_shipping_address->postalCode;

					$data['dw_billing_address']  = $data_dw_billing_address;
					$data['dw_shipping_address'] = $data_dw_shipping_address;
					$data['dw_email']            = $order_meta['email'];
					$data['dw_payer']            = $order_meta['payer'];
				}
			}
		}

		$this->response->setOutput($this->load->view('extension/payment/globalpayments_ucp_order_ajax', $data));
	}

	public function checkApiCredentials() {
		$response = [];

		if (!isset($this->request->post['app_id']) || !isset($this->request->post['app_key'])) {
			$response['error'] = $this->language->get('error_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response));

			return;
		}

		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(GatewayId::GP_API);

		$environmentValue = (bool)$this->request->post['environment'];
		$environment = Environment::TEST;
		if ($environmentValue) {
			$environment = Environment::PRODUCTION;
		}

		$ajaxData['appId'] = $this->request->post['app_id'];
		$ajaxData['appKey'] = $this->request->post['app_key'];
		$ajaxData['environment'] = $environment;

		$accountNames = [];
		try {
			$gatewayResponse = $this->globalpayments->gateway->processRequest(GetAccessTokenRequest::class, null, $ajaxData);
			foreach($gatewayResponse->accounts as $value){
				$accountNames[] = $value->name;
			}
			if (!empty($gatewayResponse->token)) {
				$response['success'] = $this->language->get('success_credentials_check');
				unset($response['error']);
			} else {
				$response['error'] = $this->language->get('error_request');
			}
		} catch (\Exception $e) {
			unset($response['success']);
			$response['error'] = $this->language->get('error_request') . ' ' . $e->getMessage();
		}
		$response['accountNames'] = $accountNames;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function transactionCommand() {
		$response = [];

		$this->load->language('extension/payment/globalpayments_ucp_order');

		if ( ! isset($this->request->post['order_id'])
			|| ! isset($this->request->post['gateway_id'])
			|| ! isset($this->request->post['transaction_id'])
			|| ! isset($this->request->post['transaction_type'])
			|| ! isset($this->request->post['transaction_amount'])
			|| ! isset($this->request->post['currency'])
		) {
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
		$requestData->gatewayId       = $this->request->post['gateway_id'];

		try {
			switch ($this->request->post['transaction_type']) {
				case AbstractGateway::CAPTURE:
					$gatewayResponse     = $this->globalpayments->gateway->processCapture($requestData);
					$response['success'] = $this->language->get('text_success_capture');
					break;
				case AbstractGateway::REFUND:
					$gatewayResponse     = $this->globalpayments->gateway->processRefund($requestData);
					// Check if this is a complete refund to update order status
					// Get the original order total to compare with refund amount
					$this->load->model('sale/order');
					$orderInfo = $this->model_sale_order->getOrder($this->request->post['order_id']);
                    $orderTotal = $this->currency->format($orderInfo['total'], $orderInfo['currency_code'], $orderInfo['currency_value'], false);
					$isCompleteRefund = false;
                    $this->load->model('extension/payment/globalpayments_ucp');
                    $transactions = $this->model_extension_payment_globalpayments_ucp->getTransactions($this->request->post['order_id']);
                    $totalamountrefunded = 0;
                    foreach ($transactions as $transaction) {
                        if ($transaction['payment_action'] === 'refund') {
                            $totalamountrefunded += (float) $transaction['amount'];
                        }
                    }
                    $totalamountrefunded += (float) $amount;
                    if ($orderInfo) {
						$isCompleteRefund = (float) $totalamountrefunded >= (float) $orderTotal;
					}

					if ($isCompleteRefund) {
						// Update order status to 11 (Refunded) for complete refunds
						$this->completeStatusUpdate((int) $this->request->post['order_id']);
						$response['success'] = $this->language->get('text_success_full_refund');
					} else {
                        $this->orderHistoryUpdate((int) $this->request->post['order_id']);
						$response['success'] = $this->language->get('text_success_partial_refund');

					}
					break;
				case AbstractGateway::REVERSE:
					$gatewayResponse     = $this->globalpayments->gateway->processReverse($requestData);
					// Update order status to 11 (Refunded) for complete refunds
					$this->completeStatusUpdate((int) $this->request->post['order_id']);
					$response['success'] = $this->language->get('text_success_reverse');
					break;
				case AbstractGateway::GET_TRANSACTIONS_DETAILS:
					$this->getInitializedTransactionDetails($requestData);
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

	private function orderHistoryUpdate($orderId): void
	{
		$orderStatusId = 2; // processing
		$comment = "Partially refund done";
		$notify = 1; // true

		// Add order history entry
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$orderId . "', order_status_id = '" . (int)$orderStatusId . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
	}

	private function completeStatusUpdate($orderId): void
	{
		$orderStatusId = 11; // Refunded status
		$comment = $this->language->get('text_refunded_comment');
		$notify = 1; // true

		// Update order status
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$orderStatusId . "', date_modified = NOW() WHERE order_id = '" . (int)$orderId . "'");

		// Add order history entry
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$orderId . "', order_status_id = '" . (int)$orderStatusId . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
	}

	private function handleInitializedTransactions($orderId, $transaction, $transactions_count, $should_refund) {
		$transaction_actions = [];
		if ($transactions_count === 1 && $transaction['payment_action'] === AbstractGateway::INITIATE) {
			if ($this->user->isLogged() && $this->user->getGroupId() == 1) {
				$this->load->model('sale/order');
				$order_status_info = $this->model_sale_order->getOrder($orderId);
				$order_status = $order_status_info['order_status'];
				if ($order_status == 'Pending') {
					$this->cache->delete('get_initiated_transaction_details_response');
					$transaction_actions[] = [
						'action' => AbstractGateway::GET_TRANSACTIONS_DETAILS,
						'button' => $this->language->get('button_getTransactionDetails'),
					];
				}
			}
		}
		if ($transactions_count === 2 && $transaction['payment_action'] === AbstractGateway::AUTHORIZE) {
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
		if (($transactions_count === 3 && $transaction['payment_action'] === AbstractGateway::CAPTURE)
			|| ($transactions_count === 2 && ($transaction['payment_action'] === AbstractGateway::CHARGE || $transaction['payment_action'] === AbstractGateway::AUTHORIZE))) {
				$transaction_actions[] = [
					'action' => AbstractGateway::REVERSE,
					'button' => $this->language->get('button_reverse'),
				];
		}

		return $transaction_actions;
	}

	private function getInitializedTransactionDetails($requestData) {
		if ($this->cache->get('get_initiated_transaction_details_response')) {
			$response = $this->cache->get('get_initiated_transaction_details_response');
		} else {
			$gatewayResponse = $this->globalpayments->gateway->getTransactionDetails($requestData);
			$details = sprintf(
				"Transaction Id: %s
				Transaction Status: %s
				Transaction Type: %s
				Amount: %s
				Currency: %s",
				$gatewayResponse->transactionId,
				$gatewayResponse->transactionStatus,
				$gatewayResponse->transactionType,
				$gatewayResponse->amount,
				$gatewayResponse->currency,
			);

			if (!empty($gatewayResponse->bnplResponse)) {
				$details .= sprintf("
					Provider: %s
					Provider type: %s",
					PaymentMethodName::BNPL,
					$gatewayResponse->bnplResponse->providerName
				);
			} elseif ($gatewayResponse->bankPaymentResponse) {
				$details .= sprintf("
					Payment type: %s",
					$gatewayResponse->paymentType //PAYMENT TYPE
				);
			} elseif ($gatewayResponse->alternativePaymentResponse) {
				$details .= sprintf("
					Provider: %s
					Provider type: %s",
					PaymentMethodName::APM,
					strtoupper($gatewayResponse->alternativePaymentResponse->providerName)
				);
			}

			$response['getTransactionDetails'] = nl2br($details);
			$response['success'] = $this->language->get('text_success_getTransactionDetails');
			$this->cache->set('get_initiated_transaction_details_response', $response);
		}

		AbstractRequest::sendJsonResponse($response);
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
		$this->model_setting_extension->install('payment', 'globalpayments_clicktopay');
		$this->model_setting_extension->install('payment', 'globalpayments_affirm');
		$this->model_setting_extension->install('payment', 'globalpayments_klarna');
		$this->model_setting_extension->install('payment', 'globalpayments_clearpay');
		$this->model_setting_extension->install('payment', 'globalpayments_openbanking');
		$this->model_setting_extension->install('payment', 'globalpayments_paypal');

		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_googlepay');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_googlepay');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_applepay');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_applepay');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_clicktopay');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_clicktopay');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_affirm');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_affirm');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_klarna');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_klarna');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_clearpay');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_clearpay');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_openbanking');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_openbanking');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/globalpayments_paypal');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/globalpayments_paypal');

		$this->load->model('extension/payment/globalpayments_ucp');
		$this->model_extension_payment_globalpayments_ucp->install();
		$this->load->model('extension/payment/globalpayments_googlepay');
		$this->model_extension_payment_globalpayments_googlepay->install();
		$this->load->model('extension/payment/globalpayments_applepay');
		$this->model_extension_payment_globalpayments_applepay->install();
		$this->load->model('extension/payment/globalpayments_clicktopay');
		$this->model_extension_payment_globalpayments_clicktopay->install();
		$this->load->model('extension/payment/globalpayments_affirm');
		$this->model_extension_payment_globalpayments_affirm->install();
		$this->load->model('extension/payment/globalpayments_klarna');
		$this->model_extension_payment_globalpayments_klarna->install();
		$this->load->model('extension/payment/globalpayments_clearpay');
		$this->model_extension_payment_globalpayments_clearpay->install();
		$this->load->model('extension/payment/globalpayments_openbanking');
		$this->model_extension_payment_globalpayments_openbanking->install();

		$this->load->model('extension/payment/globalpayments_paypal');
		$this->model_extension_payment_globalpayments_paypal->install();

	}

	public function uninstall() {
		$this->load->model('setting/extension');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_googlepay');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_applepay');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_clicktopay');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_affirm');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_klarna');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_clearpay');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_openbanking');
		$this->model_setting_extension->uninstall('payment', 'globalpayments_paypal');

		$this->load->model('extension/payment/globalpayments_ucp');
		$this->model_extension_payment_globalpayments_ucp->uninstall();
	}
}
