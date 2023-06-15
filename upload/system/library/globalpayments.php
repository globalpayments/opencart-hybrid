<?php
/**
 * Global Payments PHP Library
 */

/**
 * Autoload SDK.
 */
$autoloader = __DIR__ . '/globalpayments/autoload.php';
if (is_readable($autoloader)) {
	include_once $autoloader;
}

use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Gateways\GpApiGateway;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets\ClickToPay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets\ApplePay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets\GooglePay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Affirm;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Klarna;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Clearpay;

class GlobalPayments {
	/**
	 * Extension version.
	 */
	const VERSION = '1.4.0';

	/**
	 * @var GlobalPayments\PaymentGatewayProvider\Gateways\GatewayInterface
	 */
	public $gateway;

	public $paymentMethod;

	protected $registry;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($key) {
		return $this->registry->get($key);
	}

	public function setGateway($gatewayId) {
		switch ($gatewayId) {
			case GatewayId::GP_API:
				$this->setGpApiGateway();
				break;
		}
	}

	public function setPaymentMethod($paymentMethodId) {
		$this->setGpApiGateway(true);
		switch($paymentMethodId) {
			case ClickToPay::PAYMENT_METHOD_ID:
				$this->setClickToPayPaymentMethod();
				break;
			case ApplePay::PAYMENT_METHOD_ID:
				$this->setApplePayPaymentMethod();
				break;
			case GooglePay::PAYMENT_METHOD_ID:
				$this->setGooglePayPaymentMethod();
				break;
			case Affirm::PAYMENT_METHOD_ID:
				$this->setAffirmPaymentMethod();
				break;
			case Klarna::PAYMENT_METHOD_ID:
				$this->setKlarnaPaymentMethod();
				break;
			case Clearpay::PAYMENT_METHOD_ID:
				$this->setClearpayPaymentMethod();
				break;
		}
	}


	public function setGpApiGateway($config_only = false) {
		$this->gateway = new GpApiGateway();

		/**
		 * All these settings should be provided through the Admin Dashboard.
		 */
		$this->gateway->enabled            = $this->config->get('payment_globalpayments_ucp_enabled');
		$this->gateway->title              = $this->config->get('payment_globalpayments_ucp_title');
		$this->gateway->isProduction       = $this->config->get('payment_globalpayments_ucp_is_production');
		$this->gateway->appId              = $this->config->get('payment_globalpayments_ucp_app_id');
		$this->gateway->appKey             = $this->config->get('payment_globalpayments_ucp_app_key');
		$this->gateway->sandboxAppId       = $this->config->get('payment_globalpayments_ucp_sandbox_app_id');
		$this->gateway->sandboxAppKey      = $this->config->get('payment_globalpayments_ucp_sandbox_app_key');
		$this->gateway->debug              = $this->config->get('payment_globalpayments_ucp_debug');
		$this->gateway->merchantContactUrl = $this->config->get('payment_globalpayments_ucp_contact_url');
		$this->gateway->paymentAction      = $this->config->get('payment_globalpayments_ucp_payment_action');
		$this->gateway->allowCardSaving    = $this->config->get('payment_globalpayments_ucp_allow_card_saving');
		$this->gateway->txnDescriptor      = $this->config->get('payment_globalpayments_ucp_txn_descriptor');
		$this->gateway->baseUrl            = $this->url->link('extension/payment/', '', true);

		$this->load->model('localisation/country');
		$store_country_id = $this->config->get('config_country_id');
		$store_country    = $this->model_localisation_country->getCountry($store_country_id);
		$this->gateway->country = $store_country['iso_code_2'];

		/**
		 * All these settings should be platform specific.
		 */
		$this->gateway->dynamicHeaders = [
			'x-gp-platform'  => 'opencart;version=' . VERSION,
			'x-gp-extension' => 'globalpayments-opencart;version=' . self::VERSION,
		];
		$this->gateway->logDirectory = DIR_LOGS;

		if ($config_only) {
			return;
		}

		$this->gateway->checkEnrollmentUrl        = $this->url->link('extension/payment/globalpayments_ucp/threeDSecureCheckEnrollment', '', true);
		$this->gateway->methodNotificationUrl     = $this->url->link('extension/payment/globalpayments_ucp/threeDSecureMethodNotification', '', true);
		$this->gateway->initiateAuthenticationUrl = $this->url->link('extension/payment/globalpayments_ucp/threeDSecureInitiateAuthentication', '', true);
		$this->gateway->challengeNotificationUrl  = $this->url->link('extension/payment/globalpayments_ucp/threeDSecureChallengeNotification', '', true);

		$this->gateway->threeDSLibPath = 'catalog/view/javascript/globalpayments-3ds.min.js';

		$this->load->language('extension/payment/globalpayments_ucp');
		$this->gateway->errorTransactionStatusDeclined    = $this->language->get('error_txn_declined');
		$this->gateway->errorGatewayResponse              = $this->language->get('error_txn_error');
		$this->gateway->errorThreeDSecure                 = $this->language->get('error_threedsecure');
		$this->gateway->errorThreeDSecureNoLiabilityShift = $this->language->get('error_threedsecure_no_liability');

		$this->load->language('extension/credit_card/globalpayments_ucp');
		$this->gateway->errorVerifyNotVerified            = $this->language->get('error_txn_not_verified');
	}

