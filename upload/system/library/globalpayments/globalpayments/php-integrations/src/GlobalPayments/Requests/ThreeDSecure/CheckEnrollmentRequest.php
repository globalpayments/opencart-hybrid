<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure;

use GlobalPayments\Api\Entities\Enums\Secure3dStatus;
use GlobalPayments\Api\Entities\Enums\Secure3dVersion;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\Services\Secure3dService;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class CheckEnrollmentRequest extends AbstractRequest {
	const NO_RESPONSE  = 'NO_RESPONSE';

	public function execute() {
		$response    = [];
		$paymentMethod = new CreditCardData();
		$paymentMethod->token = $this->getPaymentToken();

		$threeDSecureData = Secure3dService::checkEnrollment($paymentMethod)
		                                   ->withAmount($this->requestData->order->amount)
		                                   ->withCurrency($this->requestData->order->currency)
		                                   ->execute();

		$response['enrolled']             = $threeDSecureData->enrolled ?? Secure3dStatus::NOT_ENROLLED;
		$response['version']              = $threeDSecureData->getVersion();
		$response['status']               = $threeDSecureData->status;
		$response['liabilityShift']       = $threeDSecureData->liabilityShift;
		$response['serverTransactionId']  = $threeDSecureData->serverTransactionId;
		$response['sessionDataFieldName'] = $threeDSecureData->sessionDataFieldName;

		if ( Secure3dStatus::ENROLLED !== $threeDSecureData->enrolled ) {
			return $response;
		}
		if (Secure3dVersion::TWO === $threeDSecureData->getVersion()) {
			$response['methodUrl']   = $threeDSecureData->issuerAcsUrl;
			$response['methodData']  = $threeDSecureData->payerAuthenticationRequest;
			$response['messageType'] = $threeDSecureData->messageType;

			return $response;
		}
		if (Secure3dVersion::ONE === $threeDSecureData->getVersion()) {
			throw new \Exception('Please try again with another card.');
		}
		return $response;
	}
}
