<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\Enums\TransactionModifier;
use GlobalPayments\Api\Entities\StoredCredential;
use GlobalPayments\Api\Entities\Enums\StoredCredentialInitiator;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class ChargeRequest extends AbstractRequest {
	public function execute() {
		$paymentMethod                 = new CreditCardData();
		$paymentMethod->token          = $this->getPaymentToken();
		$paymentMethod->cardHolderName = $this->getCardHolderName();
		$paymentTokenInfo              = $this->getPaymentTokenInfo();
		if (!empty($this->requestData->mobileType)) {
			$paymentMethod->mobileType = $this->requestData->mobileType;
		}

		if (!empty($this->requestData->threeDSecure)) {
			$paymentMethod->threeDSecure = $this->requestData->threeDSecure;
		}
		
		if (empty($this->requestData->saveCard)) {
			$this->requestData->saveCard = false;
		}

		$builder = $paymentMethod->charge($this->requestData->order->amount)
		                         ->withCurrency($this->requestData->order->currency)
		                         ->withClientTransactionId($this->requestData->order->reference)
		                         ->withDescription($this->requestData->order->description)
		                         ->withOrderId((string) $this->requestData->order->orderReference)
		                         ->withDynamicDescriptor($this->requestData->dynamicDescriptor)
		                         ->withRequestMultiUseToken((int)$this->requestData->saveCard)
		                         ->withPaymentMethodUsageMode($paymentTokenInfo['usage']);

		// Determine if this is a stored credential transaction
		$is_stored_credential = !empty($this->requestData->saveCard) || !empty($paymentTokenInfo['token']);
		if ($is_stored_credential) {
			$is_first = empty($paymentTokenInfo['token']);

			$storedCredential = new StoredCredential();
			$storedCredential->initiator = StoredCredentialInitiator::PAYER;
			$storedCredential->type = 'UNSCHEDULED';
			$storedCredential->sequence = $is_first ? 'FIRST' : 'SUBSEQUENT';

			$builder = $builder->withStoredCredential($storedCredential);
		}

		if (!empty($this->requestData->mobileType)) {
			$builder = $builder->withModifier(TransactionModifier::ENCRYPTED_MOBILE);
		}

		return $builder->execute();
	}
}
