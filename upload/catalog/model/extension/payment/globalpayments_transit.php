<?php

class ModelExtensionPaymentGlobalPaymentsTransit extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/globalpayments_transit');

		$status = true;
		$method_data = array();

		// Check if the payment method is enabled
		if (!$this->config->get('payment_globalpayments_transit_status')) {
			$status = false;
		}

		// Get title from configuration or use default
		$method_data_title = $this->config->get('payment_globalpayments_transit_title');
		if (empty($method_data_title)) {
			$method_data_title = $this->language->get('heading_title');
		}

		if ($status) {
			$method_data = array(
				'code'       => 'globalpayments_transit',
				'title'      => $method_data_title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_globalpayments_transit_sort_order') ?: 1
			);
		}

		return $method_data;
	}

	public function getCustomerCards($customer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_transit_card WHERE customer_id = '" . (int)$customer_id . "' ORDER BY is_default DESC, token_id DESC");
		return $query->rows;
	}

	public function getCards($customer_id, $gateway_id = null) {
		return $this->getCustomerCards($customer_id);
	}

	public function getCard($token_id) {
		$query = $this->db->query("SELECT token FROM " . DB_PREFIX . "globalpayments_transit_card WHERE token_id = '" . (int)$token_id . "'");

		return $query->row['token'] ?? null;
	}

	public function addCard() {
		$args = func_get_args();

		if (count($args) >= 8) {
			$customer_id = (int)$args[1];
			$token = $args[2];
			$card_type = $args[3];
			$last4 = $args[4];
			$exp_year = $args[5];
			$exp_month = $args[6];
			$is_default = (int)$args[7];
		} else {
			$customer_id = (int)$args[0];
			$token = $args[1];
			$card_type = $args[2];
			$last4 = $args[3];
			$exp_year = $args[4];
			$exp_month = $args[5];
			$is_default = 0;
		}

		if ($is_default) {
			$this->db->query("UPDATE " . DB_PREFIX . "globalpayments_transit_card SET is_default = '0' WHERE customer_id = '" . (int)$customer_id . "'");
		}

		$dateAddedColumn = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "globalpayments_transit_card` LIKE 'date_added'");

		if ($dateAddedColumn->num_rows) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transit_card`
			SET `customer_id` = '" . (int)$customer_id . "',
			    `token` = '" . $this->db->escape($token) . "',
			    `card_type` = '" . $this->db->escape($card_type) . "',
			    `card_last4` = '" . $this->db->escape($last4) . "',
			    `expiry_year` = '" . $this->db->escape($exp_year) . "',
			    `expiry_month` = '" . $this->db->escape($exp_month) . "',
			    `is_default` = '" . (int)$is_default . "',
			    `date_added` = NOW()");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transit_card`
			SET `customer_id` = '" . (int)$customer_id . "',
			    `token` = '" . $this->db->escape($token) . "',
			    `card_type` = '" . $this->db->escape($card_type) . "',
			    `card_last4` = '" . $this->db->escape($last4) . "',
			    `expiry_year` = '" . $this->db->escape($exp_year) . "',
			    `expiry_month` = '" . $this->db->escape($exp_month) . "',
			    `is_default` = '" . (int)$is_default . "'");
		}
	}

	public function deleteCard($customer_id, $token_id = null) {
		if ($token_id === null) {
			$token_id = $customer_id;
			$customer_id = (int)$this->customer->getId();
		}

		if (empty($this->getCard($token_id))) {
			return null;
		}

		return $this->db->query("DELETE FROM " . DB_PREFIX . "globalpayments_transit_card WHERE token_id = '" . (int)$token_id . "' AND customer_id = '" . (int)$customer_id . "'");
	}

	public function defaultCard($customer_id, $token_id) {
		if (empty($this->getCard($token_id))) {
			return null;
		}

		$this->db->query("UPDATE " . DB_PREFIX . "globalpayments_transit_card SET is_default = '0' WHERE customer_id = '" . (int)$customer_id . "'");

		return $this->db->query("UPDATE " . DB_PREFIX . "globalpayments_transit_card SET is_default = '1' WHERE customer_id = '" . (int)$customer_id . "' AND token_id = '" . (int)$token_id . "'");
	}

	public function addTransaction($order_id, $payment_action, $amount, $currency, $gatewayResponse) {
		$reference = '';
		if (isset($gatewayResponse->transactionReference->clientTransactionId)) {
			$reference = $gatewayResponse->transactionReference->clientTransactionId;
		} else if (isset($gatewayResponse->transactionReference->transactionId)) {
			$reference = $gatewayResponse->transactionReference->transactionId;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transit_transaction`
		SET `order_id` = '" . (int)$order_id . "',
		    `payment_action` = '" . $this->db->escape($payment_action) . "',
		    `gateway_transaction_id` = '" . $this->db->escape($gatewayResponse->transactionReference->transactionId) . "',
		    `response_code` = '" . $this->db->escape($gatewayResponse->responseCode) . "',
		    `response_message` = '" . $this->db->escape($gatewayResponse->responseMessage) . "',
		    `reference` = '" . $this->db->escape($reference) . "',
		    `amount` = '" . (float)$amount . "',
		    `currency` = '" . $this->db->escape((string)$currency) . "',
		    `time_created` = NOW()");
	}
}