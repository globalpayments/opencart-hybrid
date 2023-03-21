<?php

class ModelExtensionPaymentGlobalPaymentsGooglePay extends Model {
	public function install() {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`code`, `key`, `value`, `serialized`) 
		                  VALUES ('payment_globalpayments_googlepay', 'payment_globalpayments_googlepay_accepted_cards', 'VISA,MASTERCARD,AMEX,DISCOVER,JCB', '0');");
	}
}
