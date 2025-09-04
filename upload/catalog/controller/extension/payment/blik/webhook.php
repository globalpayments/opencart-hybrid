<?php

// Try to load BlikPayment class if available
$blik_payment_file = DIR_SYSTEM . 'library/globalpayments/globalpayments/' .
    'php-integrations/src/GlobalPayments/Gateways/DiUiApms/BlikPayment.php';
if (file_exists($blik_payment_file)) {
    require_once $blik_payment_file;
}

// Try to load OpenBankingPayment class if available
$ob_payment_file = DIR_SYSTEM . 'library/globalpayments/globalpayments/' .
    'php-integrations/src/GlobalPayments/Gateways/DiUiApms/OpenBankingPayment.php';
if (file_exists($ob_payment_file)) {
    require_once $ob_payment_file;
}

/**
 * GlobalPayments BLIK Webhook Controller
 * Handles webhook notifications from GlobalPayments for BLIK and Open Banking
 */
class ControllerExtensionPaymentBlikWebhook extends Controller
{
    /**
     * Main webhook handler - receives all GlobalPayments notifications
     */
    public function index(): void
    {
        // Set response headers for webhook
        $this->response->addHeader('Content-Type: application/json');
        
        // Get action parameter
        $action = $this->request->get['action'] ?? $this->request->post['action'] ?? '';
        
        // Get comprehensive request data (PrestaShop pattern)
        $request_data = $this->getWebhookRequestData();
        
        // Log incoming webhook for debugging
        error_log('GlobalPayments Webhook received: ' . json_encode($request_data));
        
        switch ($action) {
            case 'blik_status_handler':
                $this->handleBlikStatusNotification();
                break;
            case 'ob_status_handler':
                $this->handleObStatusNotification();
                break;
            default:
                $this->response->setOutput(json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action: ' . $action
                ]));
                $this->response->addHeader('HTTP/1.1 400 Bad Request');
                return;
        }
    }

    /**
     * Handle BLIK payment status notifications
     */
    private function handleBlikStatusNotification(): void
    {
        try {
            // Get comprehensive request data
            $request_data = $this->getWebhookRequestData();
            
            // Check if BlikPayment class exists and has the handler method
            if (
                class_exists('GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\BlikPayment') &&
                method_exists(
                    'GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\BlikPayment',
                    'handle_blik_status_notification'
                )
            ) {
                $result = \GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\BlikPayment::handle_blik_status_notification(
                    $request_data,
                    $this->registry
                );
                
                if ($result['status'] === 'success') {
                    $this->response->setOutput(json_encode($result));
                    $this->response->addHeader('HTTP/1.1 200 OK');
                } else {
                    $this->response->setOutput(json_encode($result));
                    $this->response->addHeader('HTTP/1.1 400 Bad Request');
                }
            } else {
                // Fallback: process notification using legacy webhook logic
                $result = $this->processWebhookFallback($request_data);
                
                if ($result['status'] === 'success') {
                    $this->response->setOutput(json_encode($result));
                    $this->response->addHeader('HTTP/1.1 200 OK');
                } else {
                    $this->response->setOutput(json_encode($result));
                    $this->response->addHeader('HTTP/1.1 400 Bad Request');
                }
            }
            
        } catch (Exception $e) {
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'BLIK webhook processing failed: ' . $e->getMessage()
            ]));
            $this->response->addHeader('HTTP/1.1 500 Internal Server Error');
        }
    }

    /**
     * Handle Open Banking payment status notifications
     */
    private function handleObStatusNotification(): void
    {
        try {
            // Check if OpenBankingPayment class exists and has the handler method
            if (
                class_exists('GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\OpenBankingPayment') &&
                method_exists(
                    'GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\OpenBankingPayment',
                    'handle_ob_status_notification'
                )
            ) {
                $result = \GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\OpenBankingPayment::handle_ob_status_notification(
                    $this->registry
                );
                
                if ($result['status'] === 'success') {
                    $this->response->setOutput(json_encode($result));
                    $this->response->addHeader('HTTP/1.1 200 OK');
                } else {
                    $this->response->setOutput(json_encode($result));
                    $this->response->addHeader('HTTP/1.1 400 Bad Request');
                }
            } else {
                $this->response->setOutput(json_encode([
                    'status' => 'error',
                    'message' => 'Open Banking webhook handler not implemented'
                ]));
                $this->response->addHeader('HTTP/1.1 501 Not Implemented');
            }
            
        } catch (Exception $e) {
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'Open Banking webhook processing failed: ' . $e->getMessage()
            ]));
            $this->response->addHeader('HTTP/1.1 500 Internal Server Error');
        }
    }

    /**
     * Fallback webhook processing when BlikPayment class is not available
     */
    private function processWebhookFallback(array $request_data): array
    {
        try {
            // Extract order ID from various sources
            $order_id = $this->extractOrderIdFromWebhook($request_data);
            
            if (!$order_id) {
                return [
                    'status' => 'error',
                    'message' => 'Could not extract order ID from webhook data'
                ];
            }
            
            // Load order
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);
            
            if (!$order) {
                return [
                    'status' => 'error',
                    'message' => 'Order not found: ' . $order_id
                ];
            }
            
            // Extract payment status
            $payment_status = $this->extractPaymentStatus($request_data);
            $transaction_id = $this->extractTransactionId($request_data);
            
            // Update order status
            $this->updateOrderFromWebhook($order, $payment_status, $transaction_id);
            
            return [
                'status' => 'success',
                'message' => 'Webhook processed successfully',
                'order_id' => $order_id
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Webhook fallback processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract order ID from webhook data
     */
    private function extractOrderIdFromWebhook(array $data): ?int
    {
        // Try reference field first
        if (isset($data['reference'])) {
            $reference = $data['reference'];
            
            // Direct numeric reference
            if (is_numeric($reference)) {
                return (int)$reference;
            }
            
            // Extract from patterns like "Order_41", "TRN_xxx_Order_350"
            if (preg_match('/_Order_(\d+)/', $reference, $matches)) {
                return (int)$matches[1];
            }
            
            // Extract any number from reference
            if (preg_match('/(\d+)/', $reference, $matches)) {
                return (int)$matches[1];
            }
        }
        
        // Try platforms array
        if (isset($data['platforms']) && is_array($data['platforms'])) {
            foreach ($data['platforms'] as $platform) {
                if (isset($platform['order_id'])) {
                    if (preg_match('/_Order_(\d+)/', $platform['order_id'], $matches)) {
                        return (int)$matches[1];
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Extract payment status from webhook data
     */
    private function extractPaymentStatus(array $data): string
    {
        $status_fields = ['status', 'payment_status', 'transaction_status'];
        
        foreach ($status_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }
        
        return 'UNKNOWN';
    }

    /**
     * Extract transaction ID from webhook data
     */
    private function extractTransactionId(array $data): ?string
    {
        $id_fields = ['id', 'transaction_id', 'reference'];
        
        foreach ($id_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }
        
        return null;
    }

    /**
     * Update order based on webhook notification
     */
    private function updateOrderFromWebhook(
        array $order,
        string $payment_status,
        ?string $transaction_id
    ): void {
        $order_id = $order['order_id'];
        $status_upper = strtoupper($payment_status);
        
        // Create comment with webhook details
        $comment = "Payment status: $payment_status";
        if ($transaction_id) {
            $comment .= ", Transaction ID: $transaction_id";
        }
        
        switch ($status_upper) {
            case 'CAPTURED':
            case 'COMPLETED':
            case 'SUCCESS':
                $order_status_id = 2; // Processing
                $notify = true;
                break;
            case 'DECLINED':
            case 'FAILED':
            case 'CANCELLED':
                $order_status_id = 7; // Failed
                $notify = true;
                break;
            case 'PENDING':
                $order_status_id = 1; // Pending
                $notify = false;
                break;
            default:
                return; // Unknown status, don't update
        }
        
        // Add order history
        $this->model_checkout_order->addOrderHistory(
            $order_id,
            $order_status_id,
            $comment,
            $notify
        );
    }

    /**
     * Get comprehensive webhook request data (PrestaShop pattern)
     * Combines GET, POST, and JSON body data
     */
    private function getWebhookRequestData(): array
    {
        // Start with GET and POST data
        $request_data = array_merge($_GET, $_POST);
        
        // Parse JSON body if present
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            $json_data = json_decode($raw_input, true);
            if (json_last_error() === JSON_ERROR_NONE && $json_data) {
                $request_data = array_merge($request_data, $json_data);
            }
        }
        
        return $request_data;
    }

    /**
     * Direct statusUpdate method for backward compatibility
     * Routes to BLIK handler by default
     */
    public function statusUpdate(): void
    {
        // Set response headers for webhook
        $this->response->addHeader('Content-Type: application/json');
        
        // Route to BLIK handler for backward compatibility
        $this->handleBlikStatusNotification();
    }
}