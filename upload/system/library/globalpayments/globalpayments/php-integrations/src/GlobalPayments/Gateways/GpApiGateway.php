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
		
		return array(
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
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array|Array
	 */
	public function getBackendGatewayOptions() {
		return array(
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
		);
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
}
