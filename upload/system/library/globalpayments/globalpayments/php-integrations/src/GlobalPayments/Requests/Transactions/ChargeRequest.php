<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\Transactions;

use GlobalPayments\Api\Entities\{
	InstallmentData,
	InstallmentTerms,
	StoredCredential
};
use GlobalPayments\Api\Entities\Enums\{
	StoredCredentialType,
	TransactionModifier,
	StoredCredentialInitiator
};
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;

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
		                         ->withClientTransactionId($this->requestData->order->reference ?? '')
		                         ->withDescription($this->requestData->order->description ?? '')
		                         ->withOrderId((string) $this->requestData->order->orderReference)
		                         ->withDynamicDescriptor($this->requestData->dynamicDescriptor)
		                         ->withRequestMultiUseToken((int)$this->requestData->saveCard);

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

		// Determine if this is a stored credential transaction
		$is_stored_credential = !empty($this->requestData->saveCard) || !empty($paymentTokenInfo['token']);
		if ($is_stored_credential) {
			$is_first = empty($paymentTokenInfo['token']);
			$isInstallmentType = $this->isInstallmentType();

			$storedCredential = new StoredCredential();
			$storedCredential->initiator = StoredCredentialInitiator::PAYER;
			$storedCredential->type = $isInstallmentType ? StoredCredentialType::INSTALLMENT : StoredCredentialType::UNSCHEDULED;
			$storedCredential->sequence = $is_first ? 'FIRST' : 'SUBSEQUENT';
			if (!empty($this->requestData->contactReference)) {
				$storedCredential->contract_reference =  "CART#".$this->requestData->contactReference;
			}

			$builder = $builder->withStoredCredential($storedCredential);
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

	private function isInstallmentType(): bool {
		if (empty($this->requestData->installments)) {
			return false;
		}

		$country = strtoupper((string) ($this->config['country'] ?? ''));
		$currency = strtoupper((string) ($this->config['currency'] ?? ''));
		$isMxInstallments = $country === 'MX' && $currency === 'MXN';
		$isVisaInstallments = $this->shouldUseVisaInstallments();

		return $isMxInstallments || $isVisaInstallments;
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
