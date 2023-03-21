<?php

namespace GlobalPayments\PaymentGatewayProvider\Traits;

use GlobalPayments\Api\Entities\Enums\TransactionStatus;

trait ResponseMessageTrait {
	public $errorThreeDSecure = '3DS Authentication failed. Please try again.';
	public $errorThreeDSecureNoLiabilityShift = '3DS Authentication failed. Please try again with another card.';
	public $errorTransactionStatusDeclined = 'Your card has been declined by the bank.';
	public $errorVerifyNotVerified = 'Your card could not be verified.';
	public $errorGatewayResponse = 'An error occurred while processing the card.';

	public function mapResponseCodeToFriendlyMessage($responseCode) {
		switch ($responseCode) {
			case TransactionStatus::DECLINED:
				$error = $this->errorTransactionStatusDeclined;
				break;
			case 'NOT_VERIFIED':
				$error = $this->errorVerifyNotVerified;
				break;
			default:
				$error = $this->errorGatewayResponse;
		}

		return $error;
	}
}
