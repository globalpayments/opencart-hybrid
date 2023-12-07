<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\OpenBanking;

use GlobalPayments\Api\Entities\Enums\BNPLShippingMethod;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\AbstractPaymentMethod;
use GlobalPayments\Api\Utils\GenerationUtils;

abstract class AbstractOpenBanking extends AbstractPaymentMethod {

	/**
	 * Currencies and countries this payment method is allowed for.
	 *
	 * @return array
	 */
	abstract public function getMethodAvailability();

	abstract public function getFrontendPaymentMethodOptions();

	public function checkIfPaymentAllowed($order) {
		$availability = $this->getMethodAvailability();
		$currency = $order->currency;
		$billingCountry = $order->billingAddress['country'];
		$shippingCountry = $order->shippingAddress['country'];
		$countriesFilter = !empty($availability[$currency]);

		if (!isset($availability[$currency])) {
			return false;
		}

		if (!empty($order->cart) && $countriesFilter) {
			 if (!in_array($billingCountry, $availability[$currency])) {
				return false;
			}
		}

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
				$toHash = http_build_query($params);
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
			'return' => $baseUrl . $this->paymentMethodId . '/openBankingReturn&amp;',
			'status' => $baseUrl . $this->paymentMethodId . '/openBankingStatus&amp;',
			'cancel' => $baseUrl . $this->paymentMethodId . '/openBankingCancel&amp;',
		];
	}

	public function openBankingReturn()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
  	 	$queryString = $_SERVER['QUERY_STRING'];
  	  	if (!empty($queryString) && strpos($queryString, 'openBankingReturn&amp;?') !== false) {
			$modifiedUrl = str_replace('openBankingReturn&amp;?', 'processOpenBankingReturn&', $currentUrl);
			header('Location: ' . $modifiedUrl);
	 		exit;
  	  	}
	}

	public function openBankingCancel()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
  	 	$queryString = $_SERVER['QUERY_STRING'];
  	  	if (!empty($queryString) && strpos($queryString, 'openBankingCancel&amp;?') !== false) {
			$modifiedUrl = str_replace('openBankingCancel&amp;?', 'processOpenBankingCancel&', $currentUrl);
			header('Location: ' . $modifiedUrl);
	 		exit;
  	  	}
	}
}
