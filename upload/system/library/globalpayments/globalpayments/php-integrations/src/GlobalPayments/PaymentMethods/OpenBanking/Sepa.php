<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\OpenBanking;

use GlobalPayments\Api\Entities\Enums\PaymentProvider;

class Sepa extends AbstractOpenBanking {
	public const PAYMENT_METHOD_ID = 'globalpayments_sepa';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with Sepa';

	/**
	 * {@inheritdoc}
	 */
	public function getMethodAvailability()
	{
		$countries = [];
		if (!empty($this->countries)) {
			$countries = explode("|", $this->countries);
		}

		return [
			'EUR' => $countries,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return [];
	}
}
