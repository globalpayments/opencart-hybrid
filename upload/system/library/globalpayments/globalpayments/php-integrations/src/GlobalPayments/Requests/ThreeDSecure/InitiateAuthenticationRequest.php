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
		$threeDSecureData->serverTransactionId = $this->requestData->serverTransactionId;
		$methodUrlCompletion                   = MethodUrlCompletion::YES;

		$threeDSecureData = Secure3dService::initiateAuthentication($paymentMethod, $threeDSecureData)
		                                   ->withAmount($this->requestData->order->amount)
		                                   ->withCurrency($this->requestData->order->currency)
		                                   ->withOrderCreateDate(date('Y-m-d H:i:s'))
		                                   ->withAddress($this->mapAddress($this->requestData->order->billingAddress), AddressType::BILLING)
		                                   ->withAddress($this->mapAddress($this->requestData->order->shippingAddress), AddressType::SHIPPING)
		                                   ->withAddressMatchIndicator($this->requestData->order->addressMatchIndicator)
		                                   //->withCustomerEmail($this->requestData->order->email)
		                                   ->withAuthenticationSource($this->requestData->authenticationSource)
		                                   ->withAuthenticationRequestType($this->requestData->authenticationRequestType)
		                                   ->withMessageCategory($this->requestData->messageCategory)
		                                   ->withChallengeRequestIndicator($this->requestData->challengeRequestIndicator)
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
		$browserData->acceptHeader       = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
		$browserData->colorDepth         = $this->requestData->browserData->colorDepth;
		$browserData->ipAddress          = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$browserData->javaEnabled        = $this->requestData->browserData->javaEnabled ?? false;
		$browserData->javaScriptEnabled  = $this->requestData->browserData->javascriptEnabled;
		$browserData->language           = $this->requestData->browserData->language;
		$browserData->screenHeight       = $this->requestData->browserData->screenHeight;
		$browserData->screenWidth        = $this->requestData->browserData->screenWidth;
		$browserData->challengWindowSize = $this->requestData->challengeWindow->windowSize;
		$browserData->timeZone           = $this->requestData->browserData->timezoneOffset;
		$browserData->userAgent          = $this->requestData->browserData->userAgent;

		return $browserData;
	}

	private function mapAddress($addressData) {
		$address              = new Address();
		$address->countryCode = CountryUtils::getNumericCodeByCountry($addressData->country);

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
