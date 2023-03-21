<?php

class ModelExtensionPaymentGlobalPaymentsUcp extends Model {
	public function install() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_card` (
			  `token_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `gateway_id` VARCHAR(50) NOT NULL,
			  `customer_id` INT(11) NOT NULL,
			  `token` VARCHAR(255) NOT NULL,
			  `card_type` VARCHAR(50) NOT NULL,
			  `card_last4` CHAR(4) NOT NULL,
			  `expiry_year` CHAR(4) NOT NULL,
			  `expiry_month` CHAR(2) NOT NULL,
			  `is_default` INT(1) DEFAULT 0,
			  PRIMARY KEY (`token_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_transaction` (
			  `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
			  `gateway_id` VARCHAR(50) NOT NULL,
			  `payment_action` ENUM('authorize', 'charge', 'capture', 'refund', 'reverse') NOT NULL,
			  `gateway_transaction_id` VARCHAR(100) NOT NULL,
			  `response_code` CHAR(50) NOT NULL,
			  `response_message` CHAR(50) NOT NULL,
			  `reference` CHAR(50) NOT NULL,
			  `amount` DECIMAL(10, 2) NOT NULL,
			  `currency` CHAR(3) NOT NULL,
			  `time_created` datetime NOT NULL,
			  PRIMARY KEY (`transaction_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_card`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_transaction`;");
	}

	public function getTransactions($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_transaction WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function addTransaction($order_id, $gateway_id, $payment_action, $amount, $currency, $gatewayResponse) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transaction` 
		SET `order_id` = '" . (int)$order_id . "', 
		    `gateway_id` = '" . $this->db->escape( $gateway_id ) . "', 
		    `payment_action` = '" . $this->db->escape( $payment_action ) . "', 
		    `gateway_transaction_id` = '" . $this->db->escape( $gatewayResponse->transactionReference->transactionId ) . "',
		    `response_code` = '" . $this->db->escape( $gatewayResponse->responseCode ) . "',
		    `response_message` = '" . $this->db->escape( $gatewayResponse->responseMessage ) . "',
		    `reference` = '" . $this->db->escape( $gatewayResponse->transactionReference->clientTransactionId ) . "',
		    `amount` = '" . (float)$amount . "',
		    `currency` = '" . $currency . "',
		    `time_created` = '" . $gatewayResponse->timestamp . "'");
	}
}
