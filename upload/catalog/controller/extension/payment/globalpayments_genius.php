<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\AbstractGateway;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

// Genius specific imports
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\ServiceConfigs\Gateways\GeniusConfig;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
class ControllerExtensionPaymentGlobalPaymentsGenius extends Controller {
	private $gateway_id = 'globalpayments_genius';
	private $lastReusableToken = '';

	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway($this->gateway_id);
	}

	public function index() {

		$this->load->language('extension/payment/globalpayments_genius');
		$this->setOrder();
		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_credit_card'] = $this->language->get('text_credit_card');
		
		$data['entry_cc_owner'] = $this->language->get('entry_cc_owner');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
		$data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
		$data['entry_save_card'] = $this->language->get('entry_save_card');
		$data['entry_allow_card_saving'] = $this->language->get('entry_save_card');
		$data['text_saved_cards'] = $this->language->get('text_saved_cards');
		$data['text_use_new_card'] = $this->language->get('text_use_new_card');
		$data['text_card_ending'] = $this->language->get('text_card_ending');
		$data['text_expires'] = $this->language->get('text_expires');

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_please_wait'] = $this->language->get('text_please_wait');

		$data['action'] = $this->url->link('extension/payment/globalpayments_genius/confirm', '', true);
		$data['payment_tab_option'] = 'new';
		$data['gateway'] = $this->globalpayments->gateway;
		$data['allow_card_saving'] = (bool)$this->config->get('payment_globalpayments_genius_card');
		
		// Check if customer is logged in for saved cards
		if ($this->customer->isLogged()) {
			$data['customer_logged'] = true;
			$this->load->model('extension/payment/globalpayments_genius');
			$data['saved_cards'] = $this->model_extension_payment_globalpayments_genius->getCustomerCards($this->customer->getId());
		} else {
			$data['customer_logged'] = false;
			$data['saved_cards'] = [];
		}

		// Genius Configuration
		$live_mode = $this->config->get('payment_globalpayments_genius_live_mode');
		
		// Determine environment
		$data['environment'] = $live_mode ? 'production' : 'sandbox';
		
		// Get Genius credentials
		$data['merchant_name'] = $live_mode 
			? $this->config->get('payment_globalpayments_genius_live_merchant_name')
			: $this->config->get('payment_globalpayments_genius_sandbox_merchant_name');
			
		$data['merchant_site_id'] = $live_mode 
			? $this->config->get('payment_globalpayments_genius_live_merchant_site_id')
			: $this->config->get('payment_globalpayments_genius_sandbox_merchant_site_id');
			
		$data['merchant_key'] = $live_mode 
			? $this->config->get('payment_globalpayments_genius_live_merchant_key')
			: $this->config->get('payment_globalpayments_genius_sandbox_merchant_key');
			
		$data['web_api_key'] = $live_mode 
			? $this->config->get('payment_globalpayments_genius_live_web_api_key')
			: $this->config->get('payment_globalpayments_genius_sandbox_web_api_key');
		
		// Use GlobalPayments.js SDK URL
		$data['globalpayments_js_url'] = 'https://js.globalpay.com/v1/globalpayments.min.js';

		// Environment indicator
		if (!$live_mode) {
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
		
		return $this->load->view('extension/payment/globalpayments_genius', $data);
	}

	public function confirm() {
		$this->load->language('extension/payment/globalpayments_genius');
		
		try {
			if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
				throw new Exception('Invalid request method');
			}

			$saveCardRequested = false;
			if (isset($this->request->post['save_card']) && $this->request->post['save_card'] == '1') {
				$saveCardRequested = true;
			}
			if (isset($this->request->post[$this->gateway_id]['saveCard']) && $this->request->post[$this->gateway_id]['saveCard'] == '1') {
				$saveCardRequested = true;
			}

			$this->load->model('checkout/order');
			if (!isset($this->session->data['order_id'])) {
				throw new Exception('No order found in session');
			}
			
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			if (!$order_info) {
				throw new Exception($this->language->get('error_order_not_found'));
			}

			$this->load->model('extension/payment/globalpayments_genius');
			if ($this->hasSuccessfulExistingTransaction((int)$order_info['order_id'])) {
				$this->cart->clear();
				unset($this->session->data['error']);
				if (isset($this->request->post['ajax']) ||
					(isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
					strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode([
						'success' => true,
						'redirect' => $this->url->link('checkout/success', '', true)
					]));
				} else {
					$this->response->redirect($this->url->link('checkout/success', '', true));
				}
				return;
			}

			$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
			$currency = $order_info['currency_code'];
			$paymentAction = $this->getConfiguredPaymentAction();

			// Process payment based on available data
			$response = null;
			if (isset($this->request->post['saved_card_token']) && $this->request->post['saved_card_token'] !== 'new') {
				if (!$this->customer->isLogged()) {
					throw new Exception('Must be logged in to use a saved card');
				}
				$response = $this->processSavedCardPayment((int)$this->request->post['saved_card_token'], $amount, $currency, $order_info);
			} else if (isset($this->request->post['payment_token']) && !empty($this->request->post['payment_token'])) {
				$response = $this->processGeniusTokenPayment($this->request->post, $amount, $currency, $order_info, $saveCardRequested);
			} else if (
				!empty($this->request->post['card_number']) ||
				!empty($this->request->post['expiry_month']) ||
				!empty($this->request->post['expiry_year']) ||
				!empty($this->request->post['cvv'])
			) {
				throw new Exception('Raw card data is not accepted. Please use the secure hosted payment flow.');
			} else {
				throw new Exception('Missing tokenized payment data');
			}

			if (!$response) {
				throw new Exception('Payment processing failed - no response received');
			}

			if (isset($response->responseCode) && ($response->responseCode === 'A0000' || $response->responseCode === '00' || $response->responseCode === 'SUCCESS')) {
				$avsCvnCheck = $this->validateAvsCvnResult($response);

				if (!$avsCvnCheck['passed']) {
					$this->reverseRejectedTransaction($response, $amount, $currency, $paymentAction);
					throw new Exception($avsCvnCheck['reason']);
				}

				$order_status_id = (int) $this->config->get('payment_globalpayments_genius_order_status_id');
				if (!$order_status_id) {
					$order_status_id = 2;
				}

				$comment = sprintf(
					'Genius %s Successful - Transaction ID: %s, Auth Code: %s, Response: %s',
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
				try {
					$this->model_extension_payment_globalpayments_genius->addTransaction(
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
				if ($this->customer->isLogged() && $saveCardRequested && isset($this->request->post['paymentTokenResponse'])) {
					try {
						// Prefer the reusable VaultToken returned by Genius transaction response.
						$storedToken = '';
						if ($this->lastReusableToken !== '') {
							$storedToken = (string)$this->lastReusableToken;
						}
						if (isset($response->token) && !empty($response->token)) {
							$storedToken = (string)$response->token;
						}
						if ($storedToken === '' && isset($response->transactionId) && !empty($response->transactionId)) {
							$storedToken = (string)$response->transactionId;
						}

						$decodedTokenResponse = html_entity_decode($this->request->post['paymentTokenResponse'], ENT_QUOTES, 'UTF-8');
						$tokenData = json_decode($decodedTokenResponse, true);
						if (!$tokenData && is_string($decodedTokenResponse)) {
							$tokenData = json_decode(stripslashes($decodedTokenResponse), true);
						}
						if ($tokenData) {
							$details = isset($tokenData['details']) && is_array($tokenData['details']) ? $tokenData['details'] : array();
							if ($storedToken === '') {
								if (isset($tokenData['paymentReference'])) {
									$storedToken = (string)$tokenData['paymentReference'];
								} elseif (isset($tokenData['payment_reference'])) {
									$storedToken = (string)$tokenData['payment_reference'];
								} elseif (isset($tokenData['token'])) {
									$storedToken = (string)$tokenData['token'];
								} elseif (isset($tokenData['paymentToken'])) {
									$storedToken = (string)$tokenData['paymentToken'];
								}
							}

							if (strpos($storedToken, 'OTT_') === 0) {
								$storedToken = '';
							}

							if (!empty($storedToken)) {
								$cardType   = isset($details['cardType']) ? $details['cardType'] : 'unknown';
								$cardLast4  = isset($details['cardLast4']) ? $details['cardLast4'] : '****';
								$expiryYear  = isset($details['expiryYear']) ? $details['expiryYear'] : '';
								$expiryMonth = isset($details['expiryMonth']) ? $details['expiryMonth'] : '';
								$this->model_extension_payment_globalpayments_genius->addCard(
									$this->customer->getId(),
									$storedToken,
									$cardType,
									$cardLast4,
									$expiryYear,
									$expiryMonth !== '' ? sprintf('%02d', (int)$expiryMonth) : ''
								);
							}
						}
					} catch (Exception $e) {
						// Do not block checkout success when vault save fails.
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

			if (stripos($error_message, 'duplicate') !== false) {
				$orderId = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;
				if ($orderId > 0) {
					$this->load->model('extension/payment/globalpayments_genius');
					if ($this->hasSuccessfulExistingTransaction($orderId)) {
						$this->cart->clear();
						unset($this->session->data['error']);
						if (isset($this->request->post['ajax']) ||
							(isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
							strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
							$this->response->addHeader('Content-Type: application/json');
							$this->response->setOutput(json_encode([
								'success' => true,
								'redirect' => $this->url->link('checkout/success', '', true)
							]));
						} else {
							$this->response->redirect($this->url->link('checkout/success', '', true));
						}
						return;
					}
				}
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
		}
	}

	private function hasSuccessfulExistingTransaction($orderId) {
		$transactions = $this->model_extension_payment_globalpayments_genius->getTransactions((int)$orderId);
		foreach ($transactions as $transaction) {
			$action = isset($transaction['payment_action']) ? strtolower((string)$transaction['payment_action']) : '';
			$responseCode = isset($transaction['response_code']) ? strtoupper((string)$transaction['response_code']) : '';
			$responseMessage = isset($transaction['response_message']) ? strtoupper((string)$transaction['response_message']) : '';
			$isPaymentAction = in_array($action, array('authorize', 'charge', 'capture'), true);
			$isSuccess = in_array($responseCode, array('00', 'SUCCESS', 'A0000'), true) || $responseMessage === 'APPROVED' || $responseMessage === 'CAPTURED';

			if ($isPaymentAction && $isSuccess) {
				return true;
			}
		}

		return false;
	}

	private function configureGateway() {
		$geniusConfig = new GeniusConfig();
		$live_mode = $this->config->get('payment_globalpayments_genius_live_mode');
		
		if ($live_mode) {
			$geniusConfig->merchantName = $this->config->get('payment_globalpayments_genius_live_merchant_name');
			$geniusConfig->merchantSiteId = $this->config->get('payment_globalpayments_genius_live_merchant_site_id');
			$geniusConfig->merchantKey = $this->config->get('payment_globalpayments_genius_live_merchant_key');
			$geniusConfig->registerNumber = $this->config->get('payment_globalpayments_genius_live_web_api_key');
		} else {
			$geniusConfig->merchantName = $this->config->get('payment_globalpayments_genius_sandbox_merchant_name');
			$geniusConfig->merchantSiteId = $this->config->get('payment_globalpayments_genius_sandbox_merchant_site_id');
			$geniusConfig->merchantKey = $this->config->get('payment_globalpayments_genius_sandbox_merchant_key');
			$geniusConfig->registerNumber = $this->config->get('payment_globalpayments_genius_sandbox_web_api_key');
		}
		
		ServicesContainer::configureService($geniusConfig);
	}

	private function processGeniusTokenPayment($cardData, $amount, $currency, $orderInfo, $saveCardRequested = false) {
		$paymentToken = $cardData['payment_token'];
		if ($saveCardRequested) {
			$this->configureGateway();
			$reusableToken = $this->createReusableTokenFromPaymentReference($paymentToken, $orderInfo);
			if ($reusableToken !== '') {
				$paymentToken = $reusableToken;
				$this->lastReusableToken = $reusableToken;
			}
		}

		try {
			return $this->executePaymentTransaction($paymentToken, $amount, $currency, $orderInfo, $saveCardRequested, isset($cardData['paymentTokenResponse']) ? $cardData['paymentTokenResponse'] : '');

		} catch (Exception $e) {
			throw new Exception('Payment processing failed: ' . $e->getMessage());
		}
	}

	private function createReusableTokenFromPaymentReference($paymentReference, $orderInfo) {
		if (empty($paymentReference)) {
			return '';
		}

		try {
			$card = new CreditCardData();
			$card->token = $paymentReference;
			$card->cardHolderName = $orderInfo['payment_firstname'] . ' ' . $orderInfo['payment_lastname'];

			$tokenizeResponse = $card->tokenize()->execute();
			if (isset($tokenizeResponse->token) && !empty($tokenizeResponse->token)) {
				$token = (string)$tokenizeResponse->token;
				if (strpos($token, 'OTT_') !== 0) {
					return $token;
				}
			}

			if (isset($tokenizeResponse->transactionId) && !empty($tokenizeResponse->transactionId)) {
				$token = (string)$tokenizeResponse->transactionId;
				if (strpos($token, 'OTT_') !== 0) {
					return $token;
				}
			}
		} catch (Exception $e) {
			// Non-blocking: fallback to one-time token flow when reusable tokenization fails.
		}

		return '';
	}

	private function processSavedCardPayment($tokenId, $amount, $currency, $orderInfo) {
		$tokenId = (int)$tokenId;
		if ($tokenId <= 0) {
			throw new Exception('Invalid saved card selected');
		}
		$this->load->model('extension/payment/globalpayments_genius');
		$savedCard = $this->model_extension_payment_globalpayments_genius->getCustomerCards($this->customer->getId());

		$cardToken = null;
		foreach ($savedCard as $card) {
			if ((int)$card['token_id'] === $tokenId) {
				$cardToken = $card['token'];
				break;
			}
		}

		if (!$cardToken) {
			throw new Exception('Invalid saved card selected');
		}

		try {
			return $this->executePaymentTransaction($cardToken, $amount, $currency, $orderInfo, false, '');

		} catch (Exception $e) {
			if (stripos($e->getMessage(), 'payment information not available') !== false) {
				throw new Exception('Saved card is no longer valid. Please use a new card and save it again.');
			}

			throw new Exception('Payment processing failed: ' . $e->getMessage());
		}
	}

	private function generateTransactionId() {
		$orderId = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;
		$micro = str_replace('.', '', sprintf('%.6f', microtime(true)));
		$rand = function_exists('random_int') ? random_int(1000, 9999) : mt_rand(1000, 9999);

		return 'OC_GENIUS_' . $orderId . '_' . $micro . '_' . $rand;
	}

	private function getConfiguredPaymentAction() {
		$paymentAction = $this->config->get('payment_globalpayments_genius_payment_action');

		return $paymentAction === 'authorize' ? 'authorize' : 'charge';
	}

	private function getPaymentActionLabel($paymentAction) {
		return $paymentAction === 'authorize' ? 'Authorization' : 'Charge';
	}

	private function getTransactionDescriptor() {
		$descriptor = trim((string)$this->config->get('payment_globalpayments_genius_order_transaction_descriptor'));

		return $descriptor !== '' ? substr($descriptor, 0, 25) : '';
	}

	private function generateInvoiceNumber() {
		$orderId = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;
		$timePart = substr(date('His'), -4);
		$orderPart = str_pad((string)($orderId % 10000), 4, '0', STR_PAD_LEFT);

		return $orderPart . $timePart;
	}

	private function generateGatewayOrderReference() {
		$micro = str_replace('.', '', sprintf('%.6f', microtime(true)));
		$rand = function_exists('random_int') ? random_int(10, 99) : mt_rand(10, 99);

		return 'OCG' . substr($micro, -9) . $rand;
	}

	private function executePaymentTransaction($paymentToken, $amount, $currency, $orderInfo, $saveCard = false, $paymentTokenResponse = '') {
		$this->globalpayments->setGateway($this->gateway_id);

		$requestData = new RequestData();
		$requestData->paymentToken = (string)$paymentToken;
		$requestData->paymentTokenResponse = (string)$paymentTokenResponse;
		$requestData->saveCard = (bool)$saveCard;
		$requestData->requestType = AbstractGateway::getRequestType($this->getConfiguredPaymentAction());
		$requestData->dynamicDescriptor = $this->getTransactionDescriptor();
		$requestData->meta = (object)array(
			'invoiceNumber' => $this->generateInvoiceNumber(),
		);

		$order = new OrderData();
		$platformOrderId = isset($orderInfo['order_id']) ? (string)$orderInfo['order_id'] : (string)$this->session->data['order_id'];
		$order->amount = $amount;
		$order->currency = $currency;
		$order->orderReference = $this->generateGatewayOrderReference();
		$order->reference = $this->generateTransactionId();
		$order->description = 'Order #' . $platformOrderId;

		$requestData->order = $order;

		return $this->globalpayments->gateway->processPayment($requestData);
	}

	private function validateAvsCvnResult($response) {
		if (!$this->config->get('payment_globalpayments_genius_check_avs_cvn')) {
			return array('passed' => true, 'reason' => '');
		}

		$avsResponse = $this->extractAvsResponseCode($response);
		$cvnResponse = $this->extractCvnResponseCode($response);
		$avsRejectConditions = $this->config->get('payment_globalpayments_genius_avs_reject_conditions');
		$cvnRejectConditions = $this->config->get('payment_globalpayments_genius_cvn_reject_conditions');

		$avsRejectConditions = is_array($avsRejectConditions) ? $avsRejectConditions : array();
		$cvnRejectConditions = is_array($cvnRejectConditions) ? $cvnRejectConditions : array();

		if ($avsResponse !== '' && in_array($avsResponse, $avsRejectConditions, true)) {
			return array(
				'passed' => false,
				'reason' => sprintf($this->language->get('error_avs_cvn_rejected'), 'AVS', $avsResponse)
			);
		}

		if ($cvnResponse !== '' && in_array($cvnResponse, $cvnRejectConditions, true)) {
			return array(
				'passed' => false,
				'reason' => sprintf($this->language->get('error_avs_cvn_rejected'), 'CVN', $cvnResponse)
			);
		}

		return array('passed' => true, 'reason' => '');
	}

	private function reverseRejectedTransaction($response, $amount, $currency, $paymentAction) {
		try {
			if ($paymentAction === 'authorize') {
				$response->void()->execute();
				return;
			}

			$response->refund($amount)
				->withCurrency($currency)
				->execute();
		} catch (Exception $exception) {
			// Preserve the original checkout error if reversal fails.
		}
	}

	private function extractAvsResponseCode($response) {
		if (isset($response->avsResponseCode) && $response->avsResponseCode !== null) {
			return (string)$response->avsResponseCode;
		}

		if (isset($response->cardIssuerResponse) && isset($response->cardIssuerResponse->avsAddressResult) && $response->cardIssuerResponse->avsAddressResult !== null) {
			return (string)$response->cardIssuerResponse->avsAddressResult;
		}

		return '';
	}

	private function extractCvnResponseCode($response) {
		if (isset($response->cvnResponseCode) && $response->cvnResponseCode !== null) {
			return (string)$response->cvnResponseCode;
		}

		if (isset($response->cardIssuerResponse) && isset($response->cardIssuerResponse->cvvResult) && $response->cardIssuerResponse->cvvResult !== null) {
			return (string)$response->cardIssuerResponse->cvvResult;
		}

		return '';
	}

	public function getMethod($address, $total) {
		$this->load->model('extension/payment/globalpayments_genius');
		return $this->model_extension_payment_globalpayments_genius->getMethod($address, $total);
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
}
