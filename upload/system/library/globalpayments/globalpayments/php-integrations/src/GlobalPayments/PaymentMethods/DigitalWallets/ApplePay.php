<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets;

use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class ApplePay extends AbstractDigitalWallet {
	public const PAYMENT_METHOD_ID = 'globalpayments_applepay';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Apple Pay';

	/**
	 * Indicates the card brands the merchant accepts for Apple Pay.
	 *
	 * @var
	 */
	public $ccTypes;

	/**
	 * Apple Merchant Id.
	 *
	 * @var string
	 */
	public $appleMerchantId;

	/**
	 * Apple Merchant Cert Path.
	 *
	 * @var string
	 */
	public $appleMerchantCertPath;

	/**
	 * Apple Merchant Key Path.
	 *
	 * @var string
	 */
	public $appleMerchantKeyPath;

	/**
	 * Apple Merchant Key Passphrase.
	 *
	 * @var string
	 */
	public $appleMerchantKeyPassphrase;

	/**
	 * Apple Merchant Domain.
	 *
	 * @var string
	 */
	public $appleMerchantDomain;

	/**
	 * Apple Merchant Display Name.
	 *
	 * @var string
	 */
	public $appleMerchantDisplayName;

	/**
	 * Merchant validation endpoint.
	 *
	 * @var
	 */
	public $validateMerchantUrl;

	/**
	 * Merchant country.
	 *
	 * @var
	 */
	public $country;

	/**
	 * Apple Pay button color.
	 *
	 * @var string
	 */
	public $buttonColor;

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return array(
			'appleMerchantDisplayName' => $this->appleMerchantDisplayName,
			'ccTypes'                  => $this->ccTypes,
			'validateMerchantUrl'      => $this->validateMerchantUrl,
			'country'                  => $this->country,
			'buttonColor'              => $this->buttonColor,
		);
	}

	/**
	 * Validate merchant.
	 *
	 * @param RequestData $requestData
	 */
	public function validateMerchant(RequestData $requestData) {
		try {
			if (empty($requestData->applePayValidationUrl)) {
				throw new \Exception('Invalid Apple Pay validation Url.');
			}

			if (! $this->appleMerchantId ||
				! $this->appleMerchantCertPath ||
				! $this->appleMerchantKeyPath ||
				! $this->appleMerchantDomain ||
				! $this->appleMerchantDisplayName
			) {
				throw new \Exception('Invalid Apple Pay settings.');
			}

			$validationPayload                       = array();
			$validationPayload['merchantIdentifier'] = $this->appleMerchantId;
			$validationPayload['displayName']        = $this->appleMerchantDisplayName;
			$validationPayload['initiative']         = 'web';
			$validationPayload['initiativeContext']  = $this->appleMerchantDomain;

			// Create curl handle
			$request = curl_init();
			curl_setopt($request, CURLOPT_URL, $requestData->applePayValidationUrl);
			curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($request, CURLOPT_POST, 1);
			curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($validationPayload));
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 300);
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($request, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
			curl_setopt($request, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
			curl_setopt($request, CURLOPT_SSLCERT, $this->appleMerchantCertPath);
			curl_setopt($request, CURLOPT_SSLKEY, $this->appleMerchantKeyPath);
			if (!empty($this->appleMerchantKeyPassphrase)) {
				curl_setopt($request, CURLOPT_KEYPASSWD, $this->appleMerchantKeyPassphrase);
			}

			// Execute
			$curlResponse = curl_exec($request);

			// Check if any error occurred
			if (curl_errno($request)) {
				throw new \Exception(curl_error($request));
			}

			// Close handle
			curl_close($request);

			AbstractRequest::sendJsonResponse([
				'error'   => false,
				'message' => $curlResponse,
			]);
		} catch (\Exception $e) {
			AbstractRequest::sendJsonResponse([
				'error'   => true,
				'message' => $e->getMessage(),
			]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getMobileType() {
		return EncyptedMobileType::APPLE_PAY;
	}
}
