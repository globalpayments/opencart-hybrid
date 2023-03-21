<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Services\ReportingService;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class GetTransactionDetailsRequest extends AbstractRequest {
	public function execute() {
		return ReportingService::transactionDetail($this->requestData->transactionId)->execute();
	}
}
