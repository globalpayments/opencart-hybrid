<?php

class ModelExtensionPaymentGlobalPaymentsGpiTrans extends Model {
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
			  `payment_action` ENUM('authorize', 'charge', 'capture', 'refund', 'reverse', 'initiate', 'cancel') NOT NULL,
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
		$transactionId = $gatewayResponse->transactionReference?->transactionId
			?? $gatewayResponse->transactionId
			?? '';
		$responseCode = $gatewayResponse->responseCode ?? '';
		$responseMessage = $gatewayResponse->responseMessage ?? '';
		$reference = $gatewayResponse->transactionReference?->clientTransactionId
			?? $gatewayResponse->referenceNumber
			?? '';
		$timestamp = $gatewayResponse->timestamp ?? '';
		if (empty($timestamp) || strtotime($timestamp) === false) {
			$timestamp = date('Y-m-d H:i:s');
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transaction` 
		SET `order_id` = '" . (int)$order_id . "', 
		    `gateway_id` = '" . $this->db->escape( $gateway_id ) . "', 
		    `payment_action` = '" . $this->db->escape( $payment_action ) . "', 
		    `gateway_transaction_id` = '" . $this->db->escape( $transactionId ) . "',
		    `response_code` = '" . $this->db->escape( $responseCode ) . "',
		    `response_message` = '" . $this->db->escape( $responseMessage ) . "',
		    `reference` = '" . $this->db->escape( $reference ) . "',
		    `amount` = '" . (float)$amount . "',
		    `currency` = '" . $currency . "',
		    `time_created` = '" . $this->db->escape($timestamp) . "'");
	}

	/**
	 * Returns the total refunded amount recorded for an order.
	 *
	 * @param int $order_id
	 *
	 * @return float
	 */
	public function getRefundedAmount($order_id) {
		$query = $this->db->query(
			"SELECT SUM(amount) AS total_refunded FROM " . DB_PREFIX . "globalpayments_transaction"
			. " WHERE order_id = '" . (int)$order_id . "'"
			. " AND payment_action = 'refund'"
		);

		return (float)($query->row['total_refunded'] ?? 0);
	}

	public function fixColumns() {
		// SQL query to check if the value exists
		$insertEnumValues = "ENUM('authorize', 'charge', 'capture', 'refund', 'reverse', 'initiate', 'cancel')";
		$checkEnumValues = "enum('authorize','charge','capture','refund','reverse','initiate','cancel')";
		$latestEnumValues = $this->db->query('SHOW COLUMNS FROM ' . DB_PREFIX . 'globalpayments_transaction WHERE Field = "payment_action" AND Type =' . '"' . $checkEnumValues . '"')->num_rows > 0;
		if (empty($latestEnumValues)) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "globalpayments_transaction MODIFY COLUMN payment_action " . $insertEnumValues . " NOT NULL");
		}
	}
}