	public function setGooglePayPaymentMethod() {
		$this->paymentMethod                           = new GooglePay($this->gateway);
		$this->paymentMethod->enabled                  = $this->config->get('payment_globalpayments_googlepay_enabled');
		$this->paymentMethod->title                    = $this->config->get('payment_globalpayments_googlepay_title');
		$this->paymentMethod->paymentAction            = $this->config->get('payment_globalpayments_googlepay_payment_action');
		$this->paymentMethod->ccTypes                  = explode(',', $this->config->get('payment_globalpayments_googlepay_accepted_cards'));
		$this->paymentMethod->globalPaymentsMerchantId = $this->config->get('payment_globalpayments_googlepay_gp_merchant_id');
		$this->paymentMethod->googleMerchantId         = $this->config->get('payment_globalpayments_googlepay_merchant_id');
		$this->paymentMethod->googleMerchantName       = $this->config->get('payment_globalpayments_googlepay_merchant_name');
		$this->paymentMethod->buttonColor              = $this->config->get('payment_globalpayments_googlepay_button_color');
	}

	public function setApplePayPaymentMethod() {
		$this->paymentMethod                             = new ApplePay($this->gateway);
		$this->paymentMethod->enabled                    = $this->config->get('payment_globalpayments_applepay_enabled');
		$this->paymentMethod->title                      = $this->config->get('payment_globalpayments_applepay_title');
		$this->paymentMethod->paymentAction              = $this->config->get('payment_globalpayments_applepay_payment_action');
		$this->paymentMethod->appleMerchantId            = $this->config->get('payment_globalpayments_applepay_apple_merchant_id');
		$this->paymentMethod->appleMerchantCertPath      = DIR_STORAGE . $this->config->get('payment_globalpayments_applepay_apple_merchant_cert_path');
		$this->paymentMethod->appleMerchantKeyPath       = DIR_STORAGE . $this->config->get('payment_globalpayments_applepay_apple_merchant_key_path');
		$this->paymentMethod->appleMerchantKeyPassphrase = $this->config->get('payment_globalpayments_applepay_apple_merchant_key_passphrase');
		$this->paymentMethod->appleMerchantDomain        = $this->config->get('payment_globalpayments_applepay_apple_merchant_domain');
		$this->paymentMethod->appleMerchantDisplayName   = $this->config->get('payment_globalpayments_applepay_apple_merchant_display_name');
		$this->paymentMethod->ccTypes                    = explode(',', $this->config->get('payment_globalpayments_applepay_accepted_cards'));
		$this->paymentMethod->buttonColor                = $this->config->get('payment_globalpayments_applepay_button_color');
		/**
		 * All these settings should be platform specific.
		 */
		$this->paymentMethod->validateMerchantUrl = $this->url->link('extension/payment/globalpayments_applepay/validateMerchant', '', true);
		$this->paymentMethod->country             = $this->gateway->country;;
	}

	public function setClickToPayPaymentMethod() {
		$this->paymentMethod                = new ClickToPay($this->gateway);
		$this->paymentMethod->enabled       = $this->config->get('payment_globalpayments_clicktopay_enabled');
		$this->paymentMethod->title         = $this->config->get('payment_globalpayments_clicktopay_title');
		$this->paymentMethod->paymentAction = $this->config->get('payment_globalpayments_clicktopay_payment_action');
		$this->paymentMethod->ctpClientId   = $this->config->get('payment_globalpayments_clicktopay_ctp_client_id');
		$this->paymentMethod->buttonless    = (bool) $this->config->get('payment_globalpayments_clicktopay_buttonless');
		$this->paymentMethod->ccTypes       = $this->config->get('payment_globalpayments_clicktopay_accepted_cards');
		$this->paymentMethod->canadianDebit = (bool) $this->config->get('payment_globalpayments_clicktopay_canadian_debit');
		$this->paymentMethod->wrapper       = (bool) $this->config->get('payment_globalpayments_clicktopay_wrapper');
	}

