<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class ReverseRequest extends AbstractRequest {
	public function execute() {
		$transaction = Transaction::fromId($this->requestData->transactionId);

		return $transaction->reverse($this->requestData->order->amount)
		                   ->execute();
	}
}
