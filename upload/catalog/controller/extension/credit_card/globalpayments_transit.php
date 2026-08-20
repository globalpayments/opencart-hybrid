<?php

include_once 'catalog/controller/extension/credit_card/globalpayments_gateway.php';

use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;

class ControllerExtensionCreditCardGlobalPaymentsTransit extends ControllerExtensionCreditCardGlobalPaymentsBase {
	public function __construct($registry) {
		parent::__construct($registry, 'transit');
		$this->globalpayments->setGateway(GatewayId::TRANSIT);
	}
}
