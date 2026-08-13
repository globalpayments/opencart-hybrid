<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\{
	InstallmentData,
	InstallmentTerms
};
use GlobalPayments\Api\Entities\Enums\TransactionModifier;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;

class AuthorizeRequest extends AbstractRequest {
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

		$builder = $paymentMethod->authorize($this->requestData->order->amount)
		                         ->withCurrency($this->requestData->order->currency)
		                         ->withClientTransactionId($this->requestData->order->reference ?? '')
		                         ->withDescription($this->requestData->order->description ?? '')
		                         ->withOrderId((string) $this->requestData->order->orderReference)
		                         ->withDynamicDescriptor($this->requestData->dynamicDescriptor)
		                         ->withRequestMultiUseToken($this->requestData->saveCard);
		                         

		// Add installment data if present
		if (!empty($this->requestData->installments)) {
			$installmentData = new InstallmentData();
			$installmentData->id = $this->requestData->installments->id ?? null;
			$installmentData->reference = $this->requestData->installments->reference ?? null;

			if ($this->shouldUseVisaInstallments()) {
				$installmentData->program = 'VIS';

				$language = $this->requestData->installments->language ?? null;
				$version = $this->requestData->installments->version ?? null;
				if (!empty($language) && !empty($version)) {
					$installmentTerms = new InstallmentTerms();
					$installmentTerms->language = $this->normalizeInstallmentLanguage($language);
					$installmentTerms->version = $version;
					$installmentData->terms = $installmentTerms;
				}
			}

			$builder->installment = $installmentData;
		}

		if (!empty($this->requestData->mobileType)) {
			$builder = $builder->withModifier(TransactionModifier::ENCRYPTED_MOBILE);
		}

		return $builder->execute();
	}

	private function shouldUseVisaInstallments(): bool {
		if (empty($this->config['enable_visa_installments'])) {
			return false;
		}

		return Utils::isVisaInstallmentsSupported(
			$this->config['country'] ?? null,
			$this->config['currency'] ?? null
		);
	}

	private function normalizeInstallmentLanguage(string $language): string {
		$languageMapping = [
			'en' => 'eng',
			'fr' => 'fre',
		];

		$normalized = strtolower($language);

		return $languageMapping[$normalized] ?? $normalized;
	}
}
