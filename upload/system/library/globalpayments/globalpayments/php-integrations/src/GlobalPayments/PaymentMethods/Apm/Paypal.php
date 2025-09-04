<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\Apm;

use GlobalPayments\PaymentGatewayProvider\PaymentMethods\AbstractPaymentMethod;
use GlobalPayments\Api\Utils\GenerationUtils;
use GlobalPayments\Api\Entities\Enums\AlternativePaymentType;

class Paypal extends AbstractPaymentMethod {
	public const PAYMENT_METHOD_ID = 'globalpayments_paypal';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	public $provider = AlternativePaymentType::PAYPAL;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Pay with PayPal';

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {
		return [];
	}

	public function checkIfPaymentAllowed($order) {
		return true;
	}

	public function validateRequest($request) {
		$requestMethod = $request->server['REQUEST_METHOD'] ?? $request['method'];
		switch ($requestMethod) {
			case 'GET':
				$xgpSignature = $request->get['X-GP-Signature'];

				$params = $request->get;
				unset($params['X-GP-Signature']);
				unset($params['route']);

				$toHash = http_build_query([
					'id' => $params['id'] ?? '',
					'session_token' => $params['session_token'] ?? '',
					'payer_reference' => $params['payer_reference'] ?? '',
					'pasref' => $params['pasref'] ?? '',
					'action_type' => $params['action_type'] ?? '',
					'action_id' => $params['action_id'] ?? '',
				]);
				break;
			case 'POST':
				$xgpSignature = $request['headers']['X-GP-SIGNATURE'];
				$toHash = $request['body'];
				break;
			default:
				throw new \Exception('This request method is not supported.');
		}

		$genSignature = GenerationUtils::generateXGPSignature(
			$toHash,
			$this->gateway->getCredentialSetting('appKey')
		);

		if ($xgpSignature !== $genSignature) {
			throw new \Exception('Invalid request signature.');
		}

		return true;
	}

	public function getCallbackUrls()
	{
		$baseUrl = $this->gateway->baseUrl;

		return [
			'return' => $baseUrl . $this->paymentMethodId . '/paypalReturn&amp;',
			'status' => $baseUrl . $this->paymentMethodId . '/paypalStatus&amp;',
			'cancel' => $baseUrl . $this->paymentMethodId . '/paypalCancel&amp;',
		];
	}

	public function paypalReturn()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
		$queryString = $_SERVER['QUERY_STRING'];
		if (!empty($queryString) && strpos($queryString, 'paypalReturn&amp;?') !== false) {
			$modifiedUrl = str_replace('paypalReturn&amp;?', 'processPaypalReturn&', $currentUrl);
			
			$sanitizedUrl = filter_var($modifiedUrl, FILTER_SANITIZE_URL);
			if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
				header('Location: ' . $sanitizedUrl);
				exit;
			} else {
				throw new \Exception('Invalid redirect URL.');
			}
		}
	}

	public function paypalCancel()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
		$queryString = $_SERVER['QUERY_STRING'];
		if (!empty($queryString) && strpos($queryString, 'paypalCancel&amp;?') !== false) {
			$modifiedUrl = str_replace('paypalCancel&amp;?', 'processPaypalCancel&', $currentUrl);
			
			$sanitizedUrl = filter_var($modifiedUrl, FILTER_SANITIZE_URL);
			if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
				header('Location: ' . $sanitizedUrl);
				exit;
			} else {
				throw new \Exception('Invalid redirect URL.');
			}
		}
	}
}
