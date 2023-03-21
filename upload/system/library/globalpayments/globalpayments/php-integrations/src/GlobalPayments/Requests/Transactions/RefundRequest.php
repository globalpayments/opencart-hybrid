<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class RefundRequest extends AbstractRequest {
	public function execute() {
		$transaction = Transaction::fromId($this->requestData->transactionId);

		return $transaction->refund($this->requestData->order->amount)
		                   ->withCurrency($this->requestData->order->currency)
		                   ->execute();
	}
}
