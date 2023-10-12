<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\Api\Entities\Enums\PaymentMethodUsageMode;

class VerifyRequest extends AbstractRequest {
	public function execute() {
		$paymentMethod                 = new CreditCardData();
		$paymentMethod->token          = $this->getPaymentToken();
		$paymentMethod->cardHolderName = $this->getCardHolderName();

		return $paymentMethod->verify()
		                     ->withCurrency($this->requestData->order->currency)
		                     ->withClientTransactionId($this->requestData->order->reference)
		                     ->withRequestMultiUseToken(true)
		                     ->withPaymentMethodUsageMode(PaymentMethodUsageMode::SINGLE)
		                     ->execute();
	}
}
