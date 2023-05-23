<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets;

use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;

class ClickToPay extends AbstractDigitalWallet {
	public const PAYMENT_METHOD_ID = 'globalpayments_clicktopay';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Click To Pay';

	/**
	 * Refers to the merchant’s account for Click To Pay.
	 * @var string
	 */
	public $ctpClientId;

	/**
	 * Indicates the display mode of Click To Pay.
	 *
	 * @var bool
	 */
	public $buttonless;

	/**
	 * Indicates the card brands the merchant accepts for Click To Pay (allowedCardNetworks).
	 *
	 * @var
	 */
	public $ccTypes;

	/**
	 * Indicates whether Canadian Visa debit cards are accepted.
	 *
	 * @var bool
	 */
	public $canadianDebit;

	/**
	 * Indicates whether the Global Payments footer is displayed during Click To Pay.
	 *
	 * @var bool
	 */
	public $wrapper;

	/**
	 * @inheritdoc
	 */
	public function getFrontendPaymentMethodOptions() {
		try {
			return array_merge(
				$this->gateway->getFrontendGatewayOptions(),
				array(
					'apms' => array(
						'allowedCardNetworks' => $this->ccTypes,
						'clickToPay'          => array(
							'buttonless'    => $this->buttonless,
							'canadianDebit' => $this->canadianDebit,
							'cardForm'      => false,
							'ctpClientId'   => $this->ctpClientId,
							'wrapper'       => $this->wrapper,
						),
					)
				)
			);
		} catch (\Exception $e) {
			return array(
				'error'   => true,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getMobileType() {
		return EncyptedMobileType::CLICK_TO_PAY;
	}
}
