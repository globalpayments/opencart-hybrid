<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms;

use GlobalPayments\Api\Entities\Enums\AlternativePaymentType;
use GlobalPayments\Api\Entities\Enums\Channel;
use GlobalPayments\Api\Entities\Enums\ServiceEndpoints;
use GlobalPayments\Api\Entities\GpApi\AccessTokenInfo;
use GlobalPayments\Api\PaymentMethods\AlternativePaymentMethod;
use GlobalPayments\Api\ServiceConfigs\Gateways\GpApiConfig;
use GlobalPayments\Api\ServicesContainer;

/**
 * Custom BLIK Payment Helper for OpenCart (SDK-Free Version)
 *
 * @package GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms
 */
class BlikPayment
{
    /**
     * Process BLIK payment for OpenCart
     *
     * @param int $order_id OpenCart order ID
     * @param object|null $registry OpenCart registry object
     * @return array Payment result
     */
    public static function processBlikTransaction(int $order_id, ?object $registry = null): array
    {
        try {
            // Get registry from global if not passed
            if (!$registry) {
                global $registry;
            }
            
            if (!$registry) {
                throw new \Exception('Registry not available');
            }
            
            $load = $registry->get('load');
            $opencart_config = $registry->get('config');
            
            // Load order model and get order data
            $load->model('checkout/order');
            $model_checkout_order = $registry->get('model_checkout_order');
            $order = $model_checkout_order->getOrder($order_id);
            if (!$order) {
                return [
                    'result' => 'error',
                    'message' => 'Order not found'
                ];
            }

            // Get BLIK configuration from OpenCart settings
            $settings = self::getBlikSettings($opencart_config);
            
            // Validate configuration
            if (!self::validateConfiguration($settings)) {
                return [
                    'result' => 'error',
                    'message' => 'BLIK configuration is incomplete'
                ];
            }

            // Configure GlobalPayments API
            $config = new GpApiConfig();
            $config->country = 'PL';
            $config->appId = $settings["appId"];
            $config->appKey = $settings["appKey"];
            $config->serviceUrl = $settings["environment"] === 'PRODUCTION' ?
                ServiceEndpoints::GP_API_PRODUCTION : ServiceEndpoints::GP_API_TEST;
            $config->channel = $settings["channel"];

            $accessTokenInfo = new AccessTokenInfo();
            if (isset($settings["accountName"])) {
                $accessTokenInfo->transactionProcessingAccountName = $settings["accountName"];
            }
            $config->accessTokenInfo = $accessTokenInfo;

            ServicesContainer::configureService($config);

            // Create BLIK payment method
            $paymentMethod = new AlternativePaymentMethod(AlternativePaymentType::BLIK);
            
            // Set payment method properties using OpenCart order data
            $paymentMethod->descriptor = 'ORD_' . $order['order_id'];
            $paymentMethod->country = 'PL'; // BLIK is Poland-specific
            $paymentMethod->accountHolderName = $order['firstname'] . ' ' . $order['lastname'];

            $store_url = $opencart_config->get('config_url');

            $paymentMethod->returnUrl = $store_url . 'index.php?route=extension/payment/blik/redirect/index&order_id=' . $order['order_id'].'&&action=blik_redirect_handler&&';
            $paymentMethod->statusUpdateUrl = $store_url . 'index.php?route=extension/payment/blik/webhook/index&&action=blik_status_handler';
            $paymentMethod->cancelUrl = $store_url . 'index.php?route=checkout/cart';

            // Execute the BLIK payment charge
            $orderTotal = (float)$order['total'];

            // Get currency object from registry for formatting
            $currency = $registry->get('currency');
            $orderTotal = $currency->format($orderTotal, $order['currency_code'], $order['currency_value'], false);
            $currencyCode = $order['currency_code'];

            $blikGpResponse = $paymentMethod->charge($orderTotal)
                ->withClientTransactionId('opencart_Order_' . $order['order_id'])
                ->withCurrency($currencyCode)
                ->execute();

            // Process the GlobalPayments response
            if ($blikGpResponse->responseCode === '00' || $blikGpResponse->responseCode === 'SUCCESS') {

                // Store transaction data in GlobalPayments transaction table for refund functionality
                self::storeBlikTransaction($order_id, $blikGpResponse, $orderTotal, $currencyCode, $registry);

                $result = [
                    'success' => true,
                    'transaction_id' => $blikGpResponse->transactionId,
                    'order_id' => $order['order_id'],
                    'message' => 'BLIK payment initiated successfully',
                    'status' => $blikGpResponse->responseMessage
                ];

                // If there's a redirect URL for BLIK authentication
                if (isset($blikGpResponse->alternativePaymentResponse) && 
                    isset($blikGpResponse->alternativePaymentResponse->redirectUrl)) {
                    $result['redirect_url'] = $blikGpResponse->alternativePaymentResponse->redirectUrl;
                }

                return $result;
            } else {
                // Payment failed
                $error_message = $blikGpResponse->responseMessage ?: 'BLIK payment failed';
                
                return array(
                    'result' => 'error',
                    'message' => $error_message,
                    'transaction_id' => $blikGpResponse->transactionId ?? null
                );
            }

        } catch (\Exception $e) {
            // Log error
            
            return [
                'result' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get BLIK configuration settings
     *
     * @param object $config OpenCart config object
     * @return array Settings array
     */
    private static function getBlikSettings(object $config): array
    {
        // Default settings - you can modify these or get from OpenCart configuration
        return [
            'appId' => $config->get('payment_globalpayments_ucp_sandbox_app_id'),
            'appKey' => $config->get('payment_globalpayments_ucp_sandbox_app_key'),
            'environment' => $config->get('payment_globalpayments_ucp_is_production')
                ?: 'sandbox',
            'channel' => Channel::CardNotPresent,
            'accountName' => $config->get('payment_globalpayments_ucp_sandbox_account_name')
        ];
    }

    /**
     * Validate BLIK configuration
     *
     * @param array $settings
     * @return bool
     */
    public static function validateConfiguration(array $settings): bool
    {
        $required_fields = ['appId', 'appKey', 'environment', 'channel'];

        foreach ($required_fields as $field) {
            if (empty($settings[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Store BLIK transaction data in GlobalPayments transaction table
     *
     * @param int $order_id OpenCart order ID
     * @param object $blikGpResponse GlobalPayments response object
     * @param float $amount Transaction amount
     * @param string $currency Currency code
     * @param object $registry OpenCart registry
     */
    private static function storeBlikTransaction(int $order_id, object $blikGpResponse, float $amount, string $currency, object $registry): void
    {
        try {
            $load = $registry->get('load');

            // Load the GlobalPayments UCP model to use its addTransaction method
            $load->model('extension/payment/globalpayments_ucp');
            $model = $registry->get('model_extension_payment_globalpayments_ucp');

            // Use the existing addTransaction method with BLIK-specific data
            $model->addTransaction(
                $order_id,
                'globalpayments_ucp', // Gateway ID for BLIK payments
                'charge', // Payment action - BLIK payments are captured immediately
                $amount,
                $currency,
                $blikGpResponse // Pass the response object directly
            );
        } catch (\Exception $e) {
            // Log error but don't fail the payment process
            error_log("BLIK transaction storage failed: " . $e->getMessage());
        }
    }

    /**
     * Handle BLIK payment status notification callback from payment gateway
     *
     * @param object|null $registry OpenCart registry object
     * @return array Response array
     */
    public static function handle_blik_status_notification(?object $registry = null): array
    {
        try {
            // Get registry from global if not passed
            if (!$registry) {
                global $registry;
            }
            
            if (!$registry) {
                throw new \Exception('Registry not available');
            }

            $load = $registry->get('load');

            // Get request data (could be GET or POST)
            $request_data = array_merge($_GET, $_POST);

            // Parse JSON body if present
            $raw_input = file_get_contents('php://input');
            if (!empty($raw_input)) {
                $json_data = json_decode($raw_input, true);
                if ($json_data) {
                    $request_data = array_merge($request_data, $json_data);
                }
            }

            // Extract transaction details using the real callback structure
            $transaction_id = self::extract_transaction_id($request_data);
            $payment_status = self::extract_payment_status($request_data);
            $reference = self::extract_reference($request_data);

            $processed = false;

            // Try to find order by reference field
            if (!empty($reference)) {
                $order_id = self::extract_order_id_from_reference($reference);

                if ($order_id) {

                    $load->model('checkout/order');
                    $model_checkout_order = $registry->get('model_checkout_order');
                    $order = $model_checkout_order->getOrder($order_id);

                    if ($order) {
                        self::update_order_status_from_notification($order, $payment_status, $transaction_id, $request_data, $registry);
                        $processed = true;
                    }
                }
            }

            // Fallback: try to find by platforms array
            if (!$processed && !empty($request_data['platforms'])) {
                foreach ($request_data['platforms'] as $platform) {
                    if (isset($platform['order_id'])) {
                        $order_id = self::extract_order_id_from_platform($platform['order_id']);

                        if ($order_id) {

                            $load->model('checkout/order');
                            $model_checkout_order = $registry->get('model_checkout_order');
                            $order = $model_checkout_order->getOrder($order_id);

                            if ($order) {
                                self::update_order_status_from_notification($order, $payment_status, $transaction_id, $request_data, $registry);
                                $processed = true;
                                break;
                            }
                        }
                    }
                }
            }

            // Legacy fallback: check for old format "opencart_Order_X"
            if (!$processed && !empty($reference) && strpos($reference, 'opencart_Order_') !== false) {
                $order_id = str_replace("opencart_Order_", "", $reference);

                $load->model('checkout/order');
                $model_checkout_order = $registry->get('model_checkout_order');
                $order = $model_checkout_order->getOrder($order_id);
                
                if ($order) {
                    self::update_order_status_from_notification($order, $payment_status, $transaction_id, $request_data, $registry);
                    $processed = true;
                }
            }

            // Return appropriate response
            if ($processed) {
                return [
                    'status' => 'success',
                    'message' => 'Notification processed successfully',
                    'processed' => true
                ];
            } else {
                
                return [
                    'status' => 'error',
                    'message' => 'Could not process notification',
                    'processed' => false
                ];
            }

        } catch (\Exception $e) {

            return [
                'status' => 'error',
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
                'processed' => false
            ];
        }
    }

    /**
     * Extract transaction ID from callback data
     *
     * @param array $data
     * @return string|null
     */
    private static function extract_transaction_id($data) 
    {
        $possible_fields = ['id', 'transaction_id', 'reference'];

        foreach ($possible_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }

        return null;
    }

    /**
     * Extract payment status from callback data
     *
     * @param array $data
     * @return string
     */
    private static function extract_payment_status($data) 
    {
        $possible_fields = ['status', 'payment_status', 'transaction_status'];

        foreach ($possible_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Extract reference from callback data
     *
     * @param array $data
     * @return string|null
     */
    private static function extract_reference($data)
    {
        if (isset($data['reference']) && !empty($data['reference'])) {
            return $data['reference'];
        }
        return null;
    }

    /**
     * Extract order ID from reference field
     *
     * @param string $reference
     * @return int|null
     */
    private static function extract_order_id_from_reference($reference)
    {
        // Reference might be the order ID directly, or contain it
        if (is_numeric($reference)) {
            return (int)$reference;
        }

        // Try to extract numeric part if it contains other text
        if (preg_match('/(\d+)/', $reference, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * Extract order ID from platform order_id field
     *
     * @param string $platform_order_id
     * @return int|null
     */
    private static function extract_order_id_from_platform($platform_order_id)
    {
        // Look for pattern like "_Order_28" or "Order_28"
        if (preg_match('/_Order_(\d+)$/', $platform_order_id, $matches)) {
            return (int)$matches[1];
        }

        // Fallback: look for any number at the end
        if (preg_match('/(\d+)$/', $platform_order_id, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * Update order status based on payment notification
     *
     * @param array $order OpenCart order array
     * @param string $payment_status Payment status from notification
     * @param string $transaction_id Transaction ID
     * @param array $callback_data Full callback data
     * @param object $registry OpenCart registry
     */
    private static function update_order_status_from_notification(
        array $order,
        string $payment_status,
        string $transaction_id,
        array $callback_data,
        object $registry
    ): void {
        $status_upper = strtoupper($payment_status);
        $order_id = $order['order_id'];

        // Load order model
        $load = $registry->get('load');
        $load->model('checkout/order');
        $model_checkout_order = $registry->get('model_checkout_order');

        // Create order note with callback details
        $callback_summary = "Status: $payment_status, Transaction ID: $transaction_id";
        if (isset($callback_data['amount'])) {
            $callback_summary .= ", Amount: " . $callback_data['amount'];
        }

        switch ($status_upper) {
            case 'CAPTURED':
            case 'COMPLETED':
            case 'SUCCESS':
                // Get success order status from configuration
                $order_status_id = 2; // Processing
                $comment = sprintf('BLIK payment completed via status notification. %s', $callback_summary);
                $model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);

                // Update transaction status in database if it exists
                self::updateBlikTransactionStatus($order_id, $transaction_id, 'SUCCESS', $registry);
                break;
            case 'DECLINED':
            case 'FAILED':
            case 'CANCELLED':
                // Get failed order status from configuration
                $order_status_id =  7; // Failed
                $comment = sprintf('BLIK payment failed/declined via status notification. %s', $callback_summary);
                $model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);

                // Update transaction status in database if it exists
                self::updateBlikTransactionStatus($order_id, $transaction_id, 'DECLINED', $registry);
                break;
            case 'PENDING':
                // Get pending order status from configuration
                $order_status_id =  1; // Pending
                $comment = sprintf('BLIK payment is pending. %s', $callback_summary);
                $model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, false);

                // Update transaction status in database if it exists
                self::updateBlikTransactionStatus($order_id, $transaction_id, 'PENDING', $registry);
                break;
            default:
                break;
        }
    }

    /**
     * Update BLIK transaction status in database
     *
     * @param int $order_id OpenCart order ID
     * @param string $transaction_id Transaction ID
     * @param string $status New status
     * @param object $registry OpenCart registry
     */
    private static function updateBlikTransactionStatus(int $order_id, string $transaction_id, string $status, object $registry): void
    {
        try {
            $db = $registry->get('db');

            // Update transaction status in the globalpayments_transaction table
            $db->query("UPDATE " . DB_PREFIX . "globalpayments_transaction
                       SET response_code = '" . $db->escape($status) . "'
                       WHERE order_id = '" . (int)$order_id . "'
                       AND gateway_transaction_id = '" . $db->escape($transaction_id) . "'");
        } catch (\Exception $e) {
            // Log error but don't fail the notification process
            error_log("BLIK transaction status update failed: " . $e->getMessage());
        }
    }
}
