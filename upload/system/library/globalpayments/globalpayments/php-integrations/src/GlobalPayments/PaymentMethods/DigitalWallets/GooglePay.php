<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets;

use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\Api\Entities\Enums\Environment;

class GooglePay extends AbstractDigitalWallet {
	public const PAYMENT_METHOD_ID = 'globalpayments_googlepay';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Google Pay';

	/**
	 * Indicates the card brands the merchant accepts for Google Pay.
	 *
	 * @var
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
	 * Methods allowed to authenticate a card transaction
	 *
	 * @var
	 */
	public $acaMethods;

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return array(
			'env'                      => $this->gateway->isProduction ? Environment::PRODUCTION : Environment::TEST,
			'googleMerchantId'         => $this->googleMerchantId,
			'googleMerchantName'       => $this->googleMerchantName,
			'globalPaymentsMerchantId' => $this->globalPaymentsMerchantId,
			'ccTypes'                  => $this->ccTypes,
			'btnColor'                 => $this->buttonColor,
			'acaMethods'               => $this->acaMethods
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getMobileType() {
		return EncyptedMobileType::GOOGLE_PAY;
	}
}
