<?php

include_once 'catalog/controller/extension/credit_card/globalpayments_gateway.php';

use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;

class ControllerExtensionCreditCardGlobalPaymentsGenius extends ControllerExtensionCreditCardGlobalPaymentsBase {
	public function __construct($registry) {
		parent::__construct($registry, 'genius');
		$this->globalpayments->setGateway(GatewayId::GENIUS);
	}
}
