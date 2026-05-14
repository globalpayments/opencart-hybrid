<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\ThreeDSecure;

use GlobalPayments\Api\Entities\Address;
use GlobalPayments\Api\Entities\BrowserData;
use GlobalPayments\Api\Entities\Enums\AddressType;
use GlobalPayments\Api\Entities\Enums\MethodUrlCompletion;
use GlobalPayments\Api\Entities\Enums\Secure3dStatus;
use GlobalPayments\Api\Entities\ThreeDSecure;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\Services\Secure3dService;
use GlobalPayments\Api\Utils\CountryUtils;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class InitiateAuthenticationRequest extends AbstractRequest {
	/**
	 * Country codes to send the state for
	 * CA: "124", US: "840"
	 *
	 * @var array
	 */
	private $countryCodes = [124, 840];

	public function execute() {

		$paymentMethod        = new CreditCardData();
		$paymentMethod->token = $this->getPaymentToken();

		$threeDSecureData                      = new ThreeDSecure();
		$threeDSecureData->serverTransactionId = $this->requestData->serverTransactionId ?? null;
		// Since we skip method step, always set to NO
		$methodUrlCompletion                   = MethodUrlCompletion::NO;

		$emailAddress = $this->requestData->order->email ?? null;

		// Ensure we always have a valid email
		if (empty($emailAddress) || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			$emailAddress = 'customer@example.com';
		}

		$threeDSecureData = Secure3dService::initiateAuthentication($paymentMethod, $threeDSecureData)
		                                   ->withAmount($this->requestData->order->amount)
		                                   ->withCurrency($this->requestData->order->currency)
		                                   ->withOrderCreateDate(date('Y-m-d H:i:s'))
		                                   ->withAddress($this->mapAddress($this->requestData->order->billingAddress ?? null), AddressType::BILLING)
		                                   ->withAddress($this->mapAddress($this->requestData->order->shippingAddress ?? null), AddressType::SHIPPING)
		                                   ->withAddressMatchIndicator($this->requestData->order->addressMatchIndicator)
		                                   ->withCustomerEmail($emailAddress)
		                                   ->withAuthenticationSource($this->requestData->authenticationSource ?? 'BROWSER')
		                                   ->withAuthenticationRequestType($this->requestData->authenticationRequestType ?? 'PAYMENT_TRANSACTION')
		                                   ->withMessageCategory($this->requestData->messageCategory ?? 'PAYMENT_AUTHENTICATION')
		                                   ->withChallengeRequestIndicator($this->requestData->challengeRequestIndicator ?? 'NO_PREFERENCE')
		                                   ->withBrowserData($this->getBrowserData())
		                                   ->withMethodUrlCompletion($methodUrlCompletion)
		                                   ->execute();

		$response['liabilityShift'] = $threeDSecureData->liabilityShift;
		// frictionless flow
		if ( $threeDSecureData->status !== Secure3dStatus::CHALLENGE_REQUIRED ) {
			$response['result']              = $threeDSecureData->status;
			$response['authenticationValue'] = $threeDSecureData->authenticationValue;
			$response['serverTransactionId'] = $threeDSecureData->serverTransactionId;
			$response['messageVersion']      = $threeDSecureData->messageVersion;
			$response['eci']                 = $threeDSecureData->eci;

		} else { //challenge flow
			$response['status']                               = $threeDSecureData->status;
			$response['challengeMandated']                    = $threeDSecureData->challengeMandated;
			$response['challenge']['requestUrl']              = $threeDSecureData->issuerAcsUrl;
			$response['challenge']['encodedChallengeRequest'] = $threeDSecureData->payerAuthenticationRequest;
			$response['challenge']['messageType']             = $threeDSecureData->messageType;
		}

		return $response;
	}

	private function getBrowserData() {
		$browserData                     = new BrowserData();
		$rawBrowserData                  = isset($this->requestData->browserData) && is_object($this->requestData->browserData)
			? $this->requestData->browserData
			: null;
		$challengeWindow                 = isset($this->requestData->challengeWindow) && is_object($this->requestData->challengeWindow)
			? $this->requestData->challengeWindow
			: null;
		$browserData->acceptHeader       = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
		$browserData->colorDepth         = isset($rawBrowserData->colorDepth) ? $rawBrowserData->colorDepth : 24;
		$browserData->ipAddress          = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$browserData->javaEnabled        = isset($rawBrowserData->javaEnabled) ? $rawBrowserData->javaEnabled : false;
		$browserData->javaScriptEnabled  = isset($rawBrowserData->javascriptEnabled) ? $rawBrowserData->javascriptEnabled : true;
		$browserData->language           = isset($rawBrowserData->language) ? $rawBrowserData->language : 'en-US';
		$browserData->screenHeight       = isset($rawBrowserData->screenHeight) ? $rawBrowserData->screenHeight : 1080;
		$browserData->screenWidth        = isset($rawBrowserData->screenWidth) ? $rawBrowserData->screenWidth : 1920;
		$browserData->challengWindowSize = isset($challengeWindow->windowSize) ? $challengeWindow->windowSize : 'WINDOWED_500X600';
		$browserData->timeZone           = isset($rawBrowserData->timezoneOffset) ? $rawBrowserData->timezoneOffset : 0;
		$browserData->userAgent          = isset($rawBrowserData->userAgent)
			? $rawBrowserData->userAgent
			: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (compatible)');

		return $browserData;
	}

	private function mapAddress($addressData) {

		$address              = new Address();
		// Handle null or empty address data
		if (empty($addressData) || !is_object($addressData)) {
			// Set minimal required fields for 3DS
			$address->countryCode = 840; // Default to US
			$address->streetAddress1 = 'N/A';
			$address->city = 'N/A';
			$address->postalCode = '00000';
			return $address;
		}
		$address->countryCode = CountryUtils::getNumericCodeByCountry($addressData->country ?? 'US');

		foreach ($addressData as $key => $value) {
			if (property_exists($address, $key) && ! empty($value)) {
				if ( 'state' == $key && ! in_array($address->countryCode, $this->countryCodes)) {
					continue;
				}
				$address->{$key} = $value;
			}
		};

		return $address;
	}
}
