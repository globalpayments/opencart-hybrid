<?php

use GlobalPayments\Api\Entities\Enums\BankPaymentStatus;
use GlobalPayments\Api\Entities\Enums\PaymentMethodName;
use GlobalPayments\Api\Entities\Reporting\TransactionSummary;

class ModelExtensionPaymentGlobalPaymentsUcp extends Model
{
	public function getMethod($address, $total): array|bool
	{
		$this->load->language('extension/payment/globalpayments_ucp');

		$status = $this->getStatus();
		if (false === $status) {
			return false;
		}
		$method_data = [];
		$method_data_title = $this->config->get('payment_globalpayments_ucp_title');
		if (empty($method_data_title)) {
			$method_data_title = $this->language->get('placeholder_title');
		}
		$method_data = [
			'code'       => 'globalpayments_ucp',
			'title'      => $method_data_title,
			'terms'      => '',
			'sort_order' => $this->config->get('payment_globalpayments_ucp_sort_order')
		];


		return $method_data;
	}

	public function getCards(int $customer_id, string $gateway_id): array
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_card WHERE `customer_id` = '" 
			. (int)$customer_id . "' AND `gateway_id` = '" . $this->db->escape($gateway_id) . "' ORDER BY `token_id` DESC");

		return $query->rows;
	}

	public function getCard(int $token_id): ?string
	{
		$query = $this->db->query("SELECT token FROM " . DB_PREFIX . "globalpayments_card WHERE `token_id` = '" . (int)$token_id . "'");

		return $query->row['token'] ?? null;
	}

	public function addCard(
		string $gateway_id,
		int $customer_id,
		string $token,
		string $card_type,
		string $card_last4,
		string $expiry_year,
		string $expiry_month,
		int $is_default = 0
	): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_card`
		SET `gateway_id` = '" . $this->db->escape($gateway_id) . "',
		    `customer_id` = '" . (int)$customer_id . "',
		    `token` = '" . $this->db->escape($token) . "',
		    `card_type` = '" . $this->db->escape($card_type) . "',
		    `card_last4` = '" . $this->db->escape($card_last4) . "',
		    `expiry_year` = '" . $this->db->escape($expiry_year) . "',
		    `expiry_month` = '" . $this->db->escape($expiry_month) . "',
		    `is_default` = '" . (int)$is_default . "'");
	}

	public function deleteCard(int $customer_id, int $token_id): mixed
	{
		if (empty($this->getCard($token_id))) {
			return null;
		}

		return $this->db->query("DELETE FROM " . DB_PREFIX . "globalpayments_card WHERE `customer_id` = '" . (int)$customer_id . "' AND `token_id` = '" . (int)$token_id . "'");
	}

	public function defaultCard(int $customer_id, int $token_id): mixed
	{
		if (empty($this->getCard($token_id))) {
			return null;
		}
		$this->db->query("UPDATE " . DB_PREFIX . "globalpayments_card SET `is_default` = '0' WHERE `customer_id` = '" . (int)$customer_id . "'");

		return $this->db->query("UPDATE " . DB_PREFIX . "globalpayments_card SET `is_default` = '1' WHERE `customer_id` = '" . (int)$customer_id . "' AND `token_id` = '" . (int)$token_id . "'");
	}


	public function addTransaction(
		int $order_id,
		string $gateway_id,
		string $payment_action,
		float $amount,
		string $currency,
		object $gatewayResponse
	): void {
		if (
			$gatewayResponse instanceof TransactionSummary && (
				in_array(
					$gatewayResponse->paymentType,
					[
						PaymentMethodName::BANK_PAYMENT,
						PaymentMethodName::APM
					]
				) || $gatewayResponse->gatewayResponseMessage === 'REQUEST_SUCCESS'
			)
		) {
			$transactionId = $gatewayResponse->transactionId;
			$responseCode = $gatewayResponse->gatewayResponseCode ?? BankPaymentStatus::SUCCESS;;
			$responseMessage = $gatewayResponse->gatewayResponseMessage;
			$referenceNumber = $gatewayResponse->referenceNumber;
			$transactionDate = $gatewayResponse->transactionDate->format("Y-m-d\TH:i:s.u\Z");
		} else {
			$transactionId = $gatewayResponse->transactionReference->transactionId;
			$responseCode = $gatewayResponse->responseCode;
			$responseMessage = $gatewayResponse->responseMessage;
			$referenceNumber = $gatewayResponse->transactionReference->clientTransactionId;
			$transactionDate = $gatewayResponse->timestamp;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transaction`
		SET `order_id` = '" . (int)$order_id . "',
		`gateway_id` = '" . $this->db->escape($gateway_id) . "',
		`payment_action` = '" . $this->db->escape($payment_action) . "',
		`gateway_transaction_id` = '" . $this->db->escape($transactionId) . "',
		`response_code` = '" . $this->db->escape($responseCode) . "',
		`response_message` = '" . $this->db->escape($responseMessage) . "',
		`reference` = '" . $this->db->escape($referenceNumber) . "',
		`amount` = '" . (float)$amount . "',
		`currency` = '" . $currency . "',
		`time_created` = '" . $transactionDate . "'");
	}

	private function getStatus(): bool
	{
		$evnioment = 'payment_globalpayments_ucp_' .
			(($this->config->get('payment_globalpayments_ucp_is_production')) ? "" : "sandbox_");
		return true == $this->config->get('payment_globalpayments_ucp_enabled')
		&& !empty(trim($this->config->get($evnioment . 'app_id')))
		&& !empty(trim($this->config->get($evnioment . 'app_key')))
		&& !empty(trim($this->config->get($evnioment . 'account_name')));
	}
}
