<?php

class ModelExtensionPaymentGlobalPaymentsClickToPay extends Model {
	public function install() {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`code`, `key`, `value`, `serialized`) 
		                  VALUES ('payment_globalpayments_clicktopay', 'payment_globalpayments_clicktopay_accepted_cards', 'VISA,MASTERCARD,AMEX,DISCOVER', '0');");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "globalpayments_clicktopay_order_meta` (
			  `meta_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
			  `payment_address` longtext,
			  `shipping_address` longtext,
			  `email` VARCHAR(100),
			  `payer` VARCHAR(255),
			  `time_created` datetime NOT NULL,
			  PRIMARY KEY (`meta_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "globalpayments_clicktopay_order_meta`;");
	}

	public function getOrderMeta($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "globalpayments_clicktopay_order_meta WHERE order_id = '" . (int)$order_id . "'");

		return $query->row;
	}
}
