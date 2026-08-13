<?php

namespace GlobalPayments\PaymentGatewayProvider\Utils;

use GlobalPayments\Api\Entities\Enums\TransactionStatus;
use LogicException;
use GlobalPayments\PaymentGatewayProvider\Gateways\GpApiGateway;
use GlobalPayments\Api\Entities\Enums\ShaHashType;

class Utils {
	/**
	 * Get request headers and request content
	 *
	 */
	public static function getRequest() {
		$headers = self::getAllHeaders();
		$rawContent = file_get_contents('php://input');

		if (isset($headers['Content-Encoding']) && false !== strpos($headers['Content-Encoding'], 'gzip')) {
			$rawContent = gzdecode($rawContent);
		}

		$requestMethod = $_SERVER['REQUEST_METHOD'];
		$queryParams = $_GET;
		$bodyParams = $_POST;
		$headers = array_change_key_case($headers, CASE_UPPER);

		$request = [
			'method' => $requestMethod,
			'get' => $queryParams,
			'post' => $bodyParams,
			'headers' => $headers,
			'body' => $rawContent,
		];

		return $request;
	}

	private static function getAllHeaders() {
		$headers = [];

		// Check if the function apache_request_headers() exists
		if (function_exists('apache_request_headers')) {
			$apache_headers = apache_request_headers();
			if ($apache_headers !== false) {
				$headers = $apache_headers;
			}
		}

		// If apache_request_headers() is not available or failed, try to retrieve headers manually
		if (empty($headers)) {
			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) === 'HTTP_') {
					$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
					$headers[$header] = $value;
				}
			}
		}

		return $headers;
	}

	public static function sanitizeString($string, $removeWhitespace = false) {
		$transliteratedString = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
		$sanitizedString = preg_replace('/[^a-zA-Z0-9\- ]/', '', $transliteratedString);
		if ($removeWhitespace) {
			$sanitizedString = preg_replace('/\s+/', '', $sanitizedString);
		}

		return $sanitizedString;
	}

	public static function mapResponseCodeToFriendlyMessage($responseCode = '') {
		switch ($responseCode) {
			case TransactionStatus::DECLINED:
			case 'FAILED':
				return 'Your payment was unsuccessful. Please try again or use a different payment method.';
			default:
				return 'An error occurred while processing the payment. Please try again or use a different payment method.';
		}
	}

	public static function getJsLibVersion() {
		return '5.0.1';
	}

	/**
	 * Check if Visa installments are supported for a country/currency pair.
	 */
	public static function isVisaInstallmentsSupported(?string $country, ?string $currency): bool {
		$supportedCombinations = [
			'GB' => 'GBP',
			'CA' => 'CAD',
		];

		if (!is_string($country) || !is_string($currency)) {
			return false;
		}

		$countryUpper = strtoupper($country);
		$currencyUpper = strtoupper($currency);

		return isset($supportedCombinations[$countryUpper])
			&& $supportedCombinations[$countryUpper] === $currencyUpper;
	}

	public static function validateSignature($appKey): void
	{
        $gateway = new GpApiGateway();

        $querryString = substr($_SERVER["QUERY_STRING"], strpos($_SERVER["QUERY_STRING"], 'X-GP-Signature'));

        $signature = substr(substr($querryString, 0, strpos($querryString, 'id=') - 1), 15);

        $querryStringSubString = substr($_SERVER["QUERY_STRING"], strpos($_SERVER["QUERY_STRING"], '&id=') + 1);

        $calculatedSignature = hash(
            ShaHashType::SHA512,
						$querryStringSubString . $appKey
        );

        if ($signature !== $calculatedSignature) {
			error_log("Invaild signature");
			error_log($querryStringSubString);
		throw new LogicException('Invalid request signature.');

        };
    }
}
