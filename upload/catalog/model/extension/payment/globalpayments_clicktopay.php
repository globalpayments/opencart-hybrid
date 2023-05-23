<?php
class ModelExtensionPaymentGlobalPaymentsClickToPay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/globalpayments_clicktopay');

		$status            = true;
		$method_data       = array();
		$method_data_title = $this->config->get('payment_globalpayments_clicktopay_title');
		if (empty($method_data_title)) {
			$method_data_title = $this->language->get('placeholder_title');
		}
		if ($status) {
			$method_data = array(
				'code'       => 'globalpayments_clicktopay',
				'title'      => $method_data_title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_globalpayments_clicktopay_sort_order')
			);
		}

		return $method_data;
	}

	public function addOrderMeta($order_id, $payment_address, $shipping_address, $email, $payer) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpayments_clicktopay_order_meta`
		SET `order_id` = '" . (int)$order_id . "',
		    `payment_address` = '" . $this->db->escape($payment_address) . "',
		    `shipping_address` = '" . $this->db->escape($shipping_address) . "',
		    `email` = '" . $this->db->escape($email) . "',
		    `payer` = '" . $this->db->escape($payer) . "',
		    `time_created` = now()");
	}
}
