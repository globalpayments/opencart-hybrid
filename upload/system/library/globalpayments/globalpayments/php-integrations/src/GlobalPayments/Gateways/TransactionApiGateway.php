<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;

class TransactionApiGateway extends AbstractGateway {

	public const FIRST_LINE_SUPPORT_EMAIL = 'api.integrations@globalpay.com';

	public $gatewayId = GatewayId::TRANSACTION_API;

	public $gatewayProvider = GatewayProvider::TRANSACTION_API;

	public $region;

	public $publicKey;

	public $sandboxPublicKey;

	public $apiKey;

	public $sandboxApiKey;

	public $apiSecret;

	public $sandboxApiSecret;

	public $accountCredential;

	public $sandboxAccountCredential;

	public function getFrontendGatewayOptions() {
		return array(
			'X-GP-Api-Key' => $this->getCredentialSetting('publicKey'),
			'X-GP-Environment' => $this->isProduction ? 'prod' : 'test'
		);
	}

	public function getBackendGatewayOptions() {
		return array(
			'gatewayProvider' => $this->gatewayProvider,
			'apiKey' => $this->getCredentialSetting('apiKey'),
			'apiSecret' => $this->getCredentialSetting('apiSecret'),
			'accountCredential' => $this->getCredentialSetting('accountCredential'),
			'country' => $this->region,
			'apiVersion' => '2021-04-08',
			'apiPartnerName' => 'php_sdk_opencart',
			'debug' => $this->debug,
			'logDirectory' => $this->logDirectory,
		);
	}

	public function getFirstLineSupportEmail() {
		return self::FIRST_LINE_SUPPORT_EMAIL;
	}

	public function processPayment(RequestData $requestData) {
		return parent::processPayment($requestData);
	}
}
