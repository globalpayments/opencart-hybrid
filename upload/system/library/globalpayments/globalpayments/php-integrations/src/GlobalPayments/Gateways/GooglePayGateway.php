<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\GatewayProvider;

class GooglePayGateway extends AbstractGateway {
	/**
	 * First line support e-mail.
	 */
	public const FIRST_LINE_SUPPORT_EMAIL = 'api.integrations@globalpay.com';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $gatewayId = GatewayId::GOOGLE_PAY;

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
	public $isDigitalWallet = true;

	/**
	 * Digital Wallet provider.
	 *
	 * @var string
	 */
	public $mobileType = EncyptedMobileType::GOOGLE_PAY;

	/**
	 * Supported credit card types.
	 *
	 * @var array
	 */
	public $ccTypes;

	/**
	 * Global Payments Merchant Id.
	 *
	 * @var string
	 */
	public $globalPaymentsMerchantId;

	/**
	 * Google Merchant ID.
	 *
	 * @var string
	 */
	public $googleMerchantId;

	/**
	 * Google Merchant Name.
	 *
	 * @var string
	 */
	public $googleMerchantName;

	/**
	 * Google Pay button color.
	 *
	 * @var string
	 */
	public $buttonColor;

	/**
	 * Global Payments gateway.
	 *
	 * @var GlobalPayments\PaymentGatewayProvider\Gateways\GatewayInterface
	 */
	private $gateway;

	public function __construct(GatewayInterface $gateway) {
		$this->gateway = $gateway;
		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array|Array
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ApiException
	 */
	public function getFrontendGatewayOptions() {
		return array(
			'env'                      => $this->gateway->isProduction ? Environment::PRODUCTION : Environment::TEST,
			'googleMerchantId'         => $this->googleMerchantId,
			'googleMerchantName'       => $this->googleMerchantName,
			'globalPaymentsMerchantId' => $this->globalPaymentsMerchantId,
			'ccTypes'                  => $this->ccTypes,
			'btnColor'                 => $this->buttonColor,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array|Array
	 */
	public function getBackendGatewayOptions() {
		return $this->gateway->getBackendGatewayOptions();
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
	 * The configuration for the globalpayments_secure_payment_fields_params object.
	 *
	 * @param bool $jsonEncode
	 *
	 * @return array|false|string
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ApiException
	 */
	public function paymentFieldsParams( $jsonEncode = true ) {
		$params = array(
			'id'             => $this->gatewayId,
			'gatewayOptions' => $this->getFrontendGatewayOptions(),
		);

		return $jsonEncode ? json_encode( $params ) : $params;
	}
}
