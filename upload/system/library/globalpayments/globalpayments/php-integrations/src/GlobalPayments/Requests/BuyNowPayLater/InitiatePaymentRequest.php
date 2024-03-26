<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\BuyNowPayLater;

use GlobalPayments\Api\Entities\Address;
use GlobalPayments\Api\Entities\CustomerDocument;
use GlobalPayments\Api\Entities\Enums\AddressType;
use GlobalPayments\Api\Entities\Enums\CustomerDocumentType;
use GlobalPayments\Api\Entities\Enums\PhoneNumberType;
use GlobalPayments\Api\Entities\Enums\TransactionModifier;
use GlobalPayments\Api\Entities\PhoneNumber;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\Utils\CountryUtils;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\Api\PaymentMethods\BNPL;
use GlobalPayments\Api\Entities\Enums\BNPLShippingMethod;
use GlobalPayments\Api\Entities\Enums\BNPLType;
use GlobalPayments\Api\Entities\Product;
use GlobalPayments\Api\Entities\Customer;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;

class InitiatePaymentRequest extends AbstractRequest {
	private $countryCodes = [ 'CA', 'US' ];

	public function execute() {
		$requestData = $this->requestData;

		$paymentMethod = new BNPL($requestData->meta->type);
		$paymentMethod->returnUrl       = $requestData->meta->callbackUrls['return'];
		$paymentMethod->statusUpdateUrl = $requestData->meta->callbackUrls['status'];
		$paymentMethod->cancelUrl       = $requestData->meta->callbackUrls['cancel'];

		$shippingMethod = $this->getShippingMethod();
		$billingAddress = $this->mapAddress($this->requestData->order->billingAddress);

		if ($shippingMethod == BNPLShippingMethod::EMAIL) {
			$shippingAddress = $billingAddress;
		} else {
			$shippingAddress = $this->mapAddress($this->requestData->order->shippingAddress);
		}

		$builder = $paymentMethod
			->authorize($this->requestData->order->amount)
			->withCurrency($this->requestData->order->currency)
			->withOrderId((string) $this->requestData->order->orderReference)
			->withProductData($this->getProductsData())
			->withAddress($shippingAddress, AddressType::SHIPPING)
			->withAddress($billingAddress, AddressType::BILLING)
			->withCustomerData($this->getCustomerData())
			->withBNPLShippingMethod($shippingMethod)
			->execute();

		return $builder;
	}

	private function getProductsData()
    {
        $order = $this->requestData->order;

        $product = new Product();
        $product->productId = $order->orderReference;
        $product->productName = $order->textProductInitiateOrder;
        $product->description = $product->productName;
        $product->quantity = 1;
        $product->unitPrice = $order->amount;
        $product->netUnitPrice = $product->unitPrice;
        $product->taxAmount = 0;
        //$product->discountAmount = 0;
        //$product->taxPercentage = 0;
        $product->url = $order->storeUrl;
        $product->imageUrl = HTTP_SERVER . 'product.png';

        return [$product];
    }

    private function getCustomerData()
    {
        $order = $this->requestData->order;

        $customer = new Customer();
        $customer->id = $order->customer['id'];
        $customer->firstName = Utils::sanitizeString($order->customer['firstname'], true);
        $customer->lastName = Utils::sanitizeString($order->customer['lastname'], true);
        $customer->email = $order->customer['email'];
        $phoneCode = CountryUtils::getPhoneCodesByCountry($order->paymentCountry);
        $customer->phone = new PhoneNumber($phoneCode[0], $order->customer['telephone'], PhoneNumberType::HOME);

        return $customer;
    }

    private function mapAddress($addressData) {
        $address = new Address();
        $address->streetAddress1 = Utils::sanitizeString($addressData['streetAddress1']);
        $address->streetAddress2 = Utils::sanitizeString($addressData['streetAddress2']);
        $address->city = $addressData['city'];
        $address->state = $addressData['state'];
        $address->postalCode = $addressData['postalCode'];
        $address->country = $addressData['country'];
        if (!in_array($address->country, $this->countryCodes)) {
            $address->state = $address->country;
        }
		return $address;
	}

	private function getShippingMethod() {
		$orderItems = $this->requestData->order->cart;
		$needsShipping = false;
		$hasVirtualItems = false;
		foreach ($orderItems as $item) {
			if ($item['shipping']) {
				$needsShipping = true;
			} else {
				$hasVirtualItems = true;
			}
		}
		if ($needsShipping && $hasVirtualItems) {
			return BNPLShippingMethod::COLLECTION;
		}
		if ($needsShipping) {
			return BNPLShippingMethod::DELIVERY;
		}

		return BNPLShippingMethod::EMAIL;
	}
}
