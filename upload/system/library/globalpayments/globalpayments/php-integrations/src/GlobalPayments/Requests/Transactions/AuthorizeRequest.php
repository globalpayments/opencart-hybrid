<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\{
	InstallmentData,
	InstallmentTerms
};
use GlobalPayments\Api\Entities\Enums\{
	TransactionModifier,
	GatewayProvider,
	PaymentMethodUsageMode
};
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
		                         // Provide a stable, order-specific invoice to avoid false duplicate checks.
		                         ->withInvoiceNumber((string) $this->requestData->order->orderReference)
		                         ->withRequestMultiUseToken($this->requestData->saveCard);

		if (($this->config['gatewayProvider'] ?? null) === GatewayProvider::TRANSACTION_API) {
			if (method_exists($builder, 'withPaymentMethodUsageMode')) {
 				$builder = $builder->withPaymentMethodUsageMode($paymentTokenInfo['usage']);
 			}

			// Saved cards can be blocked by gateway duplicate checks when reused quickly.
			if (($paymentTokenInfo['usage'] ?? null) === PaymentMethodUsageMode::MULTIPLE && 
				method_exists($builder, 'withAllowDuplicates')) {
				$builder = $builder->withAllowDuplicates(true);
			}
		}

		if (is_string($this->requestData->dynamicDescriptor) && 
			trim($this->requestData->dynamicDescriptor) !== '') {
			$builder = $builder->withDynamicDescriptor($this->requestData->dynamicDescriptor);
		}
		                         

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
