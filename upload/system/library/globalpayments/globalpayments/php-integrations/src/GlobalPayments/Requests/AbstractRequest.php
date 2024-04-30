<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests;

use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\Api\Entities\Enums\PaymentMethodUsageMode;

abstract class AbstractRequest implements RequestInterface {
	/**
	 * Gateway server-side config.
	 *
	 * @var array
	 */
	public $config;

	/**
	 * Request data.
	 *
	 * @var RequestData|null
	 */
	protected $requestData;

	/**
	 * AbstractRequest constructor.
	 *
	 * @param array $config
	 * @param RequestData|null $requestData
	 */
	public function __construct(array $config = array(), RequestData $requestData = null) {
		$this->config      = $config;
		$this->requestData = $requestData;
	}

	public function getPaymentToken() {
		if ( ! empty($this->requestData->paymentToken)) {
			return $this->requestData->paymentToken;
		}

		return $this->getPaymentTokenFromResponse();
	}

	public function getPaymentTokenInfo() {
		$token = $this->getMultiUseToken();

		if (empty($token)) {
				return ['token' => $this->getSingleUseToken(), 'usage' => PaymentMethodUsageMode::SINGLE];
		}

		return ['token' => $token, 'usage' => PaymentMethodUsageMode::MULTIPLE];
	}

	private function getMultiUseToken() {
		return $this->requestData->paymentToken;
	}

	private function getSingleUseToken()
	{
		$tokenResponse = json_decode($this->requestData->paymentTokenResponse);

		if (empty($tokenResponse->paymentReference)) {
			throw new \Exception($this->requestData->meta->shared_text['error_unable_to_get_payment_token']);
		}
	}

	private function getPaymentTokenFromResponse()
	{
		$tokenResponse = json_decode($this->requestData->paymentTokenResponse);

		if (empty($tokenResponse->paymentReference)) {
			throw new \Exception($this->requestData->meta->shared_text['error_unable_to_get_payment_token']);
		}

		return $tokenResponse->paymentReference;
	}

	public function getCardHolderName() {
		$tokenResponse = json_decode($this->requestData->paymentTokenResponse);

		return $tokenResponse->details->cardholderName ?? null;
	}

	public static function getPostRequestData() {
		if (('POST' !== $_SERVER['REQUEST_METHOD'])) {
			return null;
		}
		if ('application/json' === $_SERVER['CONTENT_TYPE']) {
			return json_decode(file_get_contents( 'php://input'));
		}

		return $_POST;
	}

	public static function sendJsonResponse(array $response, int $responseCode = null) {
		header('Content-Type: application/json');
		if ($responseCode) {
		    http_response_code($responseCode);
		}
		echo json_encode($response);
		exit;
	}
}
