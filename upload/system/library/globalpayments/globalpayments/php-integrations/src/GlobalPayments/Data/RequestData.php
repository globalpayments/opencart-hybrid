<?php

namespace GlobalPayments\PaymentGatewayProvider\Data;

use GlobalPayments\Api\Entities\ThreeDSecure;

class RequestData {
	/**
	 * Gateway Id.
	 *
	 * @var string
	 */
	public $gatewayId;

	/**
	 * Identifies who provides the digital wallet for the Payer.
	 *
	 * @var stirng
	 */
	public $mobileType;

	/**
	 * Payment token Id from Platform Vault. This should be set if the customer pays with a stored card.
	 *
	 * @var int
	 */
	public $paymentTokenId;

	/**
	 * Payment token from Platform Vault/Digital Wallets. This should be set if the customer pays with a stored card or Digital Wallet.
	 *
	 * @var string
	 */
	public $paymentToken;

	/**
	 * Payment token response from tokenization (received from the GP JS lib).
	 *
	 * @var string
	 */
	public $paymentTokenResponse;

	/**
	 * Payment token response from Digital Wallets. This should be set if the customer pays with a stored card in Digital Wallets.
	 *
	 * @var string
	 */
	protected $digitalWalletPaymentTokenResponse;

	/**
	 * Should save payment token in Platform Vault for later use.
	 *
	 * @var bool
	 */
	public $saveCard;

	/**
	 * @var OrderData
	 */
	public $order;

	/**
	 * Unique identifier to reference the authentication data.
	 * e.g. AUT_uzFr7t4VOqxdLDI44hHmXIjHtOOE8d
	 *
	 * @var string
	 */
	public $serverTransactionId;

	public $authenticationSource;
	public $authenticationRequestType;
	public $messageCategory;
	public $challengeRequestIndicator;
	public $challengeWindow;
	public $browserData;

	/**
	 * The authentication information that must be submitted when creating a transaction to achieve fraud liability shift from the merchant.
	 *
	 * @var ThreeDSecure
	 */
	protected $theeDSecure;

	/**
	 * Contains the value a merchant wishes to appear on the payer's payment method statement for this transaction.
	 * (set by the merchant in Admin Dashboard as Order Transaction Descriptor)
	 *
	 * @var string
	 */
	public $dynamicDescriptor;

	public $transactionId;

	public $applePayValidationUrl;

	public $requestType;

	public $bnpl;

	public $openBanking;

	public function __set($name, $value) {
		switch ($name) {
			case 'digitalWalletPaymentTokenResponse':
				$this->paymentToken = self::removeSlashesFromDigitalWalletToken($value);
				break;
			case 'order':
				if ( ! ($value instanceof OrderData)) {
					throw new \Exception('Unable to set Order request data.');
				}
				$this->order = $value;
				break;
			case 'theeDSecure':
				if ( ! ($value instanceof ThreeDSecure)) {
					throw new \Exception('Unable to set 3D Secure request data.');
				}
				$this->theeDSecure = $value;
				break;
		}
	}

	/**
	 * Transfer object properties to another object of different class.
	 *
	 * @param $object
	 * @param oject|array $data
	 *
	 * @return mixed
	 */
	public static function setDataObject($object, $data) {
		if (empty($data)) {
			return $object;
		}
		if (is_object($data)) {
			$dataObjectProperties = get_object_vars($data);
		} elseif (is_array($data)) {
			$dataObjectProperties = $data;
		} else {
			return $object;
		}

		foreach ($dataObjectProperties as $property => $value) {
			if (property_exists($object, $property)) {
				$object->{$property} = $value;
			}
		}

		return $object;
	}

	public static function removeSlashesFromDigitalWalletToken(string $token) {
		$token = str_replace('\\\\', '\\', $token);

		return $token;
	}
}
