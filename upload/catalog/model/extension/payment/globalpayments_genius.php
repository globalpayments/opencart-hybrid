<?php

class ModelExtensionPaymentGlobalPaymentsGenius extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/globalpayments_genius');

		$status = true;
		$method_data = array();
		
		// Check if the payment method is enabled
		if (!$this->config->get('payment_globalpayments_genius_status')) {
			$status = false;
		}

		// Get title from configuration or use default
		$method_data_title = $this->config->get('payment_globalpayments_genius_title');
		if (empty($method_data_title)) {
			$method_data_title = $this->language->get('heading_title');
		}

		if ($status) {
			$method_data = array(
				'code'       => 'globalpayments_genius',
				'title'      => $method_data_title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_globalpayments_genius_sort_order') ?: 1
			);
		}

		return $method_data;
	}

	public function getCustomerCards($customer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_genius_card WHERE customer_id = '" . (int)$customer_id . "' ORDER BY is_default DESC, token_id DESC");
		return $query->rows;
	}

	public function getCards($customer_id, $gateway_id = null) {
		return $this->getCustomerCards($customer_id);
	}

	public function getCard($token_id) {
		$query = $this->db->query("SELECT token FROM " . DB_PREFIX . "globalpayments_genius_card WHERE token_id = '" . (int)$token_id . "'");

		return $query->row['token'] ?? null;
	}

	public function addCard($customer_id, $token, $card_type, $last4, $exp_year, $exp_month) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_genius_card` 
		SET `customer_id` = '" . (int)$customer_id . "', 
		    `token` = '" . $this->db->escape($token) . "', 
		    `card_type` = '" . $this->db->escape($card_type) . "',
		    `card_last4` = '" . $this->db->escape($last4) . "',
		    `expiry_year` = '" . $this->db->escape($exp_year) . "',
		    `expiry_month` = '" . $this->db->escape($exp_month) . "',
		    `date_added` = NOW()");
	}

	public function deleteCard($customer_id, $token_id = null) {
		if ($token_id === null) {
			$token_id = $customer_id;
			$customer_id = (int)$this->customer->getId();
		}

		if (empty($this->getCard($token_id))) {
			return null;
		}

		return $this->db->query("DELETE FROM " . DB_PREFIX . "globalpayments_genius_card WHERE token_id = '" . (int)$token_id . "' AND customer_id = '" . (int)$customer_id . "'");
	}

	public function defaultCard($customer_id, $token_id) {
		if (empty($this->getCard($token_id))) {
			return null;
		}

		$this->db->query("UPDATE " . DB_PREFIX . "globalpayments_genius_card SET is_default = '0' WHERE customer_id = '" . (int)$customer_id . "'");

		return $this->db->query("UPDATE " . DB_PREFIX . "globalpayments_genius_card SET is_default = '1' WHERE customer_id = '" . (int)$customer_id . "' AND token_id = '" . (int)$token_id . "'");
	}

	public function addTransaction($order_id, $payment_action, $amount, $currency, $gatewayResponse) {
		$reference = '';

		if (isset($gatewayResponse->transactionReference->clientTransactionId)) {
			$reference = $gatewayResponse->transactionReference->clientTransactionId;
		} elseif (isset($gatewayResponse->transactionReference->transactionId)) {
			$reference = $gatewayResponse->transactionReference->transactionId;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_genius_transaction` 
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

	public function getTransactions($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_genius_transaction WHERE order_id = '" . (int)$order_id . "' ORDER BY transaction_id DESC");

		return $query->rows;
	}
}
