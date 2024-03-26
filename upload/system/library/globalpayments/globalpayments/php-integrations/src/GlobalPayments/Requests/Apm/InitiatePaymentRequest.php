<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Apm;

use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\Api\PaymentMethods\AlternativePaymentMethod;
use GlobalPayments\Api\Entities\Enums\AlternativePaymentType;

class InitiatePaymentRequest extends AbstractRequest {

	public function execute() {
		$requestData = $this->requestData;

		$paymentMethod = new AlternativePaymentMethod( $requestData->meta->provider );

		$paymentMethod->descriptor        = 'ORD' . $this->requestData->order->orderReference;
		$paymentMethod->country           = $requestData->order->billingAddress['country'];
		$paymentMethod->accountHolderName = $requestData->order->customer['firstname'] . ' ' . $requestData->order->customer['lastname'];
		$paymentMethod->returnUrl         = $requestData->meta->callbackUrls['return'];
		$paymentMethod->statusUpdateUrl   = $requestData->meta->callbackUrls['status'];
		$paymentMethod->cancelUrl         = $requestData->meta->callbackUrls['cancel'];

		return $paymentMethod->{$requestData->meta->paymentAction}( $this->requestData->order->amount )
			->withCurrency( $this->requestData->order->currency )
			->withOrderId( (string) $this->requestData->order->orderReference )
			->execute();
	}
}
