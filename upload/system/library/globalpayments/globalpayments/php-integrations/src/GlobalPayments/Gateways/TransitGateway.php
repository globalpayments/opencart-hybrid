<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

use GlobalPayments\Api\Entities\Enums\Channel;
use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\Entities\Enums\Secure3dStatus;
use GlobalPayments\Api\Entities\Exceptions\ApiException;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\TransIT\CreateTransactionKeyRequest;
use GlobalPayments\Api\ServiceConfigs\AcceptorConfig;
use GlobalPayments\Api\ServiceConfigs\Gateways\TransitConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\Api\Utils\Logging\SampleRequestLogger;
use GlobalPayments\PaymentGatewayProvider\Requests\TransIT\CreateManifestRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure\CheckEnrollmentRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure\InitiateAuthenticationRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure\GetAuthenticationDataRequest;

/**
 * TransIT Gateway implementation for OpenCart
 * 
 * Provides TSYS TransIT payment processing with enhanced security
 * and OpenCart-specific configuration management.
 *
 * @package GlobalPayments\PaymentGatewayProvider\Gateways
 * @version 1.0.0
 */
class TransitGateway extends AbstractGateway {
	/**
	 * First line support e-mail for TransIT gateway
	 */
	public const FIRST_LINE_SUPPORT_EMAIL = 'securesubmitcert@e-hps.com';

	/**
	 * TransIT Gateway identifier
	 */
	public const GATEWAY_ID = 'TRANSIT';

	/**
	 * Developer ID for integration
	 */
	public const DEVELOPER_ID = '003226G001';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayId = GatewayId::TRANSIT;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayProvider = GatewayProvider::TRANSIT;

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
	 * Live Merchant location's Merchant ID
	 *
	 * @var string
	 */
	public $merchantId;

	/**
	 * Live Merchant location's User ID
	 * Note: only needed to create transaction key
	 *
	 * @var string
	 */
	public $userId;

	/**
	 * Live Merchant location's Password
	 * Note: only needed to create transaction key
	 *
	 * @var string
	 */
	public $password;

	/**
	 * Live Merchant location's Device ID
	 *
	 * @var string
	 */
	public $deviceId;

	/**
	 * Live Device ID for TSEP entity specifically
	 *
	 * @var string
	 */
	public $tsepDeviceId;

	/**
	 * Live Merchant location's Transaction Key
	 *
	 * @var string
	 */
	public $transactionKey;

	/**
	 * Sandbox Merchant location's Merchant ID
	 *
	 * @var string
	 */
	public $sandboxMerchantId;

	/**
	 * Sandbox Merchant location's User ID
	 * Note: only needed to create transaction key
	 *
	 * @var string
	 */
	public $sandboxUserId;

	/**
	 * Sandbox Merchant location's Password
	 * Note: only needed to create transaction key
	 *
	 * @var string
	 */
	public $sandboxPassword;

	/**
	 * Sandbox Merchant location's Device ID
	 *
	 * @var string
	 */
	public $sandboxDeviceId;

	/**
	 * Sandbox Device ID for TSEP entity specifically
	 *
	 * @var string
	 */
	public $sandboxTsepDeviceId;

	/**
	 * Sandbox Merchant location's Transaction Key
	 *
	 * @var string
	 */
	public $sandboxTransactionKey;

	/**
	 * Should live payments be accepted
	 *
	 * @var bool
	 */
	public $isProduction = false;

	/**
	 * Should debug logging be enabled
	 *
	 * @var bool
	 */
	public $debug = false;

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
		$this->merchantId = $config['merchant_id'] ?? '';
		$this->userId = $config['user_id'] ?? '';
		$this->password = $config['password'] ?? '';
		$this->deviceId = $config['device_id'] ?? '';
		$this->tsepDeviceId = $config['tsep_device_id'] ?? '';
		$this->transactionKey = $config['transaction_key'] ?? '';

		// Sandbox settings
		$this->sandboxMerchantId = $config['sandbox_merchant_id'] ?? '';
		$this->sandboxUserId = $config['sandbox_user_id'] ?? '';
		$this->sandboxPassword = $config['sandbox_password'] ?? '';
		$this->sandboxDeviceId = $config['sandbox_device_id'] ?? '';
		$this->sandboxTsepDeviceId = $config['sandbox_tsep_device_id'] ?? '';
		$this->sandboxTransactionKey = $config['sandbox_transaction_key'] ?? '';

