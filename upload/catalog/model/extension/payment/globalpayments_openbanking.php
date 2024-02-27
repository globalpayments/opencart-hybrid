<?php
class ModelExtensionPaymentGlobalPaymentsOpenbanking extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/globalpayments_openbanking');

		$status            = true;
		$method_data       = array();
		$method_data_title = $this->config->get('payment_globalpayments_openbanking_title');
		if (empty($method_data_title)) {
			$method_data_title = $this->language->get('placeholder_title');
		}
		if ($status) {
			$method_data = array(
				'code'       => 'globalpayments_openbanking',
				'title'      => $method_data_title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_globalpayments_openbanking_sort_order')
			);
		}

		return $method_data;
	}
}
