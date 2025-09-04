<?php

// Load GlobalPayments API autoloader first
if (file_exists(DIR_SYSTEM . 'library/globalpayments/vendor/autoload.php')) {   
    require_once(DIR_SYSTEM . 'library/globalpayments/vendor/autoload.php');
} elseif (file_exists(DIR_SYSTEM . 'library/globalpayments/globalpayments/php-sdk/autoload.php')) {
    require_once(DIR_SYSTEM . 'library/globalpayments/globalpayments/php-sdk/autoload.php');
} elseif (file_exists(DIR_SYSTEM . 'library/globalpayments/autoload.php')) {
    require_once(DIR_SYSTEM . 'library/globalpayments/autoload.php');
}

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;


// Include our custom BLIK payment class
require_once(DIR_SYSTEM . 'library/globalpayments/globalpayments/php-integrations/src/GlobalPayments/Gateways/DiUiApms/BlikPayment.php');
require_once(DIR_SYSTEM . 'library/globalpayments/globalpayments/php-integrations/src/GlobalPayments/Gateways/DiUiApms/OpenBankingPayment.php');
use GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\BlikPayment;
use GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\OpenBankingPayment;

class ControllerExtensionPaymentApmMiddleware extends Controller
{
    public function confirm(): void
    {
        $payment_method = $this->request->post['payment_method'] ?? '';
        $bankName = $this->request->post['bank'] ?? '';
       
        $data = $this->setOrder($payment_method, $bankName);
        $this->response->setOutput(json_encode($data));
    }

    private function setOrder(string $payment_method, string $bankName): array
    {

            if (empty($this->session->data['order_id'])) {
				throw new \Exception($this->language->get('error_order_processing'));
			}
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$order                 = new OrderData();
			$order->amount         = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
			$order->currency       = $order_info['currency_code'];
			$order->orderReference = $this->session->data['order_id'];

            $order->customerEmail = $order_info['email'];
			$order->billingAddress = array(
				'streetAddress1' => $order_info['payment_address_1'],
				'streetAddress2' => $order_info['payment_address_2'],
				'city'           => $order_info['payment_city'],
				'state'          => $order_info['payment_zone_code'],
				'postalCode'     => $order_info['payment_postcode'],
				'country'        => $order_info['payment_iso_code_2'],
			);
			$order->shippingAddress = array(
				'streetAddress1' => $order_info['shipping_address_1'],
				'streetAddress2' => $order_info['shipping_address_2'],
				'city'           => $order_info['shipping_city'],
				'state'          => $order_info['shipping_zone_code'],
				'postalCode'     => $order_info['shipping_postcode'],
				'country'        => $order_info['shipping_iso_code_2'],
			);

            $order->addressMatchIndicator = $order->billingAddress == $order->shippingAddress;

            // Add payment information to the description field and create separate payment data
			if ($payment_method == 'blik') {
				$order->description = 'BLIK Payment - Order #' . $order->orderReference;
				$paymentInfo = [
					'method' => 'GlobalPayments - BLIK',
					'code' => 'globalpayments_ucp',
					'type' => 'alternative_payment'
				];
			} else {
				$order->description = 'Open Banking Payment - Order #' . $order->orderReference;
				$paymentInfo = [
					'method' => 'GlobalPayments - Open Banking',
					'code' => 'globalpayments_ucp',
					'type' => 'alternative_payment'
				];
			}

            $this->order = $order;
			$this->paymentInfo = $paymentInfo;
			// Update the existing order with payment method information
			$this->load->model('checkout/order');
			// Update order with payment information
			$update_data = [
				'payment_method' => $paymentInfo['method'],
			];

            // Use direct SQL to update payment information
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET
					payment_method = '" . $this->db->escape($paymentInfo['method']) . "',
					payment_code = '" . $this->db->escape($paymentInfo['code']) . "'
					WHERE order_id = '" . (int)$order->orderReference . "'");
			if ($payment_method == 'blik') {
				$response = BlikPayment::processBlikTransaction($this->order->orderReference, $this->registry);
			} else {
				$response = OpenBankingPayment::processOpenBankingTransaction(
					$this->order->orderReference,
					$bankName,
					$this->registry
				);
			}

			 $order_id = $this->session->data['order_id'];
			 $order_status_id = 1; // Set the order status ID as needed

            if (isset($response['success'])) {
				$this->model_checkout_order->addOrderHistory(
					$order_id,
					$order_status_id,
					'Payment initiated with transaction ID: ' . $response['transaction_id'],
					false
				);
			}

            return $response;
    }
}