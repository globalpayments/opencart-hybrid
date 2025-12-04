<?php
/**
 * GlobalPayments HPP Webhook Controller
 * Handles webhook notifications from GlobalPayments for Hosted Payment Page (HPP)
 */
class ControllerExtensionPaymentHppWebhook extends Controller
{
    /**
     * Main webhook handler - receives all GlobalPayments HPP notifications
     */
    public function index(): void
    {
        $action = $this->request->get['action'] ?? $this->request->post['action'] ?? '';
        $requestData = $this->getWebhookRequestData();

        error_log('GlobalPayments HPP Webhook received: ' . json_encode($requestData));

        switch ($action) {
            case 'hpp_status_handler':
                $this->handleHppStatusNotification($requestData);
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
     * Handle HPP payment status notifications
     */
    private function handleHppStatusNotification(array $requestData): void
    {
        try {
            $result = $this->processHppWebhookFallback($requestData);

            if ($result['status'] === 'success') {
                $this->response->setOutput(json_encode($result));
                $this->response->addHeader('HTTP/1.1 200 OK');
            } else {
                $this->response->setOutput(json_encode($result));
                $this->response->addHeader('HTTP/1.1 400 Bad Request');
            }
        } catch (Exception $e) {
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'HPP webhook processing failed: ' . $e->getMessage()
            ]));
            $this->response->addHeader('HTTP/1.1 500 Internal Server Error');
        }
    }

    /**
     * Fallback HPP webhook processing when HPPPayment class is not available
     */
    private function processHppWebhookFallback(array $requestData): array
    {
        try {
            if (!$this->validateHppResponseHash()) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid HPP response hash - possible tampering'
                ];
            }

            $orderId = $this->extractOrderIdFromWebhook($requestData);

            if (!$orderId) {
                return [
                    'status' => 'error',
                    'message' => 'Could not extract order ID from webhook data'
                ];
            }

            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($orderId);

            if (!$order) {
                return [
                    'status' => 'error',
                    'message' => 'Order not found: ' . $orderId
                ];
            }

            $paymentStatus = $this->extractPaymentStatus($requestData);
            $transactionId = $this->extractTransactionId($requestData);

            $this->updateOrderFromWebhook($order, $paymentStatus, $transactionId);

            return [
                'status' => 'success',
                'message' => 'HPP webhook processed successfully',
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'HPP webhook fallback processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate HPP response using GlobalPayments signature validation
     */
    private function validateHppResponseHash(): bool
    {
        $signature = $_SERVER["HTTP_X_GP_SIGNATURE"] ?? '';
        $rawInput = file_get_contents('php://input');

        if (empty($rawInput) || empty($signature)) {
            error_log('HPP: Empty signature or input data');
            return false;
        }

        $cleanInput = $this->sanitizeHppJsonInput($rawInput);

        $this->load->model('setting/setting');

        $isProduction = $this->config->get('payment_globalpayments_ucp_is_production');

        if ($isProduction == 1) {
            $appKey = $this->config->get('payment_globalpayments_ucp_app_key');
        } else {
            $appKey = $this->config->get('payment_globalpayments_ucp_sandbox_app_key');
        }

        if (empty($appKey)) {
            error_log('HPP: App key not found in gateway settings');
            return false;
        }

        $expectedSignature = hash('sha512', $cleanInput . $appKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Sanitize JSON input by cleaning escaped characters
     */
    private function sanitizeHppJsonInput(string $rawInput): string
    {
        if (strpos($rawInput, '\"') !== false || strpos($rawInput, '\\') !== false) {
            $replacements = [
                '\"' => '"',
                '\\/' => '/',
                '\\\\' => '\\'
            ];

            return str_replace(array_keys($replacements), array_values($replacements), $rawInput);
        }

        return $rawInput;
    }

    /**
     * Extract order ID from webhook data (generic method for status webhooks)
     */
    private function extractOrderIdFromWebhook(array $data): ?int
    {
        if (isset($data['ORDER_ID']) && is_numeric($data['ORDER_ID'])) {
            return (int)$data['ORDER_ID'];
        }

        if (isset($data['reference'])) {
            $reference = $data['reference'];

            if (is_numeric($reference)) {
                return (int)$reference;
            }

            if (preg_match('/_Order_(\d+)/', $reference, $matches)) {
                return (int)$matches[1];
            }

            if (preg_match('/(\d+)/', $reference, $matches)) {
                return (int)$matches[1];
            }
        }

        if (isset($data['order_id']) && is_numeric($data['order_id'])) {
            return (int)$data['order_id'];
        }

        return null;
    }

    /**
     * Extract payment status from webhook data
     */
    private function extractPaymentStatus(array $data): string
    {
        $possibleFields = ['status'];

        foreach ($possibleFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return trim($data[$field]);
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Extract transaction ID from webhook data
     */
    private function extractTransactionId(array $data): ?string
    {
        $idFields = ['id', 'transaction_id', 'reference'];

        foreach ($idFields as $field) {
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
        string $paymentStatus,
        ?string $transactionId
    ): void {
        $orderId = $order['order_id'];
        $statusUpper = strtoupper($paymentStatus);

        $comment = "Webhook: Payment status: $paymentStatus";
        if ($transactionId) {
            $comment .= ", Transaction ID: $transactionId";
        }

        switch ($statusUpper) {
            case 'CAPTURED':
            case 'COMPLETED':
            case 'SUCCESS':
                $orderStatusId = 2;
                $notify = true;
                break;
            case 'DECLINED':
            case 'FAILED':
            case 'CANCELLED':
                $orderStatusId = 10;
                $notify = true;
                break;
            case 'PENDING':
                $orderStatusId = 1;
                $notify = false;
                break;
            default:
                return;
        }

        $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $comment, $notify);
    }

    /**
     * Get comprehensive webhook request data
     * Combines GET, POST, and JSON body data
     */
    private function getWebhookRequestData(): array
    {
        $requestData = array_merge($_GET, $_POST);

        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && $jsonData) {
                $requestData = array_merge($requestData, $jsonData);
            }
        }

        return $requestData;
    }
}