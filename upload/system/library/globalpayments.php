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
use GlobalPayments\PaymentGatewayProvider\Gateways\GeniusGateway;
use GlobalPayments\PaymentGatewayProvider\Gateways\TransitGateway;
use GlobalPayments\PaymentGatewayProvider\Gateways\TransactionApiGateway;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets\ClickToPay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets\ApplePay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets\GooglePay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Affirm;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Klarna;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater\Clearpay;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\Apm\Paypal;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\OpenBanking\OpenBanking;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;

class GlobalPayments { 
	/**
	 * Extension version.
	 */
	 const VERSION = '2.3.0';

	/**
	 * GP API regions.
	 */
	const GP_API_REGION_GLOBAL = 'global';
	const GP_API_REGION_EUROPE = 'europe';

	/**
	 * @var GlobalPayments\PaymentGatewayProvider\Gateways\GatewayInterface
	 */
	public $gateway;

	public $paymentMethod;

	public $integrationType;

	public $enableThreeDSecure;

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
			case GatewayId::TRANSACTION_API:
				$this->setTransactionApiGateway();
				break;
			case GatewayId::TRANSIT:
				$this->setTransitGateway();
				break;
			case GatewayId::GENIUS:
				$this->setGeniusGateway();
				break;
		}
	}


	/**
	 * Get GP API service URLs from SDK constants.
	 *
	 * @return array
	 */
	private static function getGpApiServiceUrls(): array {
		return [
			self::GP_API_REGION_GLOBAL => [
				'production' => \GlobalPayments\Api\Entities\Enums\ServiceEndpoints::GP_API_PRODUCTION,
				'sandbox' => \GlobalPayments\Api\Entities\Enums\ServiceEndpoints::GP_API_TEST,
			],
			self::GP_API_REGION_EUROPE => [
				'production' => \GlobalPayments\Api\Entities\Enums\ServiceEndpoints::GP_API_PRODUCTION_EU,
				'sandbox' => \GlobalPayments\Api\Entities\Enums\ServiceEndpoints::GP_API_TEST_EU,
			],
		];
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
			case OpenBanking::PAYMENT_METHOD_ID:
				$this->setOpenBankingPaymentMethod();
				break;
			case Paypal::PAYMENT_METHOD_ID:
				$this->setPaypalPaymentMethod();
				break;
		}
	}


	public function setGpApiGateway($config_only = false) {
		$this->gateway = new GpApiGateway();

		/**
		 * All these settings should be provided through the Admin Dashboard.
		 */
		$this->gateway->enabled            = $this->config->get('payment_globalpayments_ucp_enabled');
		$this->gateway->enabledBlik        = $this->config->get('payment_globalpayments_ucp_enabled_blik');
		$this->gateway->enabledOpenbanking = $this->config->get('payment_globalpayments_ucp_enabled_openbanking');
		$this->gateway->title              = $this->config->get('payment_globalpayments_ucp_title');
		$this->gateway->isProduction       = $this->config->get('payment_globalpayments_ucp_is_production');
		$this->gateway->appId              = $this->config->get('payment_globalpayments_ucp_app_id');
		$this->gateway->appKey             = $this->config->get('payment_globalpayments_ucp_app_key');
		$this->gateway->accountName        = $this->config->get('payment_globalpayments_ucp_account_name');
		$this->gateway->sandboxAppId       = $this->config->get('payment_globalpayments_ucp_sandbox_app_id');
		$this->gateway->sandboxAppKey      = $this->config->get('payment_globalpayments_ucp_sandbox_app_key');
		$this->gateway->sandboxAccountName = $this->config->get('payment_globalpayments_ucp_sandbox_account_name');
		$this->gateway->debug              = $this->config->get('payment_globalpayments_ucp_debug');
		$this->gateway->merchantContactUrl = $this->config->get('payment_globalpayments_ucp_contact_url');
		$this->gateway->paymentAction      = $this->config->get('payment_globalpayments_ucp_payment_action');
		$this->gateway->allowCardSaving    = $this->config->get('payment_globalpayments_ucp_allow_card_saving');
		$this->gateway->txnDescriptor      = $this->config->get('payment_globalpayments_ucp_txn_descriptor');
		$this->gateway->baseUrl            = $this->url->link('extension/payment/', '', true);
		$this->gateway->integrationType    = $this->config->get('payment_globalpayments_ucp_integration_type') ?: "dropin_ui";
		$this->gateway->language           = $this->language->get('code');
		$this->gateway->enableInstallments = $this->config->get('payment_globalpayments_ucp_enable_installments') == 1;
		$enableVisaInstallments = $this->config->get('payment_globalpayments_ucp_enable_visa_installments');
		if ($enableVisaInstallments === null || $enableVisaInstallments === '') {
			$enableVisaInstallments = $this->config->get('payment_globalpayments_ucp_enable_installments');
		}
		$this->gateway->enableVisaInstallments = (int) $enableVisaInstallments === 1;
		$visaFundingModeRaw = (string) $this->config->get('payment_globalpayments_ucp_visa_installments_funding_mode');
		$visaFundingModeMap = [
			'any' => 'ANY',
			'consumer_funded' => 'CONSUMER_FUNDED',
			'merchant_funded' => 'MERCHANT_FUNDED',
			'hybrid_funded' => 'HYBRID_FUNDED',
			'bilateral' => 'BILATERAL',
		];
		$visaFundingModeKey = strtolower(trim($visaFundingModeRaw));
		$this->gateway->visaInstallmentsFundingMode = $visaFundingModeMap[$visaFundingModeKey] ?? 'ANY';
		$this->gateway->visaInstallmentsMaxTimeUnitNumber = $this->config->get('payment_globalpayments_ucp_visa_installments_max_time_unit_number');
		$this->gateway->visaInstallmentsMaxAmount = $this->config->get('payment_globalpayments_ucp_visa_installments_max_amount');
		$this->gateway->allowDCC           = $this->config->get('payment_globalpayments_ucp_enable_dcc') == 1;
		$region = self::normalizeGpApiRegion($this->config->get('payment_globalpayments_ucp_region'));
		$this->gateway->region             = $region;
		$this->gateway->serviceUrl         = self::resolveGpApiServiceUrl($region, (bool)$this->gateway->isProduction);
		
		$this->gateway->hppInstallmentsType = $this->config->get('payment_globalpayments_ucp_hpp_installments_plan_type');
		$this->gateway->hppInstallmentsDuration = $this->config->get('payment_globalpayments_ucp_hpp_installments_plan_duration');
		$this->gateway->hppInstallmentsTheshold = $this->config->get('payment_globalpayments_ucp_hpp_installments_threshold');
		$this->gateway->enableThreeDSecure = $this->config->get('payment_globalpayments_ucp_enable_three_d_secure') ?? $this->threeDSecureRequired();



		$this->load->model('localisation/country');
		$store_country_id = $this->config->get('config_country_id');
		$store_country    = $this->model_localisation_country->getCountry($store_country_id);
		$this->gateway->country = $store_country['iso_code_2'];
		$this->gateway->baseCountry = $store_country['iso_code_2'];
		$this->gateway->baseCurrency = $this->config->get('config_currency');
		$visaInstallmentsSupported = Utils::isVisaInstallmentsSupported(
			$this->gateway->baseCountry,
			$this->gateway->baseCurrency
		) && $this->gateway->integrationType === 'dropin_ui';
		$this->gateway->enableVisaInstallments = $this->gateway->enableVisaInstallments
			&& $visaInstallmentsSupported;

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

		$security_token = $this->generate_3ds_security_token();
		$this->gateway->threeDSSecuritySalt      = defined('SECRET_KEY') ? SECRET_KEY : md5($this->config->get('config_name') . $this->config->get('config_url'));
		$this->gateway->checkEnrollmentUrl        = $this->get_secured_3ds_url('extension/payment/globalpayments_ucp/threeDSecureCheckEnrollment', $security_token, (bool)$this->gateway->isProduction);
		$this->gateway->methodNotificationUrl     = $this->getValidNotificationUrl('extension/payment/globalpayments_ucp/threeDSecureMethodNotification', (bool)$this->gateway->isProduction);
		$this->gateway->initiateAuthenticationUrl = $this->get_secured_3ds_url('extension/payment/globalpayments_ucp/threeDSecureInitiateAuthentication', $security_token, (bool)$this->gateway->isProduction);
		$this->gateway->challengeNotificationUrl  = $this->getValidNotificationUrl('extension/payment/globalpayments_ucp/threeDSecureChallengeNotification', (bool)$this->gateway->isProduction);

		$this->gateway->threeDSLibPath = 'catalog/view/javascript/globalpayments-3ds.min.js';

		$this->load->language('extension/payment/globalpayments_ucp');
		$this->gateway->errorTransactionStatusDeclined    = $this->language->get('error_txn_declined');
		$this->gateway->errorGatewayResponse              = $this->language->get('error_txn_error');
		$this->gateway->errorThreeDSecure                 = $this->language->get('error_threedsecure');
		$this->gateway->errorThreeDSecureNoLiabilityShift = $this->language->get('error_threedsecure_no_liability');

		$this->load->language('extension/credit_card/globalpayments_ucp');
		$this->gateway->errorVerifyNotVerified = $this->language->get('error_txn_not_verified');
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
		$this->paymentMethod->acaMethods               = $this->config->get('payment_globalpayments_googlepay_allowed_card_auth_methods');
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

	public function setTransactionApiGateway() {
		$this->gateway = new TransactionApiGateway();

		$this->gateway->enabled                  = $this->config->get('payment_globalpayments_txnapi_status');
		$this->gateway->allowCardSaving          = $this->config->get('payment_globalpayments_txnapi_card');
		$this->gateway->title                    = $this->config->get('payment_globalpayments_txnapi_title');
		$this->gateway->isProduction             = $this->config->get('payment_globalpayments_txnapi_is_production');
		$this->gateway->paymentAction            = $this->config->get('payment_globalpayments_txnapi_payment_action');
		$this->gateway->region                   = $this->config->get('payment_globalpayments_txnapi_region');
		$this->gateway->publicKey                = $this->config->get('payment_globalpayments_txnapi_public_key');
		$this->gateway->sandboxPublicKey         = $this->config->get('payment_globalpayments_txnapi_sandbox_public_key');
		$this->gateway->apiKey                   = $this->config->get('payment_globalpayments_txnapi_api_key');
		$this->gateway->sandboxApiKey            = $this->config->get('payment_globalpayments_txnapi_sandbox_api_key');
		$this->gateway->apiSecret                = $this->config->get('payment_globalpayments_txnapi_api_secret');
		$this->gateway->sandboxApiSecret         = $this->config->get('payment_globalpayments_txnapi_sandbox_api_secret');
		$this->gateway->accountCredential        = $this->config->get('payment_globalpayments_txnapi_account_credential');
		$this->gateway->sandboxAccountCredential = $this->config->get('payment_globalpayments_txnapi_sandbox_account_credential');
		$this->gateway->debug                    = $this->config->get('payment_globalpayments_txnapi_debug');
		$this->gateway->logDirectory             = DIR_LOGS;
	}

	public function setTransitGateway() {
		$this->gateway = new TransitGateway([
			'merchant_id' => $this->config->get('payment_globalpayments_transit_merchant_id'),
			'user_id' => $this->config->get('payment_globalpayments_transit_user_id'),
			'password' => $this->config->get('payment_globalpayments_transit_password'),
			'device_id' => $this->config->get('payment_globalpayments_transit_device_id'),
			'tsep_device_id' => $this->config->get('payment_globalpayments_transit_tsep_device_id'),
			'transaction_key' => $this->config->get('payment_globalpayments_transit_transaction_key'),
			'sandbox_merchant_id' => $this->config->get('payment_globalpayments_transit_sandbox_merchant_id'),
			'sandbox_user_id' => $this->config->get('payment_globalpayments_transit_sandbox_user_id'),
			'sandbox_password' => $this->config->get('payment_globalpayments_transit_sandbox_password'),
			'sandbox_device_id' => $this->config->get('payment_globalpayments_transit_sandbox_device_id'),
			'sandbox_tsep_device_id' => $this->config->get('payment_globalpayments_transit_sandbox_tsep_device_id'),
			'sandbox_transaction_key' => $this->config->get('payment_globalpayments_transit_sandbox_transaction_key'),
			'is_production' => $this->config->get('payment_globalpayments_transit_is_production'),
			'debug' => $this->config->get('payment_globalpayments_transit_debug'),
			'log_directory' => DIR_LOGS
		]);

		$this->gateway->enabled = $this->config->get('payment_globalpayments_transit_status');
		$this->gateway->title = $this->config->get('payment_globalpayments_transit_title');
		$this->gateway->paymentAction = $this->config->get('payment_globalpayments_transit_payment_action');
		$this->gateway->allowCardSaving    = $this->config->get('payment_globalpayments_transit_card');
		$this->gateway->txnDescriptor = $this->config->get('payment_globalpayments_transit_transaction_descriptor');
		$this->gateway->baseUrl = $this->url->link('extension/payment/', '', true);

		$this->load->language('extension/payment/globalpayments_transit');
		$this->gateway->errorTransactionStatusDeclined = $this->language->get('error_txn_declined');
		$this->gateway->errorGatewayResponse = $this->language->get('error_txn_error');
		$this->gateway->errorThreeDSecure = $this->language->get('error_threedsecure');
		$this->gateway->errorThreeDSecureNoLiabilityShift = $this->language->get('error_threedsecure_no_liability');
	}

	public function setGeniusGateway() {
		$live_mode = $this->config->get('payment_globalpayments_genius_live_mode');
		$this->gateway = new GeniusGateway([
			'merchant_name' => $this->config->get('payment_globalpayments_genius_live_merchant_name'),
			'merchant_site_id' => $this->config->get('payment_globalpayments_genius_live_merchant_site_id'),
			'merchant_key' => $this->config->get('payment_globalpayments_genius_live_merchant_key'),
			'web_api_key' => $this->config->get('payment_globalpayments_genius_live_web_api_key'),
			'sandbox_merchant_name' => $this->config->get('payment_globalpayments_genius_sandbox_merchant_name'),
			'sandbox_merchant_site_id' => $this->config->get('payment_globalpayments_genius_sandbox_merchant_site_id'),
			'sandbox_merchant_key' => $this->config->get('payment_globalpayments_genius_sandbox_merchant_key'),
			'sandbox_web_api_key' => $this->config->get('payment_globalpayments_genius_sandbox_web_api_key'),
			'is_production' => $live_mode,
			'debug' => $this->config->get('payment_globalpayments_genius_debug'),
			'log_directory' => DIR_LOGS,
		]);

		$this->gateway->enabled = $this->config->get('payment_globalpayments_genius_status');
		$this->gateway->title = $this->config->get('payment_globalpayments_genius_title');
		$this->gateway->paymentAction = $this->config->get('payment_globalpayments_genius_payment_action');
		$this->gateway->allowCardSaving = $this->config->get('payment_globalpayments_genius_card');
		$this->gateway->txnDescriptor = $this->config->get('payment_globalpayments_genius_order_transaction_descriptor');
		$this->gateway->checkAVSCVN = $this->config->get('payment_globalpayments_genius_check_avs_cvn');
		$this->gateway->avsRejectConditions = $this->config->get('payment_globalpayments_genius_avs_reject_conditions');
		$this->gateway->cvnRejectConditions = $this->config->get('payment_globalpayments_genius_cvn_reject_conditions');
		$this->gateway->baseUrl = $this->url->link('extension/payment/', '', true);

		$this->load->language('extension/payment/globalpayments_genius');
		$this->gateway->errorTransactionStatusDeclined = $this->language->get('error_txn_declined');
		$this->gateway->errorGatewayResponse = $this->language->get('error_txn_error');
	}

	public function setOpenBankingPaymentMethod() {
		$this->paymentMethod                = new OpenBanking($this->gateway);
		$this->paymentMethod->enabled       = $this->config->get('payment_globalpayments_openbanking_enabled');
		$this->paymentMethod->title         = $this->config->get('payment_globalpayments_openbanking_title');
		$this->paymentMethod->paymentAction = $this->config->get('payment_globalpayments_openbanking_payment_action');
		$this->paymentMethod->countries     = $this->config->get('payment_globalpayments_openbanking_countries');
		$this->paymentMethod->sortCode      = $this->config->get('payment_globalpayments_openbanking_sort_code');
		$this->paymentMethod->accountName   = $this->config->get('payment_globalpayments_openbanking_account_name');
		$this->paymentMethod->accountNumber = $this->config->get('payment_globalpayments_openbanking_account_number');
		$this->paymentMethod->iban          = $this->config->get('payment_globalpayments_openbanking_iban');
		$this->paymentMethod->currencies    = $this->config->get('payment_globalpayments_openbanking_currencies');
	}

	public function setPaypalPaymentMethod() {
		$this->paymentMethod                = new Paypal($this->gateway);
		$this->paymentMethod->enabled       = $this->config->get('payment_globalpayments_paypal_enabled');
		$this->paymentMethod->title         = $this->config->get('payment_globalpayments_paypal_title');
		$this->paymentMethod->paymentAction = $this->config->get('payment_globalpayments_paypal_payment_action');
		$this->paymentMethod->accountName   = $this->config->get('payment_globalpayments_paypal_account_name');
		$this->paymentMethod->country       = $this->gateway->country;
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

	/**
	 * Resolve the GP API service URL based on region and environment.
	 *
	 * @param string|null $region
	 * @param bool $isProduction
	 *
	 * @return string
	 */
	public static function resolveGpApiServiceUrl(?string $region, bool $isProduction): string {
		$normalizedRegion = self::normalizeGpApiRegion($region);
		$environmentKey = $isProduction ? 'production' : 'sandbox';
		$serviceUrls = self::getGpApiServiceUrls();

		return $serviceUrls[$normalizedRegion][$environmentKey];
	}

	/**
	 * Normalize the GP API region with a safe fallback.
	 *
	 * @param string|null $region
	 *
	 * @return string
	 */
	protected static function normalizeGpApiRegion(?string $region): string {
		$normalizedRegion = '';
		if (is_string($region)) {
			$normalizedRegion = strtolower(trim($region));
		}

		$serviceUrls = self::getGpApiServiceUrls();
		if (!array_key_exists($normalizedRegion, $serviceUrls)) {
			return self::GP_API_REGION_GLOBAL;
		}

		return $normalizedRegion;
	}


	/**
	 * Build a valid callback URL for 3DS notifications.
	 * In sandbox/test mode, localhost URLs are rewritten to a public-looking HTTPS host.
	 */
	private function getValidNotificationUrl(string $route, bool $isProduction): string {
		$url = $this->url->link($route, '', true);

		if (!$isProduction) {
			if (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false) {
				$url = str_replace(['localhost', '127.0.0.1'], 'sandbox-webhook.example.com', $url);
				$url = str_replace('http://', 'https://', $url);
			}
		}

		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'sandbox-webhook.example.com';
			if (!$isProduction && ($host === 'localhost' || $host === '127.0.0.1')) {
				$host = 'sandbox-webhook.example.com';
			}

			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
			if (!$isProduction) {
				$scheme = 'https';
			}

			$url = sprintf('%s://%s/index.php?route=%s', $scheme, $host, $route);
		}

		return $url;
	}

	private function get_client_ip_for_token(): string {
		$ip_headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');

		foreach ($ip_headers as $header) {
			if (!empty($_SERVER[$header])) {
				$ip = (string)$_SERVER[$header];

				if (strpos($ip, ',') !== false) {
					$ips = explode(',', $ip);
					$ip = trim($ips[0]);
				}

				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	private function generate_3ds_security_token(): string {
		$timestamp = time();
		$client_ip = $this->get_client_ip_for_token();
		$secret_salt = defined('SECRET_KEY') ? SECRET_KEY : md5($this->config->get('config_name') . $this->config->get('config_url'));
		$ip_hash = substr(md5($client_ip . $secret_salt), 0, 16);
		$data = 'gp3ds_' . $timestamp . '_' . $ip_hash;
		$signature = hash_hmac('sha256', $data, $secret_salt);

		return $timestamp . ':' . $ip_hash . ':' . $signature;
	}

	private function get_secured_3ds_url(string $route, string $token, bool $isProduction): string {
		$url = $this->url->link($route, '', true);
		$separator = (strpos($url, '?') !== false) ? '&' : '?';

		return $url . $separator . 'gp3ds_token=' . urlencode($token);
	}

	public function threeDSecureRequired(){
		$this->load->model('localisation/country');
		$store_country_id = $this->config->get('config_country_id');
		$store_country    = $this->model_localisation_country->getCountry($store_country_id);

		$three_d_secure_required_countries = array(
			"AT","BE","BG","HR","CY","CZ","DK","EE","FI","FR","DE","GR","HU",
			"IE","IT","LV","LT","LU","MT","NL","PL","PT","RO","SK","SI","ES",
			"SE","EU","IS","LI","NO","CH","AL","BA","MD","ME","MK","RS","TR",
			"UA","AD","BY","MC","RU","SM","GB","VA","JP","IN"
		);
		return in_array($store_country['iso_code_2'], $three_d_secure_required_countries );
	}

	/**
	 * Returns display text for 3DS option
	 */
	public function getThreeDSecureDisplayText(){
		return sprintf(($this->threeDSecureRequired()) ?
		$this->language->get('three_d_secure_required_display_text') :
		$this->language->get('three_d_secure_not_required_display_text') );
	}
}
