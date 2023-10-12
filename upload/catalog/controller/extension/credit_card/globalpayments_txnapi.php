<?php

include_once 'catalog/controller/extension/credit_card/globalpayments_gateway.php';

use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;

class ControllerExtensionCreditCardGlobalPaymentsTxnApi extends ControllerExtensionCreditCardGlobalPaymentsBase {
	public function __construct($registry) {
		parent::__construct($registry, 'txnapi');
		$this->globalpayments->setGateway(GatewayId::TRANSACTION_API);
	}
}
