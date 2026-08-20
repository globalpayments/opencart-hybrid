<?php

class ModelExtensionPaymentGlobalPaymentsGenius extends Model {
	public function install() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_genius_card` (
			  `token_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `customer_id` INT(11) NOT NULL,
			  `token` VARCHAR(255) NOT NULL,
			  `card_type` VARCHAR(50) NOT NULL,
			  `card_last4` CHAR(4) NOT NULL,
			  `expiry_year` CHAR(4) NOT NULL,
			  `expiry_month` CHAR(2) NOT NULL,
			  `is_default` INT(1) DEFAULT 0,
			  `date_added` DATETIME NOT NULL,
			  PRIMARY KEY (`token_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_genius_transaction` (
			  `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
			  `payment_action` ENUM('authorize', 'charge', 'capture', 'refund', 'reverse', 'initiate', 'cancel') NOT NULL,
			  `gateway_transaction_id` VARCHAR(100) NOT NULL,
			  `response_code` CHAR(50) NOT NULL,
			  `response_message` CHAR(50) NOT NULL,
			  `reference` CHAR(50) NOT NULL,
			  `amount` DECIMAL(10, 2) NOT NULL,
			  `currency` CHAR(3) NOT NULL,
			  `time_created` DATETIME NOT NULL,
			  PRIMARY KEY (`transaction_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_genius_card`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_genius_transaction`;");
	}

	public function getTransactions($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_genius_transaction WHERE order_id = '" . (int)$order_id . "'");
		return $query->rows;
	}

	public function addTransaction($order_id, $payment_action, $amount, $currency, $gatewayResponse) {
		$gatewayTransactionId = '';
		if (isset($gatewayResponse->transactionReference) && isset($gatewayResponse->transactionReference->transactionId)) {
			$gatewayTransactionId = (string)$gatewayResponse->transactionReference->transactionId;
		} elseif (isset($gatewayResponse->transactionId)) {
			$gatewayTransactionId = (string)$gatewayResponse->transactionId;
		}

		$reference = '';
		if (isset($gatewayResponse->transactionReference) && isset($gatewayResponse->transactionReference->clientTransactionId)) {
			$reference = (string)$gatewayResponse->transactionReference->clientTransactionId;
		} elseif (isset($gatewayResponse->referenceNumber)) {
			$reference = (string)$gatewayResponse->referenceNumber;
		}

		$responseCode = isset($gatewayResponse->responseCode) ? (string)$gatewayResponse->responseCode : '';
		$responseMessage = isset($gatewayResponse->responseMessage) ? (string)$gatewayResponse->responseMessage : '';
		$timeCreated = isset($gatewayResponse->timestamp) && !empty($gatewayResponse->timestamp)
			? (string)$gatewayResponse->timestamp
			: date('Y-m-d H:i:s');

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_genius_transaction` 
		SET `order_id` = '" . (int)$order_id . "', 
		    `payment_action` = '" . $this->db->escape($payment_action) . "', 
		    `gateway_transaction_id` = '" . $this->db->escape($gatewayTransactionId) . "',
		    `response_code` = '" . $this->db->escape($responseCode) . "',
		    `response_message` = '" . $this->db->escape($responseMessage) . "',
		    `reference` = '" . $this->db->escape($reference) . "',
		    `amount` = '" . (float)$amount . "',
		    `currency` = '" . $currency . "',
		    `time_created` = '" . $this->db->escape($timeCreated) . "'");
	}

	public function addCard($customer_id, $token, $card_type, $card_last4, $expiry_year, $expiry_month, $is_default = 0) {
		// Set other cards as non-default if this is the new default
		if ($is_default) {
			$this->db->query("UPDATE `" . DB_PREFIX . "globalpayments_genius_card` SET `is_default` = 0 WHERE `customer_id` = '" . (int)$customer_id . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_genius_card` 
		SET `customer_id` = '" . (int)$customer_id . "', 
		    `token` = '" . $this->db->escape($token) . "', 
		    `card_type` = '" . $this->db->escape($card_type) . "',
		    `card_last4` = '" . $this->db->escape($card_last4) . "',
		    `expiry_year` = '" . $this->db->escape($expiry_year) . "',
		    `expiry_month` = '" . $this->db->escape($expiry_month) . "',
		    `is_default` = '" . (int)$is_default . "',
		    `date_added` = NOW()");
	}

	public function getCustomerCards($customer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_genius_card WHERE customer_id = '" . (int)$customer_id . "' ORDER BY is_default DESC, token_id DESC");
		return $query->rows;
	}

	public function deleteCard($token_id, $customer_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "globalpayments_genius_card WHERE token_id = '" . (int)$token_id . "' AND customer_id = '" . (int)$customer_id . "'");
	}
}
