<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\Enums\TransactionModifier;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class AuthorizeRequest extends AbstractRequest {
	public function execute() {
		$paymentMethod                 = new CreditCardData();
		$paymentMethod->token          = $this->getPaymentToken();
		$paymentMethod->cardHolderName = $this->getCardHolderName();
		if ( ! empty($this->requestData->mobileType)) {
			$paymentMethod->mobileType = $this->requestData->mobileType;
		}

		if ( ! empty($this->requestData->threeDSecure)) {
			$paymentMethod->threeDSecure = $this->requestData->threeDSecure;
		}

		$builder = $paymentMethod->authorize($this->requestData->order->amount)
		                         ->withCurrency($this->requestData->order->currency)
		                         ->withClientTransactionId($this->requestData->order->reference)
		                         ->withDescription($this->requestData->order->description)
		                         ->withOrderId((string) $this->requestData->order->orderReference)
		                         ->withDynamicDescriptor($this->requestData->dynamicDescriptor)
		                         ->withRequestMultiUseToken($this->requestData->saveCard);

		if ($this->requestData->gatewayId == GatewayId::GOOGLE_PAY || $this->requestData->gatewayId == GatewayId::APPLE_PAY) {
			$builder = $builder->withModifier(TransactionModifier::ENCRYPTED_MOBILE);
		}

		return $builder->execute();
	}
}
