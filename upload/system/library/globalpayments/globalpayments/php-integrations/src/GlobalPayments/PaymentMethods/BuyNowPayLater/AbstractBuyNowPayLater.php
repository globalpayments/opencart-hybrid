<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods\BuyNowPayLater;

use GlobalPayments\Api\Entities\Enums\BNPLShippingMethod;
use GlobalPayments\PaymentGatewayProvider\PaymentMethods\AbstractPaymentMethod;
use GlobalPayments\Api\Utils\GenerationUtils;

abstract class AbstractBuyNowPayLater extends AbstractPaymentMethod {

	/**
	 * Currencies and countries this payment method is allowed for.
	 *
	 * @return array
	 */
	abstract public function getMethodAvailability();

	abstract public function getFrontendPaymentMethodOptions();

	public function isShippingRequired() {
		return false;
	}

	public function checkIfPaymentAllowed($order) {
		$availability = $this->getMethodAvailability();
		$hasVirtualItems = $this->checkIfHasVirtualItems($order);
		$currency = $order->currency;
		$billingCountry = $order->billingAddress['country'];
		$shippingCountry = $order->shippingAddress['country'];
		if (!isset($availability[$currency])) {
			return false;
		}
		if (!empty($order->cart)) {
			if ($this->isShippingRequired() && !$hasVirtualItems) {
				if (!in_array($billingCountry, $availability[$currency])
					|| !in_array($shippingCountry, $availability[$currency])) {
						return false;
				}
			} elseif (!in_array($billingCountry, $availability[$currency])) {
				return false;
			}
		}

		return true;
	}

	private function checkIfHasVirtualItems($order) {
		$orderItems = $order->cart;
		$hasVirtualItems = false;
		foreach ($orderItems as $item) {
			if (!$item['shipping']) {
				return true;
			}
		}

		return $hasVirtualItems;
	}

	public function validateCustomerDetails($session, $customer, $isCustomerLogged = false) {

		$hasPostCode = true;
		if (empty($session->data['shipping_address']['postcode'])) {
			$hasPostCode = false;
			if (!empty($session->data['payment_address']['postcode'])) {
				$hasPostCode = true;
			}
		}

		if ($isCustomerLogged) {
			$valid = $hasPostCode && !empty($customer->getTelephone());
		} else {
			$valid = $hasPostCode && (
				array_key_exists('guest', $customer->session->data) &&
				array_key_exists('telephone', $customer->session->data['guest']) &&
				!empty($customer->session->data['guest']['telephone'])
				);
		}

		return $valid;
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
			'return' => $baseUrl . $this->paymentMethodId . '/bnplReturn&amp;',
			'status' => $baseUrl . $this->paymentMethodId . '/bnplStatus&amp;',
			'cancel' => $baseUrl . $this->paymentMethodId . '/bnplCancel&amp;',
		];
	}

	public function bnplReturn()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
  	 	$queryString = $_SERVER['QUERY_STRING'];
  	  	if (!empty($queryString) && strpos($queryString, 'bnplReturn&amp;?') !== false) {
			$modifiedUrl = str_replace('bnplReturn&amp;?', 'processBnplReturn&', $currentUrl);
			header('Location: ' . $modifiedUrl);
	 		exit;
  	  	}
	}

	public function bnplCancel()
	{
		$currentUrl = $_SERVER['REQUEST_URI'];
  	 	$queryString = $_SERVER['QUERY_STRING'];
  	  	if (!empty($queryString) && strpos($queryString, 'bnplCancel&amp;?') !== false) {
			$modifiedUrl = str_replace('bnplCancel&amp;?', 'processBnplCancel&', $currentUrl);
			header('Location: ' . $modifiedUrl);
	 		exit;
  	  	}
	}
}
