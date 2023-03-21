<?php
class ModelExtensionPaymentGlobalPaymentsUcp extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/globalpayments_ucp');

		$status            = true;
		$method_data       = array();
		$method_data_title = $this->config->get('payment_globalpayments_ucp_title');
		if (empty($method_data_title)) {
			$method_data_title = $this->language->get('placeholder_title');
		}
		if ($status) {
			$method_data = array(
				'code'       => 'globalpayments_ucp',
				'title'      => $method_data_title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_globalpayments_ucp_sort_order')
			);
		}

		return $method_data;
	}

	public function getCards($customer_id, $gateway_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_card WHERE `customer_id` = '" . (int)$customer_id . "' AND `gateway_id` = '" . $this->db->escape($gateway_id) . "' ORDER BY `token_id` DESC");

		return $query->rows;
	}

	public function getCard($token_id) {
		$query = $this->db->query("SELECT token FROM " . DB_PREFIX . "globalpayments_card WHERE `token_id` = '" . (int)$token_id . "'");

		return $query->row['token'] ?? null;
	}

	public function addCard($gateway_id, $customer_id, $token, $card_type, $card_last4, $expiry_year, $expiry_month, $is_default = 0) {
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

	public function deleteCard($customer_id, $token_id) {
		if (empty($this->getCard($token_id))) {
			return null;
		}

		return $this->db->query("DELETE FROM " . DB_PREFIX . "globalpayments_card WHERE `customer_id` = '" . (int)$customer_id . "' AND `token_id` = '" . (int)$token_id . "'");
	}

	public function defaultCard($customer_id, $token_id) {
		if (empty($this->getCard($token_id))) {
			return null;
		}
		$this->db->query("UPDATE " . DB_PREFIX . "globalpayments_card SET `is_default` = '0' WHERE `customer_id` = '" . (int)$customer_id . "'");

		return $this->db->query("UPDATE " . DB_PREFIX . "globalpayments_card SET `is_default` = '1' WHERE `customer_id` = '" . (int)$customer_id . "' AND `token_id` = '" . (int)$token_id . "'");
	}

	public function addTransaction($order_id, $gateway_id, $payment_action, $amount, $currency, $gatewayResponse) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_transaction` 
		SET `order_id` = '" . (int)$order_id . "', 
		    `gateway_id` = '" . $this->db->escape($gateway_id) . "', 
		    `payment_action` = '" . $this->db->escape($payment_action) . "', 
		    `gateway_transaction_id` = '" . $this->db->escape($gatewayResponse->transactionReference->transactionId) . "',
		    `response_code` = '" . $this->db->escape($gatewayResponse->responseCode) . "',
		    `response_message` = '" . $this->db->escape($gatewayResponse->responseMessage) . "',
		    `reference` = '" . $this->db->escape($gatewayResponse->transactionReference->clientTransactionId) . "',
		    `amount` = '" . (float)$amount . "',
		    `currency` = '" . $currency . "',
		    `time_created` = '" . $gatewayResponse->timestamp . "'");
	}
}
