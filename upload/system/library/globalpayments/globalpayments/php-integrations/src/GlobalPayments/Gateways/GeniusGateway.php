<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\ServiceConfigs\Gateways\GeniusConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\Api\Utils\Logging\SampleRequestLogger;

/**
 * Genius Gateway implementation for OpenCart
 * 
 * Provides GlobalPayments Genius payment processing with hosted fields support
 * and OpenCart-specific configuration management.
 *
 * @package GlobalPayments\PaymentGatewayProvider\Gateways
 * @version 1.0.0
 */
class GeniusGateway extends AbstractGateway {
	/**
	 * First line support e-mail for Genius gateway
	 */
	public const FIRST_LINE_SUPPORT_EMAIL = 'securesubmitcert@e-hps.com';

	/**
	 * Genius Gateway identifier
	 */
	public const GATEWAY_ID = 'GENIUS';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayId = GatewayId::GENIUS;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayProvider = GatewayProvider::GENIUS;

	/**
	 * {@inheritDoc}
	 *
	 * @var bool
	 */
	public $supportsThreeDSecure = false;

	/**
	 * {@inheritDoc}
	 *
	 * @var bool
	 */
	public $supportsDCC = false;

	/**
	 * Live Merchant location's Merchant Name
	 *
	 * @var string
	 */
	public $merchantName;

	/**
	 * Live Merchant location's Site ID
	 *
	 * @var string
	 */
	public $merchantSiteId;

	/**
	 * Live Merchant location's Merchant Key
	 *
	 * @var string
	 */
	public $merchantKey;

	/**
	 * Live Merchant location's Web API Key
	 *
	 * @var string
	 */
	public $webApiKey;

	/**
	 * Sandbox Merchant location's Merchant Name
	 *
	 * @var string
	 */
	public $sandboxMerchantName;

	/**
	 * Sandbox Merchant location's Site ID
	 *
	 * @var string
	 */
	public $sandboxMerchantSiteId;

	/**
	 * Sandbox Merchant location's Merchant Key
	 *
	 * @var string
	 */
	public $sandboxMerchantKey;

	/**
	 * Sandbox Merchant location's Web API Key
	 *
	 * @var string
	 */
	public $sandboxWebApiKey;

	/**
	 * Should live payments be accepted
	 *
	 * @var bool
	 */
	public $isProduction;

	/**
	 * Enable debug mode
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * File path to the logging directory.
	 *
	 * @var string
	 */
	public $logDirectory = '';

	/**
	 * Payment gateway enabled status
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Payment method title
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Payment action (authorize or capture)
	 *
	 * @var string
	 */
	public $paymentAction = 'capture';

	/**
	 * Allow card saving
	 *
	 * @var bool
	 */
	public $allowCardSaving = false;

	/**
	 * Transaction descriptor
	 *
	 * @var string
	 */
	public $txnDescriptor = '';

	/**
	 * Check AVS/CVN
	 *
	 * @var bool
	 */
	public $checkAVSCVN = false;

	/**
	 * AVS reject conditions
	 *
	 * @var array
	 */
	public $avsRejectConditions = [];

	/**
	 * CVN reject conditions
	 *
	 * @var array
	 */
	public $cvnRejectConditions = [];

	/**
	 * Base URL for the payment gateway
	 *
	 * @var string
	 */
	public $baseUrl = '';

	/**
	 * Error message for declined transactions
	 *
	 * @var string
	 */
	public $errorTransactionStatusDeclined = '';

	/**
	 * Error message for gateway response errors
	 *
	 * @var string
	 */
	public $errorGatewayResponse = '';

	/**
	 * Constructor
	 *
	 * @param array $config Gateway configuration
	 */
	public function __construct(array $config = []) {
		parent::__construct();
		$this->configureSettings($config);
	}

	/**
	 * Configure gateway settings from provided configuration
	 *
	 * @param array $config Configuration array
	 */
	private function configureSettings(array $config) {
		// Production settings
		$this->merchantName = $config['merchant_name'] ?? '';
		$this->merchantSiteId = $config['merchant_site_id'] ?? '';
		$this->merchantKey = $config['merchant_key'] ?? '';
		$this->webApiKey = $config['web_api_key'] ?? '';

		// Sandbox settings
		$this->sandboxMerchantName = $config['sandbox_merchant_name'] ?? '';
		$this->sandboxMerchantSiteId = $config['sandbox_merchant_site_id'] ?? '';
		$this->sandboxMerchantKey = $config['sandbox_merchant_key'] ?? '';
		$this->sandboxWebApiKey = $config['sandbox_web_api_key'] ?? '';

		// Environment and debug settings
		$this->isProduction = isset($config['is_production']) ? (bool)$config['is_production'] : false;
		$this->debug = isset($config['debug']) ? (bool)$config['debug'] : false;
		$this->logDirectory = $config['log_directory'] ?? '';
	}

	/**
	 * Get frontend gateway options for client-side configuration
	 *
	 * @return array
	 */
	public function getFrontendGatewayOptions() {
		return [
			'gateway' => self::GATEWAY_ID,
			'webApiKey' => $this->getCredentialSetting('web_api_key'),
			'env' => $this->isProduction ? 'production' : 'sandbox',
			'debug' => $this->debug
		];
	}

	/**
	 * Get backend gateway options for server-side processing
	 *
	 * @return array
	 */
	public function getBackendGatewayOptions() {
		return [
			'gatewayProvider' => $this->gatewayProvider,
			'merchantName' => $this->getCredentialSetting('merchant_name'),
			'merchantSiteId' => $this->getCredentialSetting('merchant_site_id'),
			'merchantKey' => $this->getCredentialSetting('merchant_key'),
			'webApiKey' => $this->getCredentialSetting('web_api_key'),
			'environment' => $this->isProduction ? Environment::PRODUCTION : Environment::TEST,
			'debug' => $this->debug,
			'logDirectory' => $this->logDirectory
		];
	}

	/**
	 * Get credential setting based on environment
	 *
	 * @param string $setting Setting name
	 * @return string Setting value
	 */
	public function getCredentialSetting($setting) {
		if ($this->isProduction) {
			switch ($setting) {
				case 'merchant_name':
					return $this->merchantName;
				case 'merchant_site_id':
					return $this->merchantSiteId;
				case 'merchant_key':
					return $this->merchantKey;
				case 'web_api_key':
					return $this->webApiKey;
			}
		} else {
			switch ($setting) {
				case 'merchant_name':
					return $this->sandboxMerchantName;
				case 'merchant_site_id':
					return $this->sandboxMerchantSiteId;
				case 'merchant_key':
					return $this->sandboxMerchantKey;
				case 'web_api_key':
					return $this->sandboxWebApiKey;
			}
		}
		return '';
	}

	/**
	 * Configure the Genius gateway service
	 *
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ConfigurationException
	 */
	public function configureService() {
		$config = new GeniusConfig();
		
		$config->merchantName = $this->getCredentialSetting('merchant_name');
		$config->merchantSiteId = $this->getCredentialSetting('merchant_site_id');
		$config->merchantKey = $this->getCredentialSetting('merchant_key');
		$config->registerNumber = $this->getCredentialSetting('web_api_key');
		
		$config->environment = $this->isProduction ? Environment::PRODUCTION : Environment::TEST;
		
		if ($this->debug) {
			$config->requestLogger = new SampleRequestLogger(new Logger($this->logDirectory));
		}

		ServicesContainer::configureService($config);
	}

	/**
	 * Get first line support email
	 *
	 * @return string
	 */
	public function getFirstLineSupportEmail() {
		return self::FIRST_LINE_SUPPORT_EMAIL;
	}
}
