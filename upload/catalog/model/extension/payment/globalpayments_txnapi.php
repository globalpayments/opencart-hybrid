<?php
include_once 'catalog/model/extension/payment/globalpayments_gateway.php';

class ModelExtensionPaymentGlobalPaymentsTxnApi extends ModelExtensionPaymentGlobalPaymentsGatewayBase {
	/**
	 * @param Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry,'txnapi');
	}

	/**
	 * Adds a card only when the same token/card metadata is not already stored.
	 *
	 * @param string $gateway_id
	 * @param int $customer_id
	 * @param string $token
	 * @param string $card_type
	 * @param string $card_last4
	 * @param string $expiry_year
	 * @param string $expiry_month
	 * @param int $is_default
	 *
	 * @return void
	 */
	public function addCard(
		$gateway_id,
		$customer_id,
		$token,
		$card_type,
		$card_last4,
		$expiry_year,
		$expiry_month,
		$is_default = 0
	) {
		$sql = "SELECT token_id FROM " . DB_PREFIX . "globalpayments_card"
			. " WHERE `customer_id` = '" . (int)$customer_id . "'"
			. " AND `gateway_id` = '" . $this->db->escape($gateway_id) . "'"
			. " AND (`token` = '" . $this->db->escape($token) . "'"
			. " OR (`card_type` = '" . $this->db->escape($card_type) . "'"
			. " AND `card_last4` = '" . $this->db->escape($card_last4) . "'"
			. " AND `expiry_year` = '" . $this->db->escape($expiry_year) . "'"
			. " AND `expiry_month` = '" . $this->db->escape($expiry_month) . "'))"
			. " LIMIT 1";

		$existing = $this->db->query($sql);

		if (!empty($existing->row['token_id'])) {
			return;
		}

		parent::addCard($gateway_id, $customer_id, $token, $card_type, $card_last4, $expiry_year, $expiry_month, $is_default);
	}
}
