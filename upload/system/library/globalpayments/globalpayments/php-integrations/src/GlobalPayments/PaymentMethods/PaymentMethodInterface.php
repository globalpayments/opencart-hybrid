<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods;

interface PaymentMethodInterface {
	public function getFrontendPaymentMethodOptions();
}
