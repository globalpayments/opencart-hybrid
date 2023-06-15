<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\BNPLType;
use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\Api\Entities\Enums\Environment;

class Affirm extends AbstractBuyNowPayLater {
	public const PAYMENT_METHOD_ID = 'globalpayments_affirm';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Affirm';

	/**
	 * {@inheritdoc}
	 */
	public function getMethodAvailability()
	{
		return [
			'USD' => ['US'],
			'CAD' => ['CA'],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return array(
			'bnplType' => BNPLType::AFFIRM,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isShippingRequired() {
		return true;
	}
}
