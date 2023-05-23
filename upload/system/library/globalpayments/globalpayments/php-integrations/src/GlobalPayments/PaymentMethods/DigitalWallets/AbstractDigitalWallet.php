<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\DigitalWallets;

use GlobalPayments\PaymentGatewayProvider\PaymentMethods\AbstractPaymentMethod;

abstract class AbstractDigitalWallet extends AbstractPaymentMethod {
	/**
	 * @return GlobalPayments\Api\Entities\Enums\EncyptedMobileType
	 */
	abstract public function getMobileType();
}
