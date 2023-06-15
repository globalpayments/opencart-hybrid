<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\BNPLType;
use GlobalPayments\Api\Entities\Enums\EncyptedMobileType;
use GlobalPayments\Api\Entities\Enums\Environment;

class Clearpay extends AbstractBuyNowPayLater {
	public const PAYMENT_METHOD_ID = 'globalpayments_clearpay';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Clearpay';

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
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return array(
			'bnplType' => BNPLType::CLEARPAY,
		);
	}
}
