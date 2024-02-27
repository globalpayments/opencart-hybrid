<?php

class ModelExtensionPaymentGlobalPaymentsOpenBanking extends Model {
	public function install() {

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`code`, `key`, `value`, `serialized`) 
		                  VALUES ('payment_globalpayments_openbanking', 'payment_globalpayments_openbanking_currencies', 'GBP,EUR', '0');");

	}
}
