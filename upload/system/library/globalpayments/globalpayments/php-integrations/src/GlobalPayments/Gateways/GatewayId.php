<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

abstract class GatewayId {
	public const GP_API = 'globalpayments_ucp';
	public const GOOGLE_PAY = 'globalpayments_googlepay';
	public const APPLE_PAY = 'globalpayments_applepay';
}
