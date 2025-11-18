<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

// Requiremets for HPP
use GlobalPayments\Api\Builders\HPPBuilder;
use GlobalPayments\Api\Entities\{Address, PayerDetails, PhoneNumber, Transaction};
use GlobalPayments\Api\Entities\Enums\{
    AddressType,
    CaptureMode,
    ChallengeRequestIndicator,
    Channel,
    Environment,
    ExemptStatus,
    HPPStorageModes,
    PhoneNumberType
};
use GlobalPayments\Api\PaymentMethods\TransactionReference;
use GlobalPayments\Api\ServiceConfigs\Gateways\GpApiConfig;
use GlobalPayments\Api\Services\HostedPaymentPageService;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\CountryUtils;

class ControllerExtensionPaymentGlobalPaymentsUcp extends Controller {
	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(\GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId::GP_API);
	}

	public function index() {
		$this->load->language('extension/payment/globalpayments_ucp');

		$this->setOrder();
		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['action'] = $this->url->link('extension/payment/globalpayments_ucp/confirm', '', true);

		$data['gateway'] = $this->globalpayments->gateway;

		$data['hpp_link'] = $this->buildHPP();

		$data['payment_tab_option'] = 'new';
		if ($this->customer->isLogged()) {
			$data['customer_is_logged'] = true;
			$this->load->model('extension/payment/globalpayments_ucp');
			$data['stored_payment_methods'] = $this->model_extension_payment_globalpayments_ucp->getCards(
				$this->customer->getId(),
				$this->globalpayments->gateway->gatewayId
			);
			if (!empty($data['stored_payment_methods']) && in_array(1, array_column($data['stored_payment_methods'], 'is_default'))) {
				$data['payment_tab_option'] = 'saved';
			}
		} else {
			$data['customer_is_logged']     = false;
			$data['stored_payment_methods'] = null;
		}

		// Get store currency and country
		$store_currency = $this->config->get('config_currency');
		$store_country_id = $this->config->get('config_country_id');

		// Get country ISO code from country ID
		$this->load->model('localisation/country');
		$country_info = $this->model_localisation_country->getCountry($store_country_id);
		$store_country_iso = isset($country_info['iso_code_2']) ? $country_info['iso_code_2'] : '';

		// Set base country and currency in the gateway before getting secure payment params
		$this->globalpayments->gateway->baseCountry = $store_country_iso;
		$this->globalpayments->gateway->baseCurrency = $store_currency;

		$data['base_currency'] = $store_currency;
		$data['base_country']  = $store_country_iso;

		$data['environment_indicator']                             = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields']                             = $this->globalpayments->gateway->getCreditCardFormatFields();
		$data['globalpayments_secure_payment_fields_params']       = $this->globalpayments->gateway->securePaymentFieldsParams();
		$data['globalpayments_secure_payment_threedsecure_params'] = $this->globalpayments->gateway->securePaymentFieldsThreeDSecureParams($this->order);

		return $this->load->view('extension/payment/globalpayments_ucp', $data);
	}

	public function threeDSecureCheckEnrollment() {
		$postRequestData = AbstractRequest::getPostRequestData();

		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);

		if ( ! empty($postRequestData->paymentTokenId) && 'new' !== $postRequestData->paymentTokenId) {
			$this->load->model('extension/payment/globalpayments_ucp');
			$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
		}

		$this->globalpayments->gateway->threeDSecureCheckEnrollment($requestData);
	}

	public function threeDSecureMethodNotification() {
		$this->globalpayments->gateway->threeDSecureMethodNotification();
	}

	public function threeDSecureInitiateAuthentication() {
		$postRequestData = AbstractRequest::getPostRequestData();

		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);

		if ( ! empty($postRequestData->paymentTokenId) && 'new' !== $postRequestData->paymentTokenId) {
			$this->load->model('extension/payment/globalpayments_ucp');
			$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
		}

		$this->globalpayments->gateway->threeDSecureInitiateAuthentication($requestData);
	}

	public function threeDSecureChallengeNotification() {
		$this->globalpayments->gateway->threeDSecureChallengeNotification();
	}

	public function confirm() {
		$this->load->language( 'extension/payment/globalpayments_ucp' );
		try {
			$this->setOrder();
			if (empty($this->request->post[$this->globalpayments->gateway->gatewayId])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}

			$postRequestData                   = (object)$this->request->post[$this->globalpayments->gateway->gatewayId];
			$requestData                       = new RequestData();
			$requestData                       = RequestData::setDataObject($requestData, $postRequestData);
			$requestData->paymentTokenResponse = ! empty($postRequestData->paymentTokenResponse) ? htmlspecialchars_decode($postRequestData->paymentTokenResponse) : null;
			$requestData->dynamicDescriptor    = $this->config->get('payment_globalpayments_ucp_txn_descriptor');
			$requestData->order                = $this->order;
			$requestData->meta                 = (object) [
				'shared_text' => $this->load->language('extension/payment/globalpayments_shared_text'),
			];

			if (isset($postRequestData->paymentType)
			    && 'saved' === $postRequestData->paymentType
			    && isset($postRequestData->paymentTokenId)
			    && 'new' !== $postRequestData->paymentTokenId) {
				$this->load->model('extension/payment/globalpayments_ucp');
				$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
			}
			$requestData->requestType           = AbstractGateway::getRequestType($this->globalpayments->gateway->paymentAction);

			$gatewayResponse = $this->globalpayments->gateway->processPayment($requestData);

			$this->load->model('checkout/order');
			$comment = [
				$this->language->get('text_comment_txn_id') . ' ' . $gatewayResponse->transactionReference->transactionId,
				$this->language->get('text_comment_response_code') . ' ' . $gatewayResponse->responseCode,
				$this->language->get('text_comment_response_status') . ' ' . $gatewayResponse->responseMessage,
				$this->language->get('text_comment_amount') . ' ' . $this->order->amount,
				$this->language->get('text_comment_currency') . ' ' . $this->order->currency,
				$this->language->get('text_comment_pmt_method') . ' ' . $gatewayResponse->cardType . ' ' . $gatewayResponse->cardLast4,
			];
			$comment = implode('<br/>', $comment);
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 2, $comment);

			$this->load->model('extension/payment/globalpayments_ucp');
			$this->model_extension_payment_globalpayments_ucp->addTransaction(
				$this->order->orderReference,
				$this->globalpayments->gateway->gatewayId,
				$this->globalpayments->gateway->paymentAction,
				$this->order->amount,
				$this->order->currency,
				$gatewayResponse
			);

			//succesfull response, store payment method
			if (isset($postRequestData->paymentType) && 'new' === $postRequestData->paymentType && $requestData->saveCard) {
				$payment_token = json_decode($requestData->paymentTokenResponse);

				// Duplicate card check
				$existing_cards = $this->model_extension_payment_globalpayments_ucp->getCards(
					$this->customer->getId(),
					$this->globalpayments->gateway->gatewayId
				);
				$already_saved = false;
				foreach ($existing_cards as $card) {
					if (
						isset($card['card_last4'], $card['expiry_month'], $card['expiry_year']) &&
						isset($payment_token->details->cardLast4, $payment_token->details->expiryMonth, $payment_token->details->expiryYear) &&
						(string)$card['card_last4'] === (string)$payment_token->details->cardLast4 &&
						(string)$card['expiry_month'] === (string)$payment_token->details->expiryMonth &&
						(string)$card['expiry_year'] === (string)$payment_token->details->expiryYear
					) {
						$already_saved = true;
						break;
					}
				}

				if (!$already_saved) {
					$this->model_extension_payment_globalpayments_ucp->addCard(
						$this->globalpayments->gateway->gatewayId,
						$this->customer->getId(),
						$gatewayResponse->token,
						strtoupper($payment_token->details->cardType),
						$payment_token->details->cardLast4,
						$payment_token->details->expiryYear,
						$payment_token->details->expiryMonth
					);
				}
			}

			$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link( 'checkout/checkout', '', true));
		}
	}

	public function confirmHosted(){

		$validation_result = $this->validate_request($_POST['gateway_response'] ?? '', $_POST['X-GP-Signature'] ?? false);

		if (!$validation_result) {
			http_response_code(403);
			die("Invalid Request");
		}
		$payment_data = json_decode($_POST['gateway_response'] ?? '{}', true);

		try{
			$this->session->data['order_id'] = $payment_data['link_data']['name'];
			$this->setOrder();
			$this->load->model('checkout/order');
			$comment = [
				$this->language->get('text_comment_txn_id') . ' ' . $payment_data['id'],
				$this->language->get('text_comment_auth_code') . ' ' . $payment_data['action']['result_code'],
				$this->language->get('text_comment_reference_no') . ' ' . substr($payment_data['link_data']['url'], strpos($payment_data['link_data']['url'], 'redirect/') + 9),
				$this->language->get('text_comment_response_status') . ' ' . $payment_data['status'],
				$this->language->get('text_comment_amount') . ' ' . $this->order->amount,
				$this->language->get('text_comment_currency') . ' ' . $this->order->currency,
			];
			$comment = implode('<br/>', $comment);

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 2, $comment);

			// create a new transaciton object for payment model_checkout_order

			$gatewayResponse = new Transaction();
			$gatewayResponse->transactionReference = new TransactionReference();

			$gatewayResponse->transactionReference->transactionId = $payment_data['id'];
			$gatewayResponse->responseCode = $payment_data['action']['result_code'];
			$gatewayResponse->responseMessage = $payment_data['status'];
			$gatewayResponse->transactionReference->clientTransactionId = substr($payment_data['link_data']['url'], strpos($payment_data['link_data']['url'], 'redirect/') + 9);
			$gatewayResponse->timestamp = $payment_data['time_created'];

			$this->load->model('extension/payment/globalpayments_ucp');
			$this->model_extension_payment_globalpayments_ucp->addTransaction(
				$this->order->orderReference,
				$this->globalpayments->gateway->gatewayId,
				$this->globalpayments->gateway->paymentAction,
				$this->order->amount,
				$this->order->currency,
				$gatewayResponse
			);

			if ($payment_data['action']['result_code'] == 'SUCCESS'){
				$this->response->redirect($this->url->link('checkout/success', ['order_id' => $this->session->data['order_id']], true));
			} else {
				$this->log->write($payment_data['id'] . " " . $payment_data['status']);
				$this->log->write($payment_data['payment_method']['message']);
				$this->response->redirect($this->url->link( 'checkout/failure', '', true));
			}
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->log->write($this->session->data['error']);
			$this->response->redirect($this->url->link( 'checkout/failure', '', true));
		}
	}


	private function setOrder() {
		if (empty($this->session->data['order_id'])) {
			throw new \Exception($this->language->get('error_order_processing'));
		}

		$this->load->model( 'checkout/order' );
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order                 = new OrderData();
		$order->amount         = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$order->currency       = $order_info['currency_code'];
		$order->orderReference = $this->session->data['order_id'];

		$order->customerEmail = $order_info['email'];

		$order->billingAddress = array(
			'streetAddress1' => $order_info['payment_address_1'],
			'streetAddress2' => $order_info['payment_address_2'],
			'city'           => $order_info['payment_city'],
			'state'          => $order_info['payment_zone_code'],
			'postalCode'     => $order_info['payment_postcode'],
			'country'        => $order_info['payment_iso_code_2'],
		);

		$order->shippingAddress = array(
			'streetAddress1' => $order_info['shipping_address_1'],
			'streetAddress2' => $order_info['shipping_address_2'],
			'city'           => $order_info['shipping_city'],
			'state'          => $order_info['shipping_zone_code'],
			'postalCode'     => $order_info['shipping_postcode'],
			'country'        => $order_info['shipping_iso_code_2'],
		);

		$order->addressMatchIndicator = $order->billingAddress == $order->shippingAddress;

		$this->order = $order;
	}

	function validate_request($gateway_response_json, $GP_signature) {
		if (!$gateway_response_json || !$GP_signature) {
			return false;
		}

		$parsed_data = json_decode($gateway_response_json, true);
		if (!$parsed_data) {
			error_log("Failed to parse gateway response JSON");
		return false;
		}

		if(isset($gateway_response_json['X-GP-Signature'])) {
			unset($gateway_response_json['X-GP-Signature']);
		}
		$minified_input = json_encode($parsed_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return hash("sha512", $minified_input . "ALiN55xYMwbVsCla") === $GP_signature;
	}


	public function buildHPP() {

		$uri = str_replace("index.php", "", $_SERVER['SCRIPT_URI']) . "catalog/controller/extension/payment/";

		try{
			$config = new GpApiConfig();

			if ($this->globalpayments->gateway->isProduction == 1){
				$config->appId = $this->globalpayments->gateway->appId;
				$config->appKey = $this->globalpayments->gateway->appKey;
				$config->environment = Environment::PRODUCTION;
			} else {
				$config->appId = $this->globalpayments->gateway->sandboxAppId;
				$config->appKey = $this->globalpayments->gateway->sandboxAppKey;
				$config->environment = Environment::TEST;
			}

			$config->country = $this->globalpayments->gateway->country;
			$config->channel = Channel::CardNotPresent;

			ServicesContainer::configureService($config);

			$this->load->model( 'checkout/order' );
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			// Classes for HPPBuilder
			$payerDetails = new PayerDetails();
			$payerDetails->firstName = $order_info['firstname'];
			$payerDetails->lastName = $order_info['lastname'];
			$payerDetails->email = $order_info['email'];
			$payerDetails->status = 'ACTIVE';

			$phoneNumber = new PhoneNumber(CountryUtils::getPhoneCodesByCountry(
        $order_info['payment_iso_code_2'])[0], $order_info['telephone'], PhoneNumberType::HOME);

			$billingAddress = New Address();
			$billingAddress->__set("type", AddressType::BILLING);
			$billingAddress->__set("streetAddress1", $order_info['payment_address_1']);
			$billingAddress->__set("streetAddress2", $order_info['payment_address_2']);
			$billingAddress->__set("city", $order_info['payment_city']);
			$billingAddress->__set("state", substr($order_info['payment_zone_code'], 0, 3));
			$billingAddress->__set("postalCode", $order_info['payment_postcode']);
			$billingAddress->__set("country", $order_info['payment_country']);
			$billingAddress->__set("countryCode", $order_info['payment_iso_code_2']);

			$shippingAddress = New Address();
			$shippingAddress->__set("type", AddressType::SHIPPING);
			$shippingAddress->__set("streetAddress1", $order_info['shipping_address_1']);
			$shippingAddress->__set("streetAddress2", $order_info['shipping_address_2']);
			$shippingAddress->__set("city", $order_info['shipping_city']);
			$shippingAddress->__set("state", substr($order_info['shipping_zone_code'], 0, 3));
			$shippingAddress->__set("postalCode", $order_info['shipping_postcode']);
			$shippingAddress->__set("country", $order_info['shipping_iso_code_2']);
			$shippingAddress->__set("countryCode", $order_info['shipping_iso_code_2']);

			$payerDetails->billingAddress = $billingAddress;
			$payerDetails->shippingAddress = $shippingAddress;

			if ($this->currency->convert($order_info['total'], 'EUR', $order_info['currency_code']) > 30){
				$ecommercePayment = HPPBuilder::create()
						->withName($this->session->data['order_id'])
						->withReference('ORDER-' . time())
						->withExpirationDate((new DateTime('+7 days'))->format('Y-m-d\TH:i:s\Z'))
						->withPayer($payerDetails)
						->withPayerPhone($phoneNumber)
						->withBillingAddress($billingAddress)
						->withShippingAddress($shippingAddress)
						->withShippingPhone($phoneNumber)
						->withAmount($this->order->amount, $this->order->currency)
						->withOrderReference($this->session->data['order_id'] . '-' . time())
						->withTransactionConfig(Channel::CardNotPresent, 'GB', CaptureMode::AUTO)
						->withApm(true, true)
						->withPaymentMethodConfig(HPPStorageModes::PROMPT)
						->withNotifications($uri . 'globalpayments_return_url.php', $uri . 'globalpayments_status_url.php', $uri . 'globalpayments_cancel_url.php')
						->withCurrency($order_info['currency_code'])
						->withAddressMatchIndicator(false)
						->withDigitalWallets(["googlepay", "applepay"])

						->execute();
			} else {
				$ecommercePayment = HPPBuilder::create()
						->withName($this->session->data['order_id'])
						->withReference('ORDER-' . time())
						->withExpirationDate((new DateTime('+7 days'))->format('Y-m-d\TH:i:s\Z'))
						->withPayer($payerDetails)
						->withPayerPhone($phoneNumber)
						->withBillingAddress($billingAddress)
						->withShippingAddress($shippingAddress)
						->withShippingPhone($phoneNumber)
						->withAmount($this->order->amount, $this->order->currency)
						->withOrderReference($this->session->data['order_id'] . '-' . time())
						->withTransactionConfig(Channel::CardNotPresent, 'GB', CaptureMode::AUTO)
						->withApm('true', 'true')
						->withPaymentMethodConfig(HPPStorageModes::PROMPT)
						->withNotifications($uri . 'return_url.php', $uri . 'status_url.php', $uri . 'cancel_url.php')
						->withCurrency($order_info['currency_code'])
						->withAddressMatchIndicator(false)
						->withAuthentication(ChallengeRequestIndicator::CHALLENGE_PREFERRED,ExemptStatus::LOW_VALUE, true)
						->withDigitalWallets(["googlepay", "applepay"])

						->execute();
			}

			return $ecommercePayment->payByLinkResponse->url;
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->log->write($this->session->data['error']);
		}
	}
}