		// Environment and debug settings
		$this->isProduction = isset($config['is_production']) ? (bool)$config['is_production'] : false;
		$this->debug = isset($config['debug']) ? (bool)$config['debug'] : false;
	}

	/**
	 * Get the first line support email
	 *
	 * @return string
	 */
	public function getFirstLineSupportEmail() {
		return self::FIRST_LINE_SUPPORT_EMAIL;
	}

	/**
	 * Get frontend gateway options for client-side configuration
	 *
	 * @return array
	 */
	public function getFrontendGatewayOptions() {
		return [
				'gateway' => self::GATEWAY_ID,
				'deviceId' => $this->getCredentialSetting('tsep_device_id'),
				'manifest' => $this->createManifest(),
				'env' => $this->isProduction ? Environment::PRODUCTION : "sandbox",
				'developerId' => self::DEVELOPER_ID,
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
			'merchantId' => $this->getCredentialSetting('merchant_id'),
			'username' => $this->getCredentialSetting('user_id'),
			'password' => $this->getCredentialSetting('password'),
			'transactionKey' => $this->getCredentialSetting('transaction_key'),
			'tsepDeviceId' => $this->getCredentialSetting('tsep_device_id'),
			'deviceId' => $this->getCredentialSetting('device_id'),
			'developerId' => self::DEVELOPER_ID,
			'environment' => $this->isProduction ? Environment::PRODUCTION : Environment::TEST,
			'channel' => Channel::CardNotPresent,
			'debug' => $this->debug
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
				case 'merchant_id':
					return $this->merchantId;
				case 'user_id':
					return $this->userId;
				case 'password':
					return $this->password;
				case 'device_id':
					return $this->deviceId;
				case 'tsep_device_id':
					return $this->tsepDeviceId;
				case 'transaction_key':
					return $this->transactionKey;
			}
		} else {
			switch ($setting) {
				case 'merchant_id':
					return $this->sandboxMerchantId;
				case 'user_id':
					return $this->sandboxUserId;
				case 'password':
					return $this->sandboxPassword;
				case 'device_id':
					return $this->sandboxDeviceId;
				case 'tsep_device_id':
					return $this->sandboxTsepDeviceId;
				case 'transaction_key':
					return $this->sandboxTransactionKey;
			}
		}

		return '';
	}

	/**
	 * Create manifest for TSEP integration
	 *
	 * @return object Manifest data
	 * @throws ApiException If manifest creation fails
	 */
	public function createManifest() {
			
		try {
			
            $config = new TransitConfig();
            $config->merchantId = $this->getCredentialSetting('merchant_id');
            $config->deviceId = $this->getCredentialSetting('tsep_device_id');
            $config->developerId = self::DEVELOPER_ID;
            $config->transactionKey = $this->getCredentialSetting('transaction_key');
            $config->acceptorConfig = new AcceptorConfig();
			$config->environment = ((int)$this->isProduction) ?
                Environment::PRODUCTION : Environment::TEST;
            
            ServicesContainer::configureService($config);
            $provider = ServicesContainer::instance()->getClient('default');
			
            return $provider->createManifest();
        } catch (\Exception $e) {
            error_log('Manifest creation failed: ' . $e->getMessage());
            $this->error = 'Unable to create manifest.';
            return null;
        }
	}

	/**
	 * Process payment with enhanced 3D Secure support
	 *
	 * @param RequestData $requestData Payment request data
	 * @return mixed Payment processing result
	 * @throws ApiException If payment processing fails
	 */
	public function processPayment(RequestData $requestData) {
		// Validate request data
		$this->validatePaymentRequest($requestData);

		// Process the payment
		return parent::processPayment($requestData);
	}

	/**
	 * Process 3D Secure authentication
	 *
	 * @param RequestData $requestData Request data with 3DS information
	 * @return object 3D Secure authentication result
	 * @throws ApiException If 3D Secure processing fails
	 */
	protected function processThreeDSecure(RequestData $requestData) {
		try {
			// Check enrollment
			$enrollmentRequest = new CheckEnrollmentRequest();
			$enrollmentResponse = $enrollmentRequest->execute();

			if (isset($enrollmentResponse['enrolled']) && $enrollmentResponse['enrolled']) {
				// Initiate authentication
				$authRequest = new InitiateAuthenticationRequest();
				$authResponse = $authRequest->execute();

				// Get authentication data
				$authDataRequest = new GetAuthenticationDataRequest();
				$authDataResponse = $authDataRequest->execute();

				return $authDataResponse;
			}

			return $enrollmentResponse;

		} catch (\Exception $e) {
			$this->log('3D Secure processing failed: ' . $e->getMessage());
			throw new ApiException('3D Secure authentication failed');
		}
	}

	/**
	 * Validate payment request data
	 *
	 * @param RequestData $requestData Request data to validate
	 * @throws \InvalidArgumentException If validation fails
	 */
	private function validatePaymentRequest(RequestData $requestData) {
		if (empty($requestData->amount) || $requestData->amount <= 0) {
			throw new \InvalidArgumentException('Invalid payment amount');
		}

		if (empty($requestData->currency)) {
			throw new \InvalidArgumentException('Currency is required');
		}

		// Validate required credentials
		$requiredFields = ['merchant_id', 'device_id'];
		foreach ($requiredFields as $field) {
			if (empty($this->getCredentialSetting($field))) {
				throw new \InvalidArgumentException("Missing required credential: {$field}");
			}
		}
	}

	/**
	 * Process refund request
	 *
	 * @param RequestData $requestData Refund request data
	 * @return mixed Refund processing result
	 * @throws ApiException If refund processing fails
	 */
	public function processRefund(RequestData $requestData) {
		$requestData->requestType = 'REFUND';
		$requestData->gatewayProvider = $this->gatewayProvider;

		$this->validateRefundRequest($requestData);

		return parent::processRefund($requestData);
	}

	/**
	 * Validate refund request data
	 *
	 * @param RequestData $requestData Refund request data
	 * @throws \InvalidArgumentException If validation fails
	 */
	private function validateRefundRequest(RequestData $requestData) {
		if (empty($requestData->transactionId)) {
			throw new \InvalidArgumentException('Original transaction ID is required for refunds');
		}

		if (empty($requestData->amount) || $requestData->amount <= 0) {
			throw new \InvalidArgumentException('Invalid refund amount');
		}
	}

	/**
	 * Log debug information if debug mode is enabled
	 *
	 * @param string $message Message to log
	 * @param array $context Additional context
	 */
	public function log($message, array $context = []) {
		if ($this->debug) {
			$logMessage = '[TransIT Gateway] ' . $message;
			if (!empty($context)) {
				$logMessage .= ' Context: ' . json_encode($context);
			}
			
			// Use parent logging mechanism if available
			if (method_exists(parent::class, 'log')) {
				parent::log($logMessage);
			}
		}
	}
}