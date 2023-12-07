<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\OpenBanking;

use GlobalPayments\Api\Entities\Address;
use GlobalPayments\Api\Entities\CustomerDocument;
use GlobalPayments\Api\Entities\Enums\AddressType;
use GlobalPayments\Api\Entities\Enums\CustomerDocumentType;
use GlobalPayments\Api\Entities\Enums\PhoneNumberType;
use GlobalPayments\Api\Entities\Enums\RemittanceReferenceType;
use GlobalPayments\Api\Entities\Enums\TransactionModifier;
use GlobalPayments\Api\Entities\PhoneNumber;
use GlobalPayments\Api\PaymentMethods\BankPayment;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\Utils\CountryUtils;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\Api\PaymentMethods\BNPL;
use GlobalPayments\Api\Entities\Enums\BNPLShippingMethod;
use GlobalPayments\Api\Entities\Enums\BNPLType;
use GlobalPayments\Api\Entities\Customer;
use GlobalPayments\Api\Entities\Product;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;

class InitiatePaymentRequest extends AbstractRequest {

	public function execute() {
		$requestData = $this->requestData;

		$paymentMethod = new BankPayment();

		if (!empty($requestData->openBanking->accountNumber)) {
			$paymentMethod->accountNumber = $requestData->openBanking->accountNumber;
		}
		if (!empty($requestData->openBanking->iban)) {
			$paymentMethod->iban          = $requestData->openBanking->iban;
		}
		if (!empty($requestData->openBanking->sortCode)) {
			$paymentMethod->sortCode     = $requestData->openBanking->sortCode;
		}
		if (!empty($requestData->openBanking->countries)) {
			$paymentMethod->countries    = explode("|", $requestData->openBanking->countries);
		}

		$paymentMethod->accountName      = $requestData->openBanking->accountName;
		$paymentMethod->returnUrl        = $requestData->openBanking->callbackUrls['return'];
		$paymentMethod->statusUpdateUrl  = $requestData->openBanking->callbackUrls['status'];
		$paymentMethod->cancelUrl        = $requestData->openBanking->callbackUrls['cancel'];

		$builder = $paymentMethod->charge($this->requestData->order->amount)
					 ->withCurrency($this->requestData->order->currency)
					 ->withOrderId((string) $this->requestData->order->orderReference)
					 ->withRemittanceReference(RemittanceReferenceType::TEXT, 'ORD' . $this->requestData->order->orderReference)
					 ->execute();

		return $builder;
	}
}
