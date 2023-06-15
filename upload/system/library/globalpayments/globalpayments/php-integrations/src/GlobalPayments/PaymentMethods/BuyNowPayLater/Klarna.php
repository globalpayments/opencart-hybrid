<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\BNPLType;
use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\Api\Entities\Enums\Environment;

class Klarna extends AbstractBuyNowPayLater {
	public const PAYMENT_METHOD_ID = 'globalpayments_klarna';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Klarna';

	/**
	 * {@inheritdoc}
	 */
	public function getMethodAvailability()
	{
		return [
			'CAD' => ['CA'],
			'USD' => ['US'],
			'GBP' => ['GB'],
			'AUD' => ['AU'],
			'NZD' => ['NZ'],
			'EUR' => ['AT', 'BE', 'DE', 'ES', 'FI', 'FR', 'IT', 'NL'],
			'CHF' => ['CH'],
			'DKK' => ['DK'],
			'NOK' => ['NO'],
			'PLN' => ['PL'],
			'SEK' => ['SE'],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return array(
			'bnplType' => BNPLType::KLARNA,
		);
	}
}
