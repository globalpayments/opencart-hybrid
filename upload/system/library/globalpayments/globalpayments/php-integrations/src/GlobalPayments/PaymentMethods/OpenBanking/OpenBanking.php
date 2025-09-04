<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\OpenBanking;

use GlobalPayments\Api\Utils\GenerationUtils;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\AbstractPaymentMethod;

class OpenBanking extends AbstractPaymentMethod
{
	public const PAYMENT_METHOD_ID = 'globalpayments_openbanking';

	public $paymentMethodId = self::PAYMENT_METHOD_ID;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $defaultTitle = 'Bank Payment';

	public $currencies;

	/**
	 * {@inheritdoc}
	 */
	public function getMethodAvailability()
	{
		$countries = [];
		if (!empty($this->countries)) {
			$countries = explode("|", $this->countries);
		}

		return [
			'GBP' => $countries,
			'EUR' => $countries,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrontendPaymentMethodOptions() {

		return [];
	}

	public function checkIfPaymentAllowed($order) {

		$availability = $this->getMethodAvailability();
		$currency = $order->currency;
		$billingCountry = $order->billingAddress['country'];
		$countriesFilter = !empty($availability[$currency]);

		if (!isset($availability[$currency])) {
			return false;
		}

		if (!in_array($currency, explode(',', $this->currencies))){
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

			$sanitizedUrl = filter_var($modifiedUrl, FILTER_SANITIZE_URL);
			if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
				header('Location: ' . $sanitizedUrl);
				exit;
			} else {
				throw new \Exception('Invalid redirect URL.');
			}
		}
	}

	public function openBankingCancel()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
		$queryString = $_SERVER['QUERY_STRING'];
		if (!empty($queryString) && strpos($queryString, 'openBankingCancel&amp;?') !== false) {
			$modifiedUrl = str_replace('openBankingCancel&amp;?', 'processOpenBankingCancel&', $currentUrl);

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
