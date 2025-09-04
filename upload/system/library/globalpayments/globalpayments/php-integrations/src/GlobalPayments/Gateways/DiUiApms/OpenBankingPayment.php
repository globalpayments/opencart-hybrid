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
 * Custom OpenBanking Payment Helper for OpenCart (SDK-Free Version)
 *
 * @package GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms
 */
class OpenBankingPayment
{
    /**
     * Process OpenBanking payment for OpenCart
     *
     * @param int $order_id OpenCart order ID
     * @param string $bankName Bank name for Open Banking
     * @param object|null $registry OpenCart registry object
     * @return array Payment result
     */
    public static function processOpenBankingTransaction(
        int $order_id,
        string $bankName,
        ?object $registry = null
    ): array {
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

            // Get OpenBanking configuration from OpenCart settings
            $settings = self::getOpenBankingSettings($opencart_config);
            
            // Validate configuration
            if (!self::validateConfiguration($settings)) {
                return [
                    'result' => 'error',
                    'message' => 'OpenBanking configuration is incomplete'
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

            // Create OpenBanking payment method
            $paymentMethod = new AlternativePaymentMethod(AlternativePaymentType::OB);
            
            // Set payment method properties using OpenCart order data
            $paymentMethod->descriptor = 'ORD_' . $order['order_id'];
            $paymentMethod->country = 'PL'; // OpenBanking is Poland-specific
            $paymentMethod->accountHolderName = $order['firstname'] . ' ' . $order['lastname'];
            $paymentMethod->bank = $bankName;
            // Build URLs for OpenCart
            $store_url = $opencart_config->get('config_url');

            $paymentMethod->returnUrl = $store_url . 'index.php?route=extension/payment/blik/redirect/index&order_id=' . $order['order_id'].'&&action=ob_redirect_handler&&';
            $paymentMethod->statusUpdateUrl = $store_url . 'index.php?route=extension/payment/blik/webhook/index&&action=ob_status_handler';
            $paymentMethod->cancelUrl = $store_url . 'index.php?route=checkout/cart';

            // Execute the OpenBanking payment charge
            $orderTotal = (float)$order['total'];
            // Get currency object from registry for formatting
            $currency = $registry->get('currency');
            $orderTotal = $currency->format($orderTotal, $order['currency_code'], $order['currency_value'], false);
            $currencyCode = $order['currency_code'];

            $openBankingGpResponse = $paymentMethod->charge($orderTotal)
                ->withClientTransactionId('opencart_Order_' . $order['order_id'])
                ->withCurrency($currencyCode)
                ->execute();

            // Process the GlobalPayments response
            if ($openBankingGpResponse->responseCode === '00' || 
                $openBankingGpResponse->responseCode === 'SUCCESS') {

                // Store transaction data in GlobalPayments transaction table for refund functionality
                self::storeOpenBankingTransaction($order_id, $openBankingGpResponse, $orderTotal, $currencyCode, $registry);

                $result = [
                    'success' => true,
                    'transaction_id' => $openBankingGpResponse->transactionId,
                    'order_id' => $order['order_id'],
                    'message' => 'OpenBanking payment initiated successfully',
                    'status' => $openBankingGpResponse->responseMessage
                ];

                // If there's a redirect URL for OpenBanking authentication
                if (isset($openBankingGpResponse->alternativePaymentResponse) && 
                    isset($openBankingGpResponse->alternativePaymentResponse->redirectUrl)) {
                    $result['redirect_url'] = $openBankingGpResponse->alternativePaymentResponse->redirectUrl;
                }

                // Log the successful transaction
                self::logPayment(
                    $order['order_id'],
                    $openBankingGpResponse->transactionId,
                    'success',
                    $openBankingGpResponse->responseMessage,
                    $registry
                );
                
                return $result;
            } else {
                // Payment failed
                $error_message = $openBankingGpResponse->responseMessage ?: 'OpenBanking payment failed';

                self::logPayment(
                    $order['order_id'],
                    $openBankingGpResponse->transactionId ?? null,
                    'failed',
                    $error_message,
                    $registry
                );
                
                return [
                    'result' => 'error',
                    'message' => $error_message,
                    'transaction_id' => $openBankingGpResponse->transactionId ?? null
                ];
            }

        } catch (\Exception $e) {
            // Log error
            self::logPayment($order_id, null, 'error', $e->getMessage(), $registry);
            
            return [
                'result' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get OpenBanking configuration settings
     *
     * @param object $config OpenCart config object
     * @return array Settings array
     */
    private static function getOpenBankingSettings(object $config): array
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
     * Store OpenBanking transaction data in GlobalPayments transaction table
     *
     * @param int $order_id OpenCart order ID
     * @param object $openBankingGpResponse GlobalPayments response object
     * @param float $amount Transaction amount
     * @param string $currency Currency code
     * @param object $registry OpenCart registry
     */
    private static function storeOpenBankingTransaction(int $order_id, object $openBankingGpResponse, float $amount, string $currency, object $registry): void
    {
        try {
            $load = $registry->get('load');

            // Load the GlobalPayments UCP model to use its addTransaction method
            $load->model('extension/payment/globalpayments_ucp');
            $model = $registry->get('model_extension_payment_globalpayments_ucp');

            // Use the existing addTransaction method with OpenBanking-specific data
            $model->addTransaction(
                $order_id,
                'globalpayments_ucp', // Gateway ID for OpenBanking payments
                'charge', // Payment action - OpenBanking payments are captured immediately
                $amount,
                $currency,
                $openBankingGpResponse // Pass the response object directly
            );
        } catch (\Exception $e) {
            // Log error but don't fail the payment process
            error_log("OpenBanking transaction storage failed: " . $e->getMessage());
        }
    }

    /**
     * Log payment activities
     *
     * @param int $order_id
     * @param string|null $transaction_id
     * @param string $status
     * @param string|null $message
     * @param object|null $registry
     */
    private static function logPayment(
        int $order_id,
        ?string $transaction_id,
        string $status,
        ?string $message = null,
        ?object $registry = null
    ): void {
        try {
            // Get registry from global if not passed
            if (!$registry) {
                global $registry;
            }
            
            if ($registry && $registry->has('log')) {
                $log = $registry->get('log');
                
                $log_message = sprintf(
                    'Open Banking Payment - Order ID: %d, Transaction ID: %s, Status: %s',
                    $order_id,
                    $transaction_id ?: 'N/A',
                    $status
                );
                
                if ($message) {
                    $log_message .= ', Message: ' . $message;
                }
                
                $log->write($log_message);
            }
        } catch (\Exception $e) {
            // Fallback logging - write to error log
            error_log("OpenBanking Payment Log Error: " . $e->getMessage());
        }
    }

    /**
     * Validate OpenBanking configuration
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
     * Handle Open Banking payment status notification callback from payment gateway
     *
     * @param object|null $registry OpenCart registry object
     * @return array Response array
     */
    public static function handle_ob_status_notification(?object $registry = null): array
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
                    'message' => 'Open Banking notification processed successfully',
                    'processed' => true
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Could not process Open Banking notification',
                    'processed' => false
                ];
            }

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Open Banking webhook processing failed: ' . $e->getMessage(),
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
    private static function extract_transaction_id(array $data): ?string
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
    private static function extract_payment_status(array $data): string
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
    private static function extract_reference(array $data): ?string
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
    private static function extract_order_id_from_reference(string $reference): ?int
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
    private static function extract_order_id_from_platform(string $platform_order_id): ?int
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
                $comment = sprintf('Open Banking payment completed via status notification. %s', $callback_summary);
                $model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);

                // Update transaction status in database if it exists
                self::updateOpenBankingTransactionStatus($order_id, $transaction_id, 'SUCCESS', $registry);
                break;
            case 'DECLINED':
            case 'FAILED':
            case 'CANCELLED':
                // Get failed order status from configuration
                $order_status_id = 10; // Failed
                $comment = sprintf('Open Banking payment failed/declined via status notification. %s', $callback_summary);
                $model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);

                // Update transaction status in database if it exists
                self::updateOpenBankingTransactionStatus($order_id, $transaction_id, 'DECLINED', $registry);
                break;
            case 'PENDING':
                // Get pending order status from configuration
                $order_status_id = 1; // Pending
                $comment = sprintf('Open Banking payment is pending. %s', $callback_summary);
                $model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, false);

                // Update transaction status in database if it exists
                self::updateOpenBankingTransactionStatus($order_id, $transaction_id, 'PENDING', $registry);
                break;
            default:
                break;
        }
    }

    /**
     * Update OpenBanking transaction status in database
     *
     * @param int $order_id OpenCart order ID
     * @param string $transaction_id Transaction ID
     * @param string $status New status
     * @param object $registry OpenCart registry
     */
    private static function updateOpenBankingTransactionStatus(int $order_id, string $transaction_id, string $status, object $registry): void
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
            error_log("OpenBanking transaction status update failed: " . $e->getMessage());
        }
    }
}