	public function setAffirmPaymentMethod() {
		$this->paymentMethod                = new Affirm($this->gateway);
		$this->paymentMethod->enabled       = $this->config->get('payment_globalpayments_affirm_enabled');
		$this->paymentMethod->title         = $this->config->get('payment_globalpayments_affirm_title');
		$this->paymentMethod->paymentAction = $this->config->get('payment_globalpayments_affirm_payment_action');
	}

	public function setKlarnaPaymentMethod() {
		$this->paymentMethod                = new Klarna($this->gateway);
		$this->paymentMethod->enabled       = $this->config->get('payment_globalpayments_klarna_enabled');
		$this->paymentMethod->title         = $this->config->get('payment_globalpayments_klarna_title');
		$this->paymentMethod->paymentAction = $this->config->get('payment_globalpayments_klarna_payment_action');
	}

	public function setClearpayPaymentMethod() {
		$this->paymentMethod                = new Clearpay($this->gateway);
		$this->paymentMethod->enabled       = $this->config->get('payment_globalpayments_clearpay_enabled');
		$this->paymentMethod->title         = $this->config->get('payment_globalpayments_clearpay_title');
		$this->paymentMethod->paymentAction = $this->config->get('payment_globalpayments_clearpay_payment_action');
	}

	public function setSecurePaymentFieldsTranslations() {
		$this->load->language('extension/payment/globalpayments_ucp');
		$this->gateway->textSandboxWarning      = $this->language->get('text_sandbox_warning');
		$this->gateway->textCardNumberLabel     = $this->language->get('entry_cc_number');
		$this->gateway->errorCardNumber         = $this->language->get('error_cc_number');
		$this->gateway->textCardExpirationLabel = $this->language->get('entry_cc_exp_date');
		$this->gateway->errorCardExpiration     = $this->language->get('error_cc_exp_date');
		$this->gateway->textCardCvvLabel        = $this->language->get('entry_cc_cvv');
		$this->gateway->errorCardCvv            = $this->language->get('error_cc_cvv');
		$this->gateway->textCardHolderLabel     = $this->language->get('entry_cc_card_holder');
		$this->gateway->errorCardHolder         = $this->language->get('error_cc_card_holder');
	}

	/**
	 * CSS styles for secure payment fields.
	 *
	 * @return mixed|void
	 */
	public function setSecurePaymentFieldsStyles() {
		$securePaymentFieldsStyles = json_decode($this->gateway->securePaymentFieldsStyles(), true);

		$securePaymentFieldsStyles['button#secure-payment-field.submit']                = array(
			'padding'            => '7.5px 12px;',
			'font-size'          => '12px;',
			'border'             => '1px solid #cccccc;',
			'border-radius'      => '4px',
			'box-shadow'         => 'inset 0 1px 0 rgba(255,255,255,.2), 0 1px 2px rgba(0,0,0,.05)',
			'color'              => '#ffffff',
			'text-shadow'        => '0 -1px 0 rgba(0, 0, 0, 0.25)',
			'background-color'   => '#229ac8',
			'background-image'   => 'linear-gradient(to bottom, #23a1d1, #1f90bb)',
			'background-repeat'  => 'repeat-x',
			'border-color'       => '#1f90bb #1f90bb #145e7a',
			'cursor'             => 'pointer',
			'-webkit-appearance' => 'button',
			'font-family'        => "'Open Sans', sans-serif",
			'font-weight'        => '400',
			'line-height'        => '20px',
			'width'              => '100%',
		);
		$securePaymentFieldsStyles['button#secure-payment-field.submit:disabled']       = array(
			'background-color'    => '#1f90bb',
			'background-position' => '0 -15px',
		);
		$securePaymentFieldsStyles['#secure-payment-field[type=button]:focus']          = array(
			'background-color'    => '#1f90bb',
			'background-position' => '0 -15px',
		);
		$securePaymentFieldsStyles['#secure-payment-field[type=button]:hover']          = array(
			'background-color'    => '#1f90bb',
			'background-position' => '0 -15px',
		);
		$securePaymentFieldsStyles['#secure-payment-field[type=button]:disabled:focus'] = array(
			'background-color'    => '#1f90bb',
			'background-position' => '0 -15px',
		);
		$securePaymentFieldsStyles['#secure-payment-field[type=button]:disabled:hover'] = array(
			'background-color'    => '#1f90bb',
			'background-position' => '0 -15px',
		);

		$this->gateway->setSecurePaymentFieldsStyles($securePaymentFieldsStyles);
	}
}
