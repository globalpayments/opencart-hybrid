<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

use GlobalPayments\Api\Entities\Enums\Channel;
use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\Entities\Enums\Secure3dStatus;
use GlobalPayments\Api\Entities\Exceptions\ApiException;
use GlobalPayments\Api\Gateways\GpApiConnector;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\AccessToken\GetAccessTokenRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure\CheckEnrollmentRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure\GetAuthenticationDataRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure\InitiateAuthenticationRequest;

class GpApiGateway extends AbstractGateway {
	/**
	 * First line support e-mail.
	 */
	public const FIRST_LINE_SUPPORT_EMAIL = 'api.integrations@globalpay.com';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayId = GatewayId::GP_API;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayProvider = GatewayProvider::GP_API;

	/**
	 * {@inheritDoc}
	 *
	 * @var bool
	 */
	public $supportsThreeDSecure = true;

	/**
	 * {@inheritDoc}
	 *
	 * @var bool
	 */
	public $supportsDCC = true;

	/**
	 * Enable Installments feature
	 *
	 * @var bool
	 */
	public $enableInstallments = false;

	/**
	 * Sandbox App ID.
	 *
	 * @var string
	 */
	public $sandboxAppId;

	/**
	 * Sandbox App Key.
	 *
	 * @var string
	 */
	public $sandboxAppKey;

	/**
	 * Sandbox Account Name.
	 *
	 * @var string
	 */
	public $sandboxAccountName;

	/**
	 * Live App ID.
	 *
	 * @var string
	 */
	public $appId;

	/**
	 * Live App Key.
	 *
	 * @var string
	 */
	public $appKey;

	/**
	 * Live Account Name.
	 *
	 * @var string
	 */
	public $accountName;

	/**
	 * Merchant country.
	 *
	 * @var
	 */
	public $country;

	/**
	 * Merchant region.
	 *
	 * @var string
	 */
	public $region;

	/**
	 * GP API service URL.
	 *
	 * @var string
	 */
	public $serviceUrl;

	/**
	 * Merchant defined Contact URL.
	 *
	 * @var string
	 */
	public $merchantContactUrl;

	/**
	 * 3DS Method notification endpoint.
	 *
	 * @var string
	 */
	public $methodNotificationUrl;

	/**
	 *
	 *
	 * @var string
	 */
	public $checkEnrollmentUrl;

	/**
	 *
	 *
	 * @var string
	 */
	public $initiateAuthenticationUrl;

	/**
	 * Shared 3DS security salt from platform config.
	 *
	 * @var string
	 */
	public $threeDSSecuritySalt = '';


	/**
	 * 3DS Challenge notification endpoint.
	 *
	 * @var string
	 */
	public $challengeNotificationUrl;

	/**
	 * Path to the 3DS JS lib.
	 *
	 * @var string
	 */
	public $threeDSLibPath = '';

	/**
	 * Dynamic headers ('x-gp-platform', 'x-gp-extension').
	 * e.g. 'x-gp-platform'
	 * @var array
	 */
	public $dynamicHeaders;

	public $language;

	/**
	 * @var bool
	 */
	public $enableThreeDSecure = true;
	
	/**
	 * @var string
	 */
	public $integrationType;

	/**
     * States whether the Blik payment method should be enabled
     *
     * @var bool
     */
    public $enabledBlik;

	/**
     * States whether the Open Banking payment method should be enabled
     *
     * @var bool
     */
    public $enabledOpenbanking;

	/**
	 * Base country for payment method eligibility checks
	 *
	 * @var string
	 */
	public $baseCountry;

	/**
	 * Base currency for payment method eligibility checks
	 *
	 * @var string
	 */
	public $baseCurrency;

	/**
	 * Authentication statuses
	 */
	public $threeDSecureAuthenticationStatus = array(
		Secure3dStatus::NOT_ENROLLED,
		Secure3dStatus::SUCCESS_AUTHENTICATED,
		Secure3dStatus::SUCCESS_ATTEMPT_MADE,
	);

