<?php

class ModelExtensionPaymentGlobalPaymentsTransit extends Model {
	public function install() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_transit_card` (
			  `token_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `customer_id` INT(11) NOT NULL,
			  `token` VARCHAR(255) NOT NULL,
			  `card_type` VARCHAR(50) NOT NULL,
			  `card_last4` CHAR(4) NOT NULL,
			  `expiry_year` CHAR(4) NOT NULL,
			  `expiry_month` CHAR(2) NOT NULL,
			  `date_added` DATETIME NOT NULL,
			  `is_default` INT(1) DEFAULT 0,
			  PRIMARY KEY (`token_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_transit_transaction` (
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

		// Backfill schema changes for existing installations.
		$dateAddedColumn = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "globalpayments_transit_card` LIKE 'date_added'");
		if (!$dateAddedColumn->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "globalpayments_transit_card` ADD `date_added` DATETIME NOT NULL AFTER `expiry_month`");
		}

		$referenceColumn = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "globalpayments_transit_transaction` LIKE 'reference'");
		if (!$referenceColumn->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "globalpayments_transit_transaction` ADD `reference` CHAR(50) NOT NULL AFTER `response_message`");
		}
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_transit_card`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_transit_transaction`;");
	}

	/**
	 * Get AVS rejection conditions options
	 *
	 * @return array
	 */
	public function getAvsRejectionConditions() {
		return array(
			'A' => $this->language->get('avs_code_a'),
			'B' => $this->language->get('avs_code_b'),
			'C' => $this->language->get('avs_code_c'),
			'D' => $this->language->get('avs_code_d'),
			'G' => $this->language->get('avs_code_g'),
			'I' => $this->language->get('avs_code_i'),
			'M' => $this->language->get('avs_code_m'),
			'N' => $this->language->get('avs_code_n'),
			'P' => $this->language->get('avs_code_p'),
			'R' => $this->language->get('avs_code_r'),
			'S' => $this->language->get('avs_code_s'),
			'U' => $this->language->get('avs_code_u'),
			'W' => $this->language->get('avs_code_w'),
			'X' => $this->language->get('avs_code_x'),
			'Y' => $this->language->get('avs_code_y'),
			'Z' => $this->language->get('avs_code_z'),
		);
	}

	/**
	 * Get CVN rejection conditions options
	 *
	 * @return array
	 */
	public function getCvnRejectionConditions() {
		return array(
			'N' => $this->language->get('cvn_code_n'),
			'P' => $this->language->get('cvn_code_p'),
			'S' => $this->language->get('cvn_code_s'),
			'U' => $this->language->get('cvn_code_u'),
			'?' => $this->language->get('cvn_code_question'),
		);
	}

	public function getTransactions($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_transit_transaction WHERE order_id = '" . (int)$order_id . "'");
		return $query->rows;
	}

	public function addTransaction($order_id, $payment_action, $amount, $currency, $gatewayResponse) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transit_transaction` 
		SET `order_id` = '" . (int)$order_id . "', 
		    `payment_action` = '" . $this->db->escape($payment_action) . "', 
		    `gateway_transaction_id` = '" . $this->db->escape($gatewayResponse->transactionReference->transactionId) . "',
		    `response_code` = '" . $this->db->escape($gatewayResponse->responseCode) . "',
		    `response_message` = '" . $this->db->escape($gatewayResponse->responseMessage) . "',
		    `reference` = '" . $this->db->escape($gatewayResponse->transactionReference->clientTransactionId ?? '') . "',
		    `amount` = '" . (float)$amount . "',
		    `currency` = '" . $currency . "',
		    `time_created` = NOW()");;
	}

	public function addCard($customer_id, $token, $card_type, $card_last4, $expiry_year, $expiry_month, $is_default = 0) {
		// Set other cards as non-default if this is the new default
		if ($is_default) {
			$this->db->query("UPDATE `" . DB_PREFIX . "globalpayments_transit_card` SET `is_default` = 0 WHERE `customer_id` = '" . (int)$customer_id . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transit_card` 
		SET `customer_id` = '" . (int)$customer_id . "', 
		    `token` = '" . $this->db->escape($token) . "', 
		    `card_type` = '" . $this->db->escape($card_type) . "',
		    `card_last4` = '" . $this->db->escape($card_last4) . "',
		    `expiry_year` = '" . $this->db->escape($expiry_year) . "',
		    `expiry_month` = '" . $this->db->escape($expiry_month) . "',
		    `is_default` = '" . (int)$is_default . "'");
	}

	public function getCustomerCards($customer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_transit_card WHERE customer_id = '" . (int)$customer_id . "' ORDER BY is_default DESC, token_id DESC");
		return $query->rows;
	}

	public function deleteCard($token_id, $customer_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "globalpayments_transit_card WHERE token_id = '" . (int)$token_id . "' AND customer_id = '" . (int)$customer_id . "'");
	}
}
