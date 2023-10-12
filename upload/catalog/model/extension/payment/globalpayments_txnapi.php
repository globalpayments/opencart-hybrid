<?php
include_once 'catalog/model/extension/payment/globalpayments_gateway.php';

class ModelExtensionPaymentGlobalPaymentsTxnApi extends ModelExtensionPaymentGlobalPaymentsGatewayBase {
	public function __construct($registry) {
		parent::__construct($registry,'txnapi');
	}
}
