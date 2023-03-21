<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure;

use GlobalPayments\Api\Entities\Enums\Secure3dStatus;
use GlobalPayments\Api\Services\Secure3dService;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class GetAuthenticationDataRequest extends AbstractRequest {
	const YES = 'YES';

	/**
	 * Authentication statuses
	 */
	public $threeDSecureAuthenticationStatus = array(
		Secure3dStatus::NOT_ENROLLED,
		Secure3dStatus::SUCCESS_AUTHENTICATED,
		Secure3dStatus::SUCCESS_ATTEMPT_MADE,
	);

	public function execute() {
		return Secure3dService::getAuthenticationData()
		                      ->withServerTransactionId($this->requestData->serverTransactionId)
		                      ->execute();
	}
}
