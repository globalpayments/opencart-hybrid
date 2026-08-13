<?php

use GlobalPayments\PaymentGatewayProvider\Data\{OrderData , RequestData};
use GlobalPayments\PaymentGatewayProvider\Gateways\{AbstractGateway , GatewayId};
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;
use GlobalPayments\Api\Builders\HPPBuilder;
use GlobalPayments\Api\Entities\{Address, PayerDetails, PhoneNumber, Transaction};
use GlobalPayments\Api\Entities\Enums\{
	AddressType,
	CaptureMode,
	ChallengeRequestIndicator,
	PaymentMethodUsageMode,
	Channel,
	Environment,
	ExemptStatus,
	HPPAllowedPaymentMethods,
	HPPStorageModes,
	PhoneNumberType,
	InstallmentsFundingMode
};
use GlobalPayments\Api\ServiceConfigs\Gateways\GpApiConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\CountryUtils;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;
use GlobalPayments\Api\Entities\GpApi\AccessTokenInfo;

class ControllerExtensionPaymentGlobalPaymentsUcp extends Controller
{
	private ?OrderData $order = null;

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(GatewayId::GP_API);
	}

	public function index(): string
	{
		$this->load->language('extension/payment/globalpayments_ucp');

		$this->setOrder();
		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['action'] = $this->url->link('extension/payment/globalpayments_ucp/confirm', '', true);

		$data['gateway'] = $this->globalpayments->gateway;

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
			$data['customer_is_logged'] = false;
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
		$data['base_country'] = $store_country_iso;

		$data['js_lib_version'] = Utils::getJsLibVersion();
		$data['environment_indicator'] = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');

		$data['integration_type'] = $this->config->get('payment_globalpayments_ucp_integration_type');

		if ($data['integration_type'] === 'hosted_payment') {
			$data['hpp_link'] = $this->buildHPP();
		} else {
			$data['globalpayments_secure_payment_fields_params'] = $this->globalpayments->gateway->securePaymentFieldsParams();
			$data['globalpayments_secure_payment_threedsecure_params'] = $this->globalpayments->gateway->securePaymentFieldsThreeDSecureParams($this->order);
		if (empty($this->session->data['apm_csrf_token'])) {
			$this->session->data['apm_csrf_token'] = bin2hex(random_bytes(32));
		}
		$data['apm_csrf_token'] = $this->session->data['apm_csrf_token'];

		$data['sandbox_account_name'] = $this->config->get('payment_globalpayments_ucp_sandbox_account_name');
		$data['account_name'] = $this->config->get('payment_globalpayments_ucp_account_name');
		$data['is_production'] = $this->config->get('payment_globalpayments_ucp_is_production');
		$data['allow_card_saving'] = $this->config->get('payment_globalpayments_ucp_allow_card_saving');
		$data['enable_installments'] = $this->config->get('payment_globalpayments_ucp_enable_installments');
		$enableVisaInstallments = $this->config->get('payment_globalpayments_ucp_enable_visa_installments');
		if ($enableVisaInstallments === null || $enableVisaInstallments === '') {
			$enableVisaInstallments = $this->config->get('payment_globalpayments_ucp_enable_installments');
		}
		$data['enable_visa_installments'] = $enableVisaInstallments;

		$data['environment_indicator'] = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields'] = $this->globalpayments->gateway->getCreditCardFormatFields();
		$data['globalpayments_secure_payment_fields_params'] = $this->globalpayments->gateway->securePaymentFieldsParams();
		$data['globalpayments_secure_payment_threedsecure_params'] = $this->globalpayments->gateway->securePaymentFieldsThreeDSecureParams($this->order);
		}

		return $this->load->view('extension/payment/globalpayments_ucp', $data);
	}

	public function threeDSecureCheckEnrollment(): void
	{
		$postRequestData = AbstractRequest::getPostRequestData();

		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);

		if (!empty($postRequestData->paymentTokenId) && 'new' !== $postRequestData->paymentTokenId) {
			$this->load->model('extension/payment/globalpayments_ucp');
			$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
		}

		$this->globalpayments->gateway->threeDSecureCheckEnrollment($requestData);
	}

	public function threeDSecureMethodNotification(): void
	{
		$this->globalpayments->gateway->threeDSecureMethodNotification();
	}

	public function threeDSecureInitiateAuthentication(): void
	{
		$postRequestData = AbstractRequest::getPostRequestData();

		$requestData = new RequestData();
		$requestData = RequestData::setDataObject($requestData, $postRequestData);

		if (!empty($postRequestData->paymentTokenId) && 'new' !== $postRequestData->paymentTokenId) {
			$this->load->model('extension/payment/globalpayments_ucp');
			$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
		}

		$this->globalpayments->gateway->threeDSecureInitiateAuthentication($requestData);
	}

	public function threeDSecureChallengeNotification(): void
	{
		$this->globalpayments->gateway->threeDSecureChallengeNotification();
	}

	public function confirm(): void
	{
		$this->load->language('extension/payment/globalpayments_ucp');

		if($this->config->get('payment_globalpayments_ucp_integration_type') === "hosted_payment"){
      return;
		}

		try {
			$this->setOrder();
			if (empty($this->request->post[$this->globalpayments->gateway->gatewayId])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}

			$postRequestData = (object) $this->request->post[$this->globalpayments->gateway->gatewayId];
			$requestData = new RequestData();
			$requestData = RequestData::setDataObject($requestData, $postRequestData);
			$requestData->paymentTokenResponse = !empty($postRequestData->paymentTokenResponse) ? htmlspecialchars_decode($postRequestData->paymentTokenResponse) : null;
			$requestData->dynamicDescriptor = $this->config->get('payment_globalpayments_ucp_txn_descriptor');
			$requestData->order = $this->order;
			$requestData->meta = (object) [
				'shared_text' => $this->load->language('extension/payment/globalpayments_shared_text'),
			];

			$store_currency = $this->config->get('config_currency');
			$store_country_id = $this->config->get('config_country_id');

			// Get country ISO code from country ID
			$this->load->model('localisation/country');
			$country_info = $this->model_localisation_country->getCountry($store_country_id);
			$store_country_iso = isset($country_info['iso_code_2']) ? $country_info['iso_code_2'] : '';

			// Set base country and currency in the gateway before getting secure payment params
			$this->globalpayments->gateway->baseCountry = $store_country_iso;
			$this->globalpayments->gateway->baseCurrency = $store_currency;

			// Extract installment data from paymentTokenResponse
			if (!empty($requestData->paymentTokenResponse)) {

				$tokenData = json_decode($requestData->paymentTokenResponse);
				$processInstallment = (!empty($tokenData->installment->id) || !empty($tokenData->installment->reference));
				if (isset($tokenData->installment) && $processInstallment) {
					$requestData->installments = (object) [
						'id' => $tokenData->installment->id ?? null,
						'reference' => $tokenData->installment->reference ?? null,
						'language' => $tokenData->installment->language ?? null,
						'version' => $tokenData->installment->version ?? null,
					];

				}
				$requestData = $this->updateContractReference($requestData);
			}

			if (
				isset($postRequestData->paymentType)
				&& 'saved' === $postRequestData->paymentType
				&& isset($postRequestData->paymentTokenId)
				&& 'new' !== $postRequestData->paymentTokenId
			) {
				$this->load->model('extension/payment/globalpayments_ucp');
				$requestData->paymentToken = $this->model_extension_payment_globalpayments_ucp->getCard($postRequestData->paymentTokenId);
				$requestData = $this->updateContractReference($requestData);
			}
			$requestData->requestType = AbstractGateway::getRequestType($this->globalpayments->gateway->paymentAction);

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

			// Add installment details if present
			if (!empty($gatewayResponse->installment)) {
				$installmentInfo = 'Installment ID: ' . ($gatewayResponse->installment->id ?? 'N/A');
				if (!empty($gatewayResponse->installment->mode)) {
					$installmentInfo .= ' | Mode: ' . $gatewayResponse->installment->mode;
				}
				if (!empty($gatewayResponse->installment->count)) {
					$installmentInfo .= ' | Terms: ' . $gatewayResponse->installment->count;
				}
				$comment[] = $installmentInfo;
			}

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
						(string) $card['card_last4'] === (string) $payment_token->details->cardLast4 &&
						(string) $card['expiry_month'] === (string) $payment_token->details->expiryMonth &&
						(string) $card['expiry_year'] === (string) $payment_token->details->expiryYear
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

			// Validate order_id is numeric to prevent manipulation
			if (!is_numeric($this->session->data['order_id'])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}

			$this->response->redirect(
				$this->url->link('checkout/success', ['order_id' => (int)$this->session->data['order_id']], true)
			);
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}
	}

	public function updateContractReference(RequestData $requestData): RequestData
	{

		if (
			$this->globalpayments->gateway->allowCardSaving
			&& $this->globalpayments->gateway->baseCountry === 'MX'
			&& $this->globalpayments->gateway->baseCurrency === 'MXN'
		) {
			$orderData = $requestData->order;
			$orderReference = (isset($orderData->orderReference)) ? $orderData->orderReference : $orderData->reference;
			$requestData->contactReference = $orderReference;
		} else {
			$requestData->contactReference = "";
		}

		return $requestData;
	}

	private function setOrder(): void
	{
		if (empty($this->session->data['order_id'])) {
			throw new \Exception($this->language->get('error_order_processing'));
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order = new OrderData();
		$order->amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$order->currency = $order_info['currency_code'];
		$order->orderReference = $this->session->data['order_id'];
		$order->reference = 'opencart_' . $this->session->data['order_id'] . '_' . time();
		$order->description = 'Order #' . $this->session->data['order_id'];
		$order->customerEmail = $order_info['email'];

		$order->billingAddress = [
			'streetAddress1' => $order_info['payment_address_1'],
			'streetAddress2' => $order_info['payment_address_2'],
			'city' => $order_info['payment_city'],
			'state' => $order_info['payment_zone_code'],
			'postalCode' => $order_info['payment_postcode'],
			'country' => $order_info['payment_iso_code_2'],
		];

		$order->shippingAddress = [
			'streetAddress1' => $order_info['shipping_address_1'],
			'streetAddress2' => $order_info['shipping_address_2'],
			'city' => $order_info['shipping_city'],
			'state' => $order_info['shipping_zone_code'],
			'postalCode' => $order_info['shipping_postcode'],
			'country' => $order_info['shipping_iso_code_2'],
		];

		$order->addressMatchIndicator = $order->billingAddress == $order->shippingAddress;

		$this->order = $order;
	}

	public function buildHPP(): ?string
	{
		try {
			$config = new GpApiConfig();
			$accountName ="";
			if ($this->globalpayments->gateway->isProduction == 1) {
				$config->appId = $this->globalpayments->gateway->appId;
				$config->appKey = $this->globalpayments->gateway->appKey;
				$config->environment = Environment::PRODUCTION;
				$accountName = $this->gateway->accountName ?? null;
			} else {
				$config->appId = $this->globalpayments->gateway->sandboxAppId;
				$config->appKey = $this->globalpayments->gateway->sandboxAppKey;
				$config->environment = Environment::TEST;
				$accountName = $this->gateway->sandboxAccountName ?? null;
			}
			if (!empty($accountName)) {
				$accessTokenInfo = new AccessTokenInfo();
				$accessTokenInfo->transactionProcessingAccountName = $accountName;
				$config->accessTokenInfo = $accessTokenInfo;
			}
			$config->country = $this->globalpayments->gateway->country;
			$config->channel = Channel::CardNotPresent;

			ServicesContainer::configureService($config);

			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if (!$order_info) {
				throw new \Exception('Order not found');
			}

			// Build OpenCart URLs for the return, status and cancel URL's
			$returnUrl = $this->url->link('extension/payment/hpp/redirect', '', true);
			$statusUrl = $this->url->link('extension/payment/hpp/webhook', '', true);
			$cancelUrl = $this->url->link('checkout/cart', '', true);


			// Classes for HPPBuilder
			$payerDetails = new PayerDetails();
			$payerDetails->firstName = $order_info['firstname'];
			$payerDetails->lastName = $order_info['lastname'];
			$payerDetails->email = $order_info['email'];
			$payerDetails->language = strtoupper(substr($this->config->get('config_language'), 0, 2)) ?? "EN";

			$phoneNumber = new PhoneNumber(
				CountryUtils::getPhoneCodesByCountry($order_info['payment_iso_code_2'])[0],
				$order_info['telephone'],
				PhoneNumberType::HOME
			);

			$billingAddress = new Address();
			$billingAddress->type = AddressType::BILLING;
			$billingAddress->streetAddress1 = $order_info['payment_address_1'];
			$billingAddress->streetAddress2 = $order_info['payment_address_2'];
			$billingAddress->city = $order_info['payment_city'];
			$billingAddress->state = substr($order_info['payment_zone_code'], 0, 3);
			$billingAddress->postalCode = $order_info['payment_postcode'];
			$billingAddress->country = $order_info['payment_country'];
			$billingAddress->countryCode = $order_info['payment_iso_code_2'];

			$hasShippingAddress = !empty($order_info['shipping_iso_code_2']);
			$shippingAddress = new Address();

			if($hasShippingAddress){
				$shippingAddress->type = AddressType::SHIPPING;
				$shippingAddress->streetAddress1 = $order_info['shipping_address_1'];
				$shippingAddress->streetAddress2 = $order_info['shipping_address_2'];
				$shippingAddress->city = $order_info['shipping_city'];
				$shippingAddress->state = substr($order_info['shipping_zone_code'], 0, 3);
				$shippingAddress->postalCode = $order_info['shipping_postcode'];
				$shippingAddress->country = $order_info['payment_country'];
				$shippingAddress->countryCode = $order_info['shipping_iso_code_2'];
			}else{
				$shippingAddress = $billingAddress;
				$shippingAddress->type = AddressType::SHIPPING;
			}

			$payerDetails->billingAddress = $billingAddress;
			$payerDetails->shippingAddress = $shippingAddress;

			$payerDetails->status = 'NEW';

			$store_country_id = $this->config->get('config_country_id');
			$this->load->model('localisation/country');
			$country_info = $this->model_localisation_country->getCountry($store_country_id);
			$store_country_iso = isset($country_info['iso_code_2']) ? $country_info['iso_code_2'] : '';

			// Get DCC setting
			$enableDCC = (bool) $this->globalpayments->gateway->allowDCC;
			
			// Determine payment methods based on amount and configuration
			$paymentMethods = ["CARD"];
			// $paymentMethods = [HPPAllowedPaymentMethods::BLIK];

			$digitalWallets = [];

			// Get configured HPP wallets from settings
			$hppWalletsConfig = $this->config->get('payment_globalpayments_ucp_hpp_wallets');
			$enabledWallets = is_string($hppWalletsConfig) ? json_decode($hppWalletsConfig, true)
			: (is_array($hppWalletsConfig) ? $hppWalletsConfig : []);

			// Add digital wallets based on configuration
			if (!empty($enabledWallets)) {
				foreach ($enabledWallets as $wallet) {
					$walletLower = strtolower($wallet);
					if ($walletLower === 'applepay' || $walletLower === 'apple_pay') {
						$digitalWallets[] = 'applepay';
					} elseif ($walletLower === 'googlepay' || $walletLower === 'google_pay') {
						$digitalWallets[] = 'googlepay';
					} elseif ($walletLower === 'blik') {
						$paymentMethods[] = 'BLIK';
						// $paymentMethods[] = HPPAllowedPaymentMethods::BLIK;
					} elseif ($walletLower === 'payu') {
						$paymentMethods[] = 'PAYU';
						// $paymentMethods[] = HPPAllowedPaymentMethods::PAYU;
					}
				}
			}


			$hppBuilder = HPPBuilder::create()
				->withName($this->session->data['order_id'])
				->withDescription( 'Payment for Order #' . $this->session->data['order_id'] )
				->withReference('order_id_' . $this->session->data['order_id'])
				->withPayer($payerDetails)
				->withPayerPhone($phoneNumber)
				->withBillingAddress($billingAddress)
				->withShippingAddress($shippingAddress)
				->withShippingPhone($phoneNumber)
				->withAmount($this->order->amount)
				->withOrderReference($this->session->data['order_id'])
				->withTransactionConfig(
					Channel::CardNotPresent,
					$store_country_iso,
					CaptureMode::AUTO,
					$paymentMethods,
					PaymentMethodUsageMode::SINGLE
				)
				->withPaymentMethodConfig(HPPStorageModes::PROMPT)
				->withNotifications(
					$returnUrl,
					$statusUrl,
					$cancelUrl
				)
				->withCurrency($order_info['currency_code'])
				->withAddressMatchIndicator($hasShippingAddress ?
				$this->addressMatch($billingAddress, $shippingAddress) :
				true)
				->withCurrencyConversionMode($enableDCC);
						
			// Add digital wallets if available
			if (!empty($digitalWallets)) {
				$hppBuilder->withDigitalWallets($digitalWallets);
			}

			// Add 3DS authentication for lower value transactions
			$hppBuilder->withAuthentication(
				ChallengeRequestIndicator::CHALLENGE_PREFERRED,
				ExemptStatus::LOW_VALUE,
				true
			);

			if($this->HPP_installments_eligible($store_country_iso)){
				$hppBuilder = $this->add_installments_filtering($hppBuilder);
			}
			$ecommercePayment = $hppBuilder->execute();
			return $ecommercePayment->payByLinkResponse->url;
		} catch (\Exception $e) {
			$this->session->data['error'] = "Unexpected Error Occoured, please refresh the page and try again";
			$this->log('HPP Build Error:' . $e->getMessage());
			return null;
		}
	}


	/**
	 * Write to log if debug is enabled
	 * @param String msg Error message
	 * @return void
	 */

	private function log(string $msg): void
	{
		$this->load->model('extension/payment/globalpayments_ucp');
		$debugEnabled = !empty($this->config->get('payment_globalpayments_ucp_debug'));
		if ($debugEnabled && !empty($msg)) {
			$this->log->write($msg);
		}
	}

	/**
	 * Capture installments data while order_id still in session
	 *
	 * @param string $route The controller route
	 * @param array $data The controller input data
	 * @return void
	 */
	public function captureInstallmentsData(&$route, &$data): void
	{
		if (empty($this->session->data['order_id'])) {
			return;
		}

		$order_id = (int) $this->session->data['order_id'];

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		if (empty($order_info)) {
			return;
		}
		// Check for installments data
		if (
			!isset($order_info['payment_custom_field']['hpp_installments_data'])
			|| empty($order_info['payment_custom_field']['hpp_installments_data'])
		) {
			return;
		}

		$installmentsData = json_decode($order_info['payment_custom_field']['hpp_installments_data'], true);

		if (empty($installmentsData)) {
			$this->log("captureInstallmentsData: Failed to decode installments data");
			return;
		}

		$currency = $order_info['currency_code'];

		// Prepare data for template
		$templateData = [
			'installments_data' => $installmentsData,
			'grand_total_formatted' => $this->currency->format(
				($installmentsData['total_amount'] ?? 0) / 100,
				$currency,
				1
			),
			'formatted_order_total' => $this->currency->format(
				$order_info['total'] ?? 0,
				$currency,
				1
			),
			'fees_total_formatted' => $this->currency->format(
				($installmentsData['fees']['total_amount'] ?? 0) / 100,
				$currency,
				1
			),
		];

		// Render the Twig template
		$html = $this->load->view('extension/payment/globalpayments_hpp_installments', $templateData);
		// Store rendered HTML in registry for later injection
		$this->registry->set('gp_installments_html', $html);
	}

	/**
	 * Inject installments HTML into success page output
	 * Note: at this point the order ID is not in the session
	 *
	 * @param string $route The view route
	 * @param array $data The data that was passed to the template
	 * @param string $output The rendered HTML output
	 * @return void
	 */
	public function injectInstallmentsHTML(&$route, &$data, &$output): void
	{
		// Get pre-built HTML from registry
		if (!$this->registry->has('gp_installments_html')) {
			return;
		}

		$html = $this->registry->get('gp_installments_html');

		if (empty($html)) {
			return;
		}
		if (preg_match('/(<div class="buttons".*?>)/i', $output)) {
			$output = preg_replace(
				'/(<div class="buttons".*?>)/i',
				'$1' . $html,
				$output,
				1
			);
		} elseif (preg_match('/<\/body\s*>/', $output)) {
			$output = preg_replace(
				'/<\/body\s*>/i',
				$html . '</body>',
				$output,
				1
			);
		} else {
			$this->log("Failed to add HPP installments HTML, could not find target");
		}
	}

	/**
	 * Adds installments filtering options to the installments shown on HPP
	 *
	 * @param HPPBuilder $hpp_builder to apply the fitering options to.
	 * @return HPPBuilder $hpp_builder with installments filtering applied if the
	 * withInstallments method applied, returns the same HPPBuilder if method not
	 * available.
	 */
	private function add_installments_filtering(HPPBuilder $hpp_builder): HPPBuilder
	{
		if(! method_exists($hpp_builder , "withInstallments")){
			return $hpp_builder;
		}

		// Defaults
		$fundingMode = InstallmentsFundingMode::ANY;
		$maxMonths   = 32;
		$threshold   = null;

		$planType = $this->globalpayments->gateway->hppInstallmentsType ?? null;
		if ( !empty( $planType ) && $planType !== 'any' ) {
			$planType =  strtoupper( $planType );
			$InstallmentsFundingModeReflection = new \ReflectionClass( InstallmentsFundingMode::class );
			$InstallmentsFundingModeConsts = $InstallmentsFundingModeReflection->getConstants();

			if ( isset( $InstallmentsFundingModeConsts[$planType] ) ) {
				$fundingMode = $InstallmentsFundingModeConsts[$planType];
			} else {
				$fundingMode = InstallmentsFundingMode::ANY;
			}
		}


		// Max months (only relevant for merchant funded)
		if (
			$fundingMode === InstallmentsFundingMode::MERCHANT_FUNDED &&
			!empty( $this->globalpayments->gateway->hppInstallmentsDuration ) ) {
			$raw = explode( '_', $this->globalpayments->gateway->hppInstallmentsDuration )[0];
			$months = ( int ) $raw;

			if ( $months > 0 ) {
				$maxMonths = $months;
			}
		}

		$rawThreshold = $this->globalpayments->gateway->hppInstallmentsTheshold ?? null;

		if ( $rawThreshold !== null && $rawThreshold !== '0' ) {
			$thresholdValue = ( int ) $rawThreshold;
			if ( $thresholdValue > 0 && strlen( ( string ) $rawThreshold) < 16 ) {
				$threshold = $thresholdValue;
			}
		}
		return $hpp_builder->withInstallments( $fundingMode, $maxMonths, $threshold );
	}

	/**
	 * Returns true if the iso_code reprisents either GB or Canada
	 * @param string $iso_code
	 * @return bool
	 */
	private function HPP_installments_eligible(string $iso_code): bool
	{
		if(empty($iso_code)){
			return false;
		}
		return $iso_code === "GB" || $iso_code === "CA";
	}
   /*
	 * Determines if $billingAddress and $shippingAddress class
	 * properties have the same value, type property is removed.
	 * @param Address $billingAddress
	 * @param Address $shippingAddress
	 * @return bool
	 *
	 */
	private function addressMatch( Address $billingAddress , Address $shippingAddress ): bool
	{
		$billingAddress = (array) $billingAddress;
		$shippingAddress = (array) $shippingAddress;
		unset($billingAddress["type"], $shippingAddress["type"]);

		return $billingAddress == $shippingAddress;
	}
}
