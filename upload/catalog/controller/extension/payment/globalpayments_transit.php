<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

// TransIT specific imports
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\ServiceConfigs\Gateways\TransitConfig;
use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\Entities\Address;
use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\Api\ServiceConfigs\AcceptorConfig;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\Api\Utils\Logging\SampleRequestLogger;


class ControllerExtensionPaymentGlobalPaymentsTransit extends Controller {
	private $gateway_id = 'globalpayments_transit';
	private $developer_id = '003226G001'; // TransIT Developer ID from WordPress plugin

	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway($this->gateway_id);
	}

	public function index() {
		$this->load->language('extension/payment/globalpayments_transit');
		$this->setOrder();
		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_credit_card'] = $this->language->get('text_credit_card');
		$data['text_saved_cards'] = $this->language->get('text_saved_cards');
		$data['text_use_new_card'] = $this->language->get('text_use_new_card');
		$data['text_card_ending'] = $this->language->get('text_card_ending');
		$data['text_expires'] = $this->language->get('text_expires');

		$data['entry_cc_owner'] = $this->language->get('entry_cc_owner');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
		$data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
		$data['entry_save_card'] = $this->language->get('entry_save_card');
		$data['entry_allow_card_saving'] = $this->language->get('entry_allow_card_saving');

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_please_wait'] = $this->language->get('text_please_wait');

		$data['action'] = $this->url->link('extension/payment/globalpayments_transit/confirm', '', true);
		$data['payment_tab_option'] = 'new';
		$data['default_saved_card_token'] = 'new';
		$data['gateway'] = $this->globalpayments->gateway;
		$data['allow_card_saving'] = (bool)$this->config->get('payment_globalpayments_transit_card');
		// Check if customer is logged in for saved cards
		if ($this->customer->isLogged()) {
			$data['customer_logged'] = true;
			$this->load->model('extension/payment/globalpayments_transit');
			$data['saved_cards'] = $this->model_extension_payment_globalpayments_transit->getCustomerCards($this->customer->getId());

			foreach ($data['saved_cards'] as $saved_card) {
				if (!empty($saved_card['is_default'])) {
					$data['default_saved_card_token'] = (string)$saved_card['token_id'];
					$data['payment_tab_option'] = 'saved';
					break;
				}
			}
		} else {
			$data['customer_logged'] = false;
			$data['saved_cards'] = [];
		}

		// TransIT TSEP Configuration (matching WordPress implementation)
		$is_production = $this->config->get('payment_globalpayments_transit_is_production');

		// Determine environment
		$data['environment'] = $is_production ? 'production' : 'sandbox';

		// Get TransIT credentials - using existing admin fields
		$data['merchant_id'] = $is_production
			? $this->config->get('payment_globalpayments_transit_merchant_id')
			: $this->config->get('payment_globalpayments_transit_sandbox_merchant_id');

		$data['user_id'] = $is_production
			? $this->config->get('payment_globalpayments_transit_user_id')
			: $this->config->get('payment_globalpayments_transit_sandbox_user_id');

		$data['password'] = $is_production
			? $this->config->get('payment_globalpayments_transit_password')
			: $this->config->get('payment_globalpayments_transit_sandbox_password');

		$data['device_id'] = $is_production
			? $this->config->get('payment_globalpayments_transit_device_id')
			: $this->config->get('payment_globalpayments_transit_sandbox_device_id');

		$data['tsep_device_id'] = $is_production
			? $this->config->get('payment_globalpayments_transit_tsep_device_id')
			: $this->config->get('payment_globalpayments_transit_sandbox_tsep_device_id');

		$data['transaction_key'] = $is_production
			? $this->config->get('payment_globalpayments_transit_transaction_key')
			: $this->config->get('payment_globalpayments_transit_sandbox_transaction_key');

		// Use GlobalPayments.js SDK URL (WordPress approach)
		$data['globalpayments_js_url'] = 'https://js.globalpay.com/v1/globalpayments.min.js';

		// Environment indicator
		if (!$is_production) {
			$data['sandbox_mode'] = true;
			$data['text_sandbox'] = $this->language->get('text_sandbox');
		} else {
			$data['sandbox_mode'] = false;
		}

		// Add error warning if exists
		$data['error_warning'] = '';
		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		}

		$data['environment_indicator']                             = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields']                             = $this->globalpayments->gateway->getCreditCardFormatFields();
		$data['globalpayments_secure_payment_fields_params']       = $this->globalpayments->gateway->securePaymentFieldsParams();
		$data['globalpayments_secure_payment_threedsecure_params'] = $this->globalpayments->gateway->securePaymentFieldsThreeDSecureParams($this->order);



		return $this->load->view('extension/payment/globalpayments_transit', $data);
	}

	public function confirm() {
		$this->load->language('extension/payment/globalpayments_transit');

		try {
			if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
				throw new Exception('Invalid request method');
			}

			$this->load->model('checkout/order');
			if (!isset($this->session->data['order_id'])) {
				throw new Exception('No order found in session');
			}

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			if (!$order_info) {
				throw new Exception($this->language->get('error_order_not_found'));
			}

			$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
			$currency = $order_info['currency_code'];
			$paymentAction = $this->getConfiguredPaymentAction();

			// Process payment based on available data
		// PCI-DSS Compliance: Only accept tokenized payment data, never raw card data
		$response = null;
		if (isset($this->request->post['saved_card_token']) && $this->request->post['saved_card_token'] !== 'new') {
			if (!$this->customer->isLogged()) {
				throw new Exception('Must be logged in to use a saved card');
			}
			$response = $this->processSavedCardPayment((int)$this->request->post['saved_card_token'], $amount, $currency, $order_info);
		} else if (isset($this->request->post['paymentTokenResponse']) && !empty($this->request->post['paymentTokenResponse'])) {
			$response = $this->processTransitTokenPayment($this->request->post['paymentTokenResponse'], $amount, $currency, $order_info);
		} else if (isset($this->request->post['payment_token']) && !empty($this->request->post['payment_token'])) {
			$response = $this->processGlobalPaymentsTokenPayment($this->request->post, $amount, $currency, $order_info);
		} else {
			throw new Exception('Missing tokenized payment data');
		}

		if (!$response) {
			throw new Exception('Payment processing failed - no response received');
		}

		if (isset($response->responseCode) && ($response->responseCode === 'A0000' || $response->responseCode === '00')) {
			// Check AVS/CVN and potentially reverse transaction
			$avs_check_result = $this->handleAvsCheck($response, $order_info);
			if ($avs_check_result['should_reverse']) {
				// Transaction was reversed due to AVS/CVN failure
				$error_message = $this->language->get('error_avs_cvn_rejected');
				if (empty($error_message)) {
					$error_message = 'The billing address you entered doesn\'t match the one on file with your card provider. Please check and try again.';
				}

				// Check if this is an AJAX request
				if (isset($this->request->post['ajax']) ||
					isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
					strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

					// Return JSON error response for AJAX requests
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode([
						'success' => false,
						'error' => $error_message
					]));
				} else {
					// Store error and redirect for non-AJAX requests
					$this->session->data['error'] = $error_message;
					$this->response->redirect($this->url->link('checkout/checkout', '', true));
				}
				return;
			}

			$order_status_id = (int) $this->config->get('payment_globalpayments_transit_order_status_id');
			if (!$order_status_id) {
				$order_status_id = 2;
			}

			$comment = sprintf(
				'TransIT %s Successful - Transaction ID: %s, Auth Code: %s, Response: %s',
				$this->getPaymentActionLabel($paymentAction),
				isset($response->transactionReference->transactionId) ? $response->transactionReference->transactionId : 'N/A',
				isset($response->authorizationCode) ? $response->authorizationCode : 'N/A',
				isset($response->responseMessage) ? $response->responseMessage : 'Success'
			);

			$this->model_checkout_order->addOrderHistory(
				$this->session->data['order_id'],
				$order_status_id,
				$comment,
				true
			);

			// Optional: Store transaction details
			$this->load->model('extension/payment/globalpayments_transit');
			try {
				$this->model_extension_payment_globalpayments_transit->addTransaction(
						$order_info['order_id'],
						$paymentAction,
						$amount,
						$currency,
						$response
					);
				} catch (Exception $e) {
					// Continue if custom transaction table doesn't exist
				}

				// Handle card saving if requested
				$saveCardRequested = false;
				if (isset($this->request->post['save_card']) && $this->request->post['save_card'] == '1') {
					$saveCardRequested = true;
				}

				if (isset($this->request->post[$this->gateway_id]['saveCard']) && $this->request->post[$this->gateway_id]['saveCard'] == '1') {
					$saveCardRequested = true;
				}

				if ($this->customer->isLogged() &&
					$saveCardRequested &&
					isset($this->request->post['paymentTokenResponse'])) {

					try {
						$decodedTokenResponse = html_entity_decode($this->request->post['paymentTokenResponse'], ENT_QUOTES, 'UTF-8');
						$tokenData = json_decode($decodedTokenResponse, true);

						if (!$tokenData && is_string($decodedTokenResponse)) {
							$tokenData = json_decode(stripslashes($decodedTokenResponse), true);
						}

						if ($tokenData) {
							$details = isset($tokenData['details']) && is_array($tokenData['details']) ? $tokenData['details'] : array();
							$paymentReference = '';

							if (isset($tokenData['paymentReference'])) {
								$paymentReference = $tokenData['paymentReference'];
							} elseif (isset($tokenData['payment_reference'])) {
								$paymentReference = $tokenData['payment_reference'];
							} elseif (isset($tokenData['token'])) {
								$paymentReference = $tokenData['token'];
							} elseif (isset($tokenData['paymentToken'])) {
								$paymentReference = $tokenData['paymentToken'];
							}

							if (!empty($paymentReference)) {
								$cardType = isset($details['cardType']) ? $details['cardType'] : (isset($details['card_type']) ? $details['card_type'] : 'unknown');
								$cardLast4 = isset($details['cardLast4']) ? $details['cardLast4'] : (isset($details['last4']) ? $details['last4'] : '****');
								$expiryYear = isset($details['expiryYear']) ? $details['expiryYear'] : (isset($details['expYear']) ? $details['expYear'] : '');
								$expiryMonth = isset($details['expiryMonth']) ? $details['expiryMonth'] : (isset($details['expMonth']) ? $details['expMonth'] : '');

								$this->model_extension_payment_globalpayments_transit->addCard(
									$this->customer->getId(),
									$paymentReference,
									$cardType,
									$cardLast4,
									$expiryYear,
									$expiryMonth !== '' ? sprintf('%02d', (int)$expiryMonth) : ''
								);
							} elseif ($this->config->get('payment_globalpayments_transit_debug')) {
								$this->log->write('GlobalPayments TransIT: save_card requested but token missing in paymentTokenResponse');
							}
						} elseif ($this->config->get('payment_globalpayments_transit_debug')) {
							$this->log->write('GlobalPayments TransIT: unable to decode paymentTokenResponse for card saving');
						}
					} catch (Exception $e) {
						if ($this->config->get('payment_globalpayments_transit_debug')) {
							$this->log->write('GlobalPayments TransIT: card saving failed - ' . $e->getMessage());
						}
					}
				}

				$this->cart->clear();
				unset($this->session->data['error']);

				// Check if this is an AJAX request
				if (isset($this->request->post['ajax']) ||
					isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
					strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

					// Return JSON response for AJAX requests
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode([
						'success' => true,
						'redirect' => $this->url->link('checkout/success', '', true)
					]));
				} else {
					// Regular redirect for non-AJAX requests
					$this->response->redirect($this->url->link('checkout/success', '', true));
				}

			} else {
				$error_message = 'Payment failed: ' .
					(isset($response->responseMessage) ? $response->responseMessage : 'Unknown error') .
					' (Code: ' . (isset($response->responseCode) ? $response->responseCode : 'N/A') . ')';

				// Check if this is an AJAX request
				if (isset($this->request->post['ajax']) ||
					isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
					strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

					// Return JSON error response for AJAX requests
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode([
						'success' => false,
						'error' => $error_message
					]));
				} else {
					// Store error and redirect for non-AJAX requests
					$this->session->data['error'] = $error_message;
					$this->response->redirect($this->url->link('checkout/checkout', '', true));
				}
			}

		} catch (Exception $e) {
			$error_message = $e->getMessage();

			// Check if this is an AJAX request
			if (isset($this->request->post['ajax']) ||
				isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
				strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

				// Return JSON error response for AJAX requests
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode([
					'success' => false,
					'error' => $error_message
				]));
			} else {
				// Store error and redirect for non-AJAX requests
				$this->session->data['error'] = $error_message;
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
			}
		}
	}

	private function configureGateway() {
		$transitConfig = new TransitConfig();
		$transitConfig->environment = $this->config->get('payment_globalpayments_transit_is_production') ? Environment::PRODUCTION : Environment::TEST;
		$transitConfig->developerId = $this->developer_id;

		$acceptorConfig = new AcceptorConfig();
		$transitConfig->acceptorConfig = $acceptorConfig;

		if ($this->config->get('payment_globalpayments_transit_is_production')) {
			$transitConfig->merchantId = $this->config->get('payment_globalpayments_transit_merchant_id');
			$transitConfig->deviceId = $this->config->get('payment_globalpayments_transit_device_id');
			$transitConfig->transactionKey = $this->config->get('payment_globalpayments_transit_transaction_key');
			$transitConfig->username = $this->config->get('payment_globalpayments_transit_user_id');
			$transitConfig->password = $this->config->get('payment_globalpayments_transit_password');
		} else {
			$transitConfig->merchantId = $this->config->get('payment_globalpayments_transit_sandbox_merchant_id');
			$transitConfig->deviceId = $this->config->get('payment_globalpayments_transit_sandbox_device_id');
			$transitConfig->transactionKey = $this->config->get('payment_globalpayments_transit_sandbox_transaction_key');
			$transitConfig->username = $this->config->get('payment_globalpayments_transit_sandbox_user_id');
			$transitConfig->password = $this->config->get('payment_globalpayments_transit_sandbox_password');
		}

		if ($this->config->get('payment_globalpayments_transit_debug')) {
			$transitConfig->requestLogger = new SampleRequestLogger(new Logger(DIR_LOGS));
		}

		ServicesContainer::configureService($transitConfig);
	}

	private function processTransitTokenPayment($paymentTokenResponse, $amount, $currency, $orderInfo) {
		$decodedPaymentTokenResponse = html_entity_decode($paymentTokenResponse, ENT_QUOTES, 'UTF-8');
		$tokenData = json_decode($decodedPaymentTokenResponse, true);

		if (!$tokenData || !isset($tokenData['paymentReference'])) {
			$errorMsg = 'Invalid payment token response';
			if (!$tokenData) {
				$errorMsg .= ' - Failed to decode JSON: ' . json_last_error_msg();
			} elseif (!isset($tokenData['paymentReference'])) {
				$errorMsg .= ' - Missing paymentReference field';
			}
			throw new Exception($errorMsg);
		}

		$this->configureGateway();

		$card = new CreditCardData();
		$card->token = $tokenData['paymentReference'];

		if (isset($tokenData['details'])) {
			$details = $tokenData['details'];
			$card->cardHolderName = isset($details['cardholderName']) && !empty($details['cardholderName'])
				? $details['cardholderName']
				: $orderInfo['payment_firstname'] . ' ' . $orderInfo['payment_lastname'];

			if (isset($details['expiryMonth']) && isset($details['expiryYear'])) {
				$card->expMonth = intval($details['expiryMonth']);
				$card->expYear = intval($details['expiryYear']);
			}

			if (!empty($details['cardSecurityCode'])) {
				$card->cvn = (string)$details['cardSecurityCode'];
			}
		} else {
			$card->cardHolderName = $orderInfo['payment_firstname'] . ' ' . $orderInfo['payment_lastname'];
		}

		$address = new Address();
		$address->streetAddress1 = $orderInfo['payment_address_1'];
		if (!empty($orderInfo['payment_address_2'])) {
			$address->streetAddress2 = $orderInfo['payment_address_2'];
		}
		$address->city = $orderInfo['payment_city'];
		$address->state = $orderInfo['payment_zone'];
		$address->postalCode = $orderInfo['payment_postcode'];
		$address->country = $orderInfo['payment_iso_code_2'];

		try {
			$transaction = $this->executePaymentTransaction($card, $amount, $currency, $address, $orderInfo['order_id']);

			return $transaction;

		} catch (Exception $e) {
			throw new Exception('Payment processing failed: ' . $e->getMessage());
		}
	}

	private function processGlobalPaymentsTokenPayment($cardData, $amount, $currency, $orderInfo) {
		$this->configureGateway();

		$card = new CreditCardData();
		$card->token = $cardData['payment_token'];
		$card->cardHolderName = isset($cardData['card_holder_name']) ? $cardData['card_holder_name'] : $orderInfo['payment_firstname'] . ' ' . $orderInfo['payment_lastname'];

		$address = new Address();
		$address->streetAddress1 = $orderInfo['payment_address_1'];
		$address->city = $orderInfo['payment_city'];
		$address->state = $orderInfo['payment_zone'];
		$address->postalCode = $orderInfo['payment_postcode'];
		$address->country = $orderInfo['payment_iso_code_2'];

		return $this->executePaymentTransaction($card, $amount, $currency, $address, $orderInfo['order_id']);
	}

	private function processSavedCardPayment($tokenId, $amount, $currency, $orderInfo) {
		$tokenId = (int)$tokenId;
		if ($tokenId <= 0) {
			throw new Exception('Invalid saved card selected');
		}
		$this->load->model('extension/payment/globalpayments_transit');
		$this->configureGateway();
		$savedCard = $this->model_extension_payment_globalpayments_transit->getCustomerCards($this->customer->getId());

		$cardToken = null;
		$savedCardRecord = null;
		foreach ($savedCard as $card) {
			if ((int)$card['token_id'] === $tokenId) {
				$cardToken = $card['token'];
				$savedCardRecord = $card;
				break;
			}
		}

		if (!$cardToken) {
			throw new Exception('Invalid saved card selected');
		}

		$creditCard = new CreditCardData();
		$creditCard->token = $cardToken;
		$creditCard->cardHolderName = $orderInfo['payment_firstname'] . ' ' . $orderInfo['payment_lastname'];

		if ($savedCardRecord) {
			if (!empty($savedCardRecord['expiry_month'])) {
				$creditCard->expMonth = (int)$savedCardRecord['expiry_month'];
			}
			if (!empty($savedCardRecord['expiry_year'])) {
				$creditCard->expYear = (int)$savedCardRecord['expiry_year'];
			}
		}

		$address = new Address();
		$address->streetAddress1 = $orderInfo['payment_address_1'];
		if (!empty($orderInfo['payment_address_2'])) {
			$address->streetAddress2 = $orderInfo['payment_address_2'];
		}
		$address->city        = $orderInfo['payment_city'];
		$address->state       = $orderInfo['payment_zone'];
		$address->postalCode  = $orderInfo['payment_postcode'];
		$address->country     = $orderInfo['payment_iso_code_2'];

		return $this->executePaymentTransaction($creditCard, $amount, $currency, $address, $orderInfo['order_id']);
	}

	private function generateTransactionId() {
		return 'OC_TRANSIT_' . $this->session->data['order_id'] . '_' . time();
	}

	private function getConfiguredPaymentAction() {
		$paymentAction = $this->config->get('payment_globalpayments_transit_payment_action');

		return $paymentAction === 'authorize' ? 'authorize' : 'charge';
	}

	private function getPaymentActionLabel($paymentAction) {
		return $paymentAction === 'authorize' ? 'Authorization' : 'Payment';
	}

	private function executePaymentTransaction($paymentMethod, $amount, $currency, $address, $orderId) {
		$paymentAction = $this->getConfiguredPaymentAction();
		$transactionBuilder = $paymentAction === 'authorize'
			? $paymentMethod->authorize($amount)
			: $paymentMethod->charge($amount);

		return $transactionBuilder
			->withCurrency($currency)
			->withAddress($address)
			->withClientTransactionId($this->generateTransactionId())
			->withDescription($this->buildTransactionDescriptor($orderId))
			->execute();
	}

	/**
	 * Build transaction descriptor for payment gateway
	 * Uses configured descriptor if available, otherwise defaults to order reference
	 *
	 * @param int $order_id Order ID for fallback reference
	 * @return string Transaction descriptor (max 18 characters)
	 */
	private function buildTransactionDescriptor($order_id) {
		$descriptor = $this->config->get('payment_globalpayments_transit_transaction_descriptor');

		// If no custom descriptor configured, use default
		if (empty($descriptor)) {
			$descriptor = 'OpenCart Order #' . $order_id;
		}

		// Enforce 18 character limit (TransIT requirement)
		if (strlen($descriptor) > 18) {
			$descriptor = substr($descriptor, 0, 18);
		}

		return $descriptor;
	}



	public function getMethod($address, $total) {
		$this->load->model('extension/payment/globalpayments_transit');
		return $this->model_extension_payment_globalpayments_transit->getMethod($address, $total);
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

	/**
	 * Handle AVS/CVN validation and automatic reversal if needed
	 *
	 * @param object $response Payment response from gateway
	 * @param array $order_info OpenCart order information
	 * @return array Result array with 'should_reverse' flag and reason
	 */
	private function handleAvsCheck($response, $order_info) {
		// Check if AVS/CVN checking is enabled
		if (!$this->config->get('payment_globalpayments_transit_check_avs_cvn')) {
			return array('should_reverse' => false, 'reason' => '');
		}

		// Get configured rejection conditions
		$avs_reject_conditions = $this->config->get('payment_globalpayments_transit_avs_reject_conditions');
		$cvn_reject_conditions = $this->config->get('payment_globalpayments_transit_cvn_reject_conditions');

		// Ensure they are arrays
		if (!is_array($avs_reject_conditions)) {
			$avs_reject_conditions = !empty($avs_reject_conditions) ? array($avs_reject_conditions) : array('N', 'S', 'U', 'P', 'R', 'G', 'C', 'I');
		}
		if (!is_array($cvn_reject_conditions)) {
			$cvn_reject_conditions = !empty($cvn_reject_conditions) ? array($cvn_reject_conditions) : array('P', '?', 'N');
		}

		$should_reverse = false;
		$reverse_reason = '';

		// Extract AVS response code from response object
		$avs_response_code = $this->extractAvsResponseCode($response);
		$cvn_response_code = $this->extractCvnResponseCode($response);

		// Check AVS conditions
		if (!empty($avs_response_code) && in_array($avs_response_code, $avs_reject_conditions)) {
			$should_reverse = true;
			$reverse_reason = sprintf('AVS response code %s matched rejection condition', $avs_response_code);
			if ($this->config->get('payment_globalpayments_transit_debug')) {
				$this->log->write('GlobalPayments TransIT: AVS rejection - Code ' . $avs_response_code . ' for Order #' . $order_info['order_id']);
			}
		}

		// Check CVN conditions
		if (!empty($cvn_response_code) && in_array($cvn_response_code, $cvn_reject_conditions)) {
			$should_reverse = true;
			$reverse_reason = sprintf('CVN response code %s matched rejection condition', $cvn_response_code);
			if ($this->config->get('payment_globalpayments_transit_debug')) {
				$this->log->write('GlobalPayments TransIT: CVN rejection - Code ' . $cvn_response_code . ' for Order #' . $order_info['order_id']);
			}
		}

		if ($should_reverse) {
			// Attempt to refund the transaction
			$refund_result = $this->refundTransaction($response, $order_info);
			if (!$refund_result['success']) {
				$reverse_reason .= ' - Refund failed: ' . $refund_result['error'];
				if ($this->config->get('payment_globalpayments_transit_debug')) {
					$this->log->write('GlobalPayments TransIT: Refund failed for Order #' . $order_info['order_id'] . ' - ' . $refund_result['error']);
				}
			} else {
				// Add order note about AVS reversal
				$this->load->model('checkout/order');
				$note = sprintf(
					'Payment was reversed due to AVS/CVN validation failure. AVS Code: %s, CVN Code: %s. Transaction ID: %s',
					$avs_response_code ? $avs_response_code : 'N/A',
					$cvn_response_code ? $cvn_response_code : 'N/A',
					isset($response->transactionReference->transactionId) ? $response->transactionReference->transactionId : 'N/A'
				);
				$this->model_checkout_order->addOrderHistory(
					$order_info['order_id'],
					10,  // Use failed status
					$note,
					true
				);
			}
		}

		return array('should_reverse' => $should_reverse, 'reason' => $reverse_reason);
	}

	/**
	 * Extract AVS response code from payment response
	 *
	 * @param object $response Payment gateway response
	 * @return string|null AVS response code or null
	 */
	private function extractAvsResponseCode($response) {
		// Try different property names for AVS response
		if (isset($response->cardIssuerResponse->avsAddressResult)) {
			return $response->cardIssuerResponse->avsAddressResult;
		}
		if (isset($response->avsResponseCode)) {
			return $response->avsResponseCode;
		}
		if (isset($response->avsResult)) {
			return $response->avsResult;
		}
		if (isset($response->avs_result)) {
			return $response->avs_result;
		}
		return null;
	}

	/**
	 * Extract CVN response code from payment response
	 *
	 * @param object $response Payment gateway response
	 * @return string|null CVN response code or null
	 */
	private function extractCvnResponseCode($response) {
		// Try different property names for CVN response
		if (isset($response->cardIssuerResponse->cvvResult)) {
			return $response->cardIssuerResponse->cvvResult;
		}
		if (isset($response->cvnResponseCode)) {
			return $response->cvnResponseCode;
		}
		if (isset($response->cvnResult)) {
			return $response->cvnResult;
		}
		if (isset($response->cvv_result)) {
			return $response->cvv_result;
		}
		return null;
	}

	/**
	 * Refund transaction for TransIT gateway
	 *
	 * @param object $response Original payment response
	 * @param array $order_info OpenCart order information
	 * @return array Success/error result
	 */
	private function refundTransaction($response, $order_info) {
		try {
			$this->configureGateway();
			$paymentAction = $this->getConfiguredPaymentAction();

			// Get the transaction ID
			$transaction_id = null;
			if (isset($response->transactionReference->transactionId)) {
				$transaction_id = $response->transactionReference->transactionId;
			} elseif (isset($response->transactionId)) {
				$transaction_id = $response->transactionId;
			}

			if (empty($transaction_id)) {
				return array('success' => false, 'error' => 'Transaction ID not found in response');
			}

			// Get the amount
			$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

			// Reverse authorizations with a void; refund settled charges.
			if ($paymentAction === 'authorize') {
				if ($response instanceof Transaction && method_exists($response, 'void')) {
					$refund_response = $response->void()->execute();
				} else {
					$refund_response = Transaction::fromId($transaction_id)->void()->execute();
				}
			} else {
				if ($response instanceof Transaction && method_exists($response, 'refund')) {
					$refund_response = $response->refund(floatval($amount))
						->withCurrency($order_info['currency_code'])
						->withClientTransactionId($this->generateTransactionId())
						->execute();
				} else {
					$refund_response = Transaction::fromId($transaction_id)->refund(floatval($amount))
						->withCurrency($order_info['currency_code'])
						->withClientTransactionId($this->generateTransactionId())
						->execute();
				}
			}

			if ($refund_response && isset($refund_response->responseCode) && ($refund_response->responseCode === 'A0000' || $refund_response->responseCode === '00')) {
				return array('success' => true, 'error' => '');
			} else {
				$error_msg = isset($refund_response->responseMessage) ? $refund_response->responseMessage : 'Refund failed with unknown error';
				return array('success' => false, 'error' => $error_msg);
			}

		} catch (\Throwable $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}
}