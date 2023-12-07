<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\OpenBanking;

use GlobalPayments\Api\Entities\Enums\PaymentProvider;

class FasterPayments extends AbstractOpenBanking {
	public const PAYMENT_METHOD_ID = 'globalpayments_fasterpayments';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with FasterPayments';

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
			'GBP' => $countries,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return [];
	}
}
