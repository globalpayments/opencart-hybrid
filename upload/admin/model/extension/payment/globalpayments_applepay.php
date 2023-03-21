<?php

class ModelExtensionPaymentGlobalPaymentsApplePay extends Model {
	public function install() {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`code`, `key`, `value`, `serialized`) 
		                  VALUES ('payment_globalpayments_applepay', 'payment_globalpayments_applepay_accepted_cards', 'VISA,MASTERCARD,AMEX,DISCOVER', '0');");
	}
}