	/**
	 * {@inheritDoc}
	 *
	 * @return array|Array
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ApiException
	 */
	public function getFrontendGatewayOptions() {
		// Check if BLIK and Open Banking should be enabled based on country and currency
		$enableBlik = $this->shouldEnablePolishPaymentMethods() ? (int) $this->enabledBlik : 0;
		$enableOpenbanking = $this->shouldEnablePolishPaymentMethods() ? (int) $this->enabledOpenbanking : 0;
		
		// Determine data residency based on region
		// Check for EU/Europe region (case-insensitive)
		$dataResidency = 'NONE';
		if (!empty($this->region)) {
			$regionLower = strtolower(trim($this->region));
			if (in_array($regionLower, ['europe', 'eu'])) {
				$dataResidency = 'EU';
			}
		}
		
		$options = array(
			'accessToken'           => $this->getAccessToken(),
			'apiVersion'            => GpApiConnector::GP_API_VERSION,
			'env'                   => $this->isProduction ? parent::ENVIRONMENT_PRODUCTION : parent::ENVIRONMENT_SANDBOX,
			'requireCardHolderName' => true,
			'enableThreeDSecure' 	=> $this->enableThreeDSecure,
			'fieldValidation' => [
				'enabled' => true,
			],
			'enableBlik' => $enableBlik,
			'enableOpenbanking' => $enableOpenbanking,
			'language' => $this->language,
			'integrationType' => $this->integrationType,
			'enableInstallments' => $this->enableInstallments ?? false,
			'dataResidency' => $dataResidency,
		);
		
		// MUST set serviceUrl for frontend JavaScript library to use correct regional endpoint
		// The JS library requires explicit serviceUrl even when dataResidency is set
		if (!empty($this->serviceUrl)) {
			$options['serviceUrl'] = $this->serviceUrl;
		}
		
		return $options;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array|Array
	 */
	public function getBackendGatewayOptions() {
		// Determine data residency based on region
		// Check for EU/Europe region (case-insensitive)
		$dataResidency = 'NONE';
		if (!empty($this->region)) {
			$regionLower = strtolower(trim($this->region));
			if (in_array($regionLower, ['europe', 'eu'])) {
				$dataResidency = 'EU';
			}
		}
		
		$backendOptions = array(
			'gatewayProvider'          => $this->gatewayProvider,
			'gatewayId'                => $this->gatewayId,
			'appId'                    => $this->getCredentialSetting('appId'),
			'appKey'                   => $this->getCredentialSetting('appKey'),
			'accountName'              => $this->getCredentialSetting('accountName'),
			'channel'                  => Channel::CardNotPresent,
			'country'                  => $this->country,
			'environment'              => $this->isProduction ? Environment::PRODUCTION : Environment::TEST,
			'methodNotificationUrl'    => $this->methodNotificationUrl,
			'challengeNotificationUrl' => $this->challengeNotificationUrl,
			'merchantContactUrl'       => $this->merchantContactUrl,
			'debug'                    => $this->debug,
			'logDirectory'             => $this->logDirectory,
			'dynamicHeaders'           => $this->dynamicHeaders,
			'enable_installments'      => $this->enableInstallments ?? false,
			'dataResidency'            => $dataResidency,
		);

		return $backendOptions;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getFirstLineSupportEmail() {
		return self::FIRST_LINE_SUPPORT_EMAIL;
	}

	/**
	 * Get a bearer access token to execute tokenization on GP JS lib side.
	 *
	 * @return string
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ApiException
	 */
	protected function getAccessToken() {
		$response = $this->processRequest(GetAccessTokenRequest::class);

		return $response->token;
	}

	/**
	 *
	 */
	public function threeDSecureCheckEnrollment(RequestData $requestData) {
		$security_check = $this->verify_threedsecure_request_security();
		if (true !== $security_check) {
			AbstractRequest::sendJsonResponse([
				'error'    => true,
				'message'  => $security_check['message'],
				'enrolled' => CheckEnrollmentRequest::NO_RESPONSE,
			]);
			return;
		}

		try {
			$response = $this->processRequest(CheckEnrollmentRequest::class, $requestData);
		} catch (ApiException $e) {
			$this->log($e->getMessage());
			if ('50022' == $e->responseCode) {
				throw new \Exception('Please try again with another card.');
			}
			throw new \Exception( $e->getMessage() );
		} catch (\Exception $e) {
			$response = array(
				'error'    => true,
				'message'  => $e->getMessage(),
				'enrolled' => CheckEnrollmentRequest::NO_RESPONSE,
			);
		}

		AbstractRequest::sendJsonResponse($response);
	}

	public function threeDSecureMethodNotification() {
		if (('POST' !== $_SERVER['REQUEST_METHOD'])) {
			return;
		}
		if ('application/x-www-form-urlencoded' !== $_SERVER['CONTENT_TYPE']) {
			return;
		}

		$convertedThreeDSMethodData = json_decode(base64_decode($_POST['threeDSMethodData']));
		$methodResponse             = json_encode([
			'threeDSServerTransID' => $convertedThreeDSMethodData->threeDSServerTransID,
		]);

		$response = <<<MNR
<script src="$this->threeDSLibPath"></script>
<script>
		GlobalPayments.ThreeDSecure.handleMethodNotification($methodResponse);
</script>
MNR;
		echo $response;
		exit;
	}

	public function threeDSecureInitiateAuthentication(RequestData $requestData) {
		$security_check = $this->verify_threedsecure_request_security();
		if (true !== $security_check) {
			AbstractRequest::sendJsonResponse([
				'error'   => true,
				'message' => $security_check['message'],
			]);
			return;
		}

		try {
			$response = $this->processRequest(InitiateAuthenticationRequest::class, $requestData);
		} catch (\Exception $e) {
			$response = array(
				'error'   => true,
				'message' => $e->getMessage(),
			);
		}

		AbstractRequest::sendJsonResponse($response);
	}

	public function threeDSecureChallengeNotification() {
		try {
			$challengeResponse = new \stdClass();

			if (isset($_POST['cres'])) {
				$convertedCRes = json_decode(base64_decode($_POST['cres']));

				$challengeResponse = json_encode([
					'threeDSServerTransID' => $convertedCRes->threeDSServerTransID,
					'transStatus'          => $convertedCRes->transStatus ?? '',
				]);
			}

			$response = <<<CNR
<script src="$this->threeDSLibPath"></script>
<script>
    GlobalPayments.ThreeDSecure.handleChallengeNotification($challengeResponse);
</script>
CNR;
			echo $response;
			exit;
		} catch (\Exception $e) {
			AbstractRequest::sendJsonResponse([
				'error'   => true,
				'message' => $e->getMessage(),
			]);
		}
	}

	public function threeDSecureGetAuthenticationData(RequestData $requestData) {
		try {
			$threeDSecureData = $this->processRequest(GetAuthenticationDataRequest::class, $requestData);
		} catch (\Exception $e) {
			throw new \Exception($this->errorThreeDSecureNoLiabilityShift);
		}

		if ('YES' !== $threeDSecureData->liabilityShift
		     || ! in_array( $threeDSecureData->status, $this->threeDSecureAuthenticationStatus)) {
			throw new \Exception( $this->errorThreeDSecureNoLiabilityShift);
		}

		return $threeDSecureData;
	}

	public function processPayment(RequestData $requestData) {
		if ( ! empty($requestData->serverTransactionId)) {
			$requestData->threeDSecure = $this->threeDSecureGetAuthenticationData($requestData);
		}

		return parent::processPayment($requestData);
	}

	/**
	 * Check if Polish payment methods (BLIK and Open Banking) should be enabled
	 * Based on base country being Poland (PL) and base currency being PLN
	 *
	 * @return bool
	 */
	private function shouldEnablePolishPaymentMethods(): bool {

		if (!empty($this->baseCountry) && !empty($this->baseCurrency)) {
			return ($this->baseCountry === 'PL' && $this->baseCurrency === 'PLN');
		}
		return false;
	}

	/**
	 * Verify request security for 3DS endpoints.
	 *
	 * @return bool|array
	 */
	private function verify_threedsecure_request_security() {
		$token = isset($_GET['gp3ds_token']) ? trim((string)$_GET['gp3ds_token']) : '';

		if ($token === '') {
			return $this->buildThreeDSError('missing_token', 'Security token missing. Please refresh the page and try again.');
		}

		$parts = explode(':', $token);
		if (count($parts) !== 3) {
			return $this->buildThreeDSError('invalid_token', 'Invalid security token. Please refresh the page and try again.');
		}

		$timestampPart = $parts[0];
		$tokenIpHash = $parts[1];
		$providedSignature = $parts[2];

		if (!ctype_digit((string)$timestampPart)) {
			return $this->buildThreeDSError('invalid_token', 'Invalid security token. Please refresh the page and try again.');
		}

		$timestamp = (int)$timestampPart;
		$tokenAge = time() - $timestamp;
		if ($tokenAge > 300 || $tokenAge < 0) {
			return $this->buildThreeDSError('token_expired', 'Security token expired. Please refresh the page and try again.');
		}

		$clientIp = $this->get_client_ip();
		$secretSalt = (string)$this->threeDSSecuritySalt;
		if ($secretSalt === '') {
			return $this->buildThreeDSError('misconfigured_security', 'Security verification failed. Please refresh the page and try again.');
		}

		$currentIpHash = substr(md5($clientIp . $secretSalt), 0, 16);
		if (!hash_equals($tokenIpHash, $currentIpHash)) {
			return $this->buildThreeDSError('ip_mismatch', 'Security verification failed. Please refresh the page and try again.');
		}

		$expectedData = 'gp3ds_' . $timestamp . '_' . $tokenIpHash;
		$expectedSignature = hash_hmac('sha256', $expectedData, $secretSalt);
		if (!hash_equals($expectedSignature, $providedSignature)) {
			return $this->buildThreeDSError('invalid_signature', 'Security verification failed. Please refresh the page and try again.');
		}

		$tokenHash = md5($token);
		$ipHash = md5($clientIp);

		$usageKey = 'gp_3ds_usage_' . $tokenHash;
		$usageCount = $this->get_transient_counter($usageKey);
		if ($usageCount >= 2) {
			return $this->buildThreeDSError('token_exhausted', 'Security token exhausted. Please refresh the page and try again.');
		}

		$minuteKey = 'gp_3ds_rate_' . $ipHash;
		$minuteCount = $this->get_transient_counter($minuteKey);
		if ($minuteCount >= 2) {
			return $this->buildThreeDSError('rate_limited', 'Too many requests. Please wait a moment before trying again.');
		}
		$this->set_transient_counter($minuteKey, $minuteCount + 1, 60);

		$hourlyKey = 'gp_3ds_hourly_' . $ipHash;
		$hourCount = $this->get_transient_counter($hourlyKey);
		if ($hourCount >= 10) {
			return $this->buildThreeDSError('hourly_limit', 'Request limit reached. Please try again later.');
		}
		$this->set_transient_counter($hourlyKey, $hourCount + 1, 3600);

		$remainingTtl = max(1, 300 - $tokenAge);
		$this->set_transient_counter($usageKey, $usageCount + 1, $remainingTtl);

		return true;
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 */
	private function get_transient_counter(string $key): int {
		$filePath = $this->get_transient_file_path($key);
		if (!is_file($filePath) || !is_readable($filePath)) {
			return 0;
		}

		$raw = file_get_contents($filePath);
		if ($raw === false || $raw === '') {
			return 0;
		}

		$data = json_decode($raw, true);
		if (!is_array($data) || !isset($data['expires']) || !isset($data['value'])) {
			@unlink($filePath);
			return 0;
		}

		if ((int)$data['expires'] < time()) {
			@unlink($filePath);
			return 0;
		}

		return (int)$data['value'];
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @param int $ttlSeconds
	 *
	 * @return void
	 */
	private function set_transient_counter(string $key, int $value, int $ttlSeconds): void {
		if ($ttlSeconds <= 0) {
			return;
		}

		$filePath = $this->get_transient_file_path($key);
		$directory = dirname($filePath);
		if (!is_dir($directory)) {
			@mkdir($directory, 0775, true);
		}

		$payload = [
			'value' => $value,
			'expires' => time() + $ttlSeconds,
		];

		file_put_contents($filePath, json_encode($payload), LOCK_EX);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function get_transient_file_path(string $key): string {
		if (defined('DIR_STORAGE')) {
			$basePath = rtrim((string)DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		} elseif (defined('DIR_CACHE')) {
			$basePath = rtrim((string)DIR_CACHE, '/\\') . DIRECTORY_SEPARATOR;
		} else {
			$basePath = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR;
		}

		return $basePath . 'gp_3ds_transient_' . md5($key) . '.json';
	}

	/**
	 * @param string $code
	 * @param string $message
	 *
	 * @return array
	 */
	private function buildThreeDSError(string $code, string $message): array {
		return [
			'code' => $code,
			'message' => $message,
		];
	}

	/**
	 * @return string
	 */
	private function get_client_ip(): string {
		$ipHeaders = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');

		foreach ($ipHeaders as $header) {
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
}
