<?php
/**
 * GlobalPayments HPP Redirect Controller for OpenCart
 * Handles redirects from payment gateways back to the store after HPP payments
 */
class ControllerExtensionPaymentHppRedirect extends Controller
{
    /**
     * Main index method - handles redirect requests
     */
    public function index(): void
    {
        $action = $this->request->get['action'] ?? '';
        $requestData = $this->getRedirectRequestData();

        $this->log->write('HPP Redirect request data: ' . json_encode($requestData));
        $this->log->write('HPP Redirect action: ' . $action);

        try {
            switch ($action) {
                case 'hpp_redirect_handler':
                    $this->handleHppReturn($requestData);
                    break;
                case 'hpp_final':
                    $this->handleHppFinal();
                    break;
                default:
                    $this->log->write('HPP Redirect: Unknown action: ' . $action);
                    $this->redirectToCart();
                    break;
            }
        } catch (Exception $e) {
            $this->log->write('HPP Redirect Controller Error: ' . $e->getMessage());
            $this->redirectToCart();
        }
    }

    /**
     * Handle HPP return callback (intermediate page)
     * Creates auto-submit form to final processing
     */
    private function handleHppReturn(array $requestData): void
    {
        try {
            $signature = $_SERVER["HTTP_X_GP_SIGNATURE"] ?? '';
            $rawInput = file_get_contents('php://input');

            if (!$this->validateHppResponseHash()) {
                $this->log->write('HPP Return: Invalid signature');
                $this->redirectToCart();
                return;
            }

            $inputData = json_decode($rawInput, true);
            if (!$inputData) {
                $this->log->write('HPP Return: Invalid JSON data');
                $this->redirectToCart();
                return;
            }

            $this->renderHppReturnPage($signature, $rawInput);
        } catch (Exception $e) {
            $this->log->write('HPP Return Error: ' . $e->getMessage());
            $this->redirectToCart();
        }
    }

    /**
     * Handle final HPP processing after return page submission
     */
    private function handleHppFinal(): void
    {
        try {
            if (!isset($_POST['X-GP-Signature']) || !isset($_POST['gateway_response'])) {
                $this->log->write('HPP Final: Missing required POST data');
                $this->redirectToCart();
                return;
            }

            $signature = $_POST['X-GP-Signature'];
            $gatewayResponseJson = $_POST['gateway_response'];

            $gatewayResponseJson = $this->sanitizeHppJsonInput($gatewayResponseJson);

            if (!$this->validateFinalSignature($gatewayResponseJson, $signature)) {
                $this->log->write('HPP Final: Invalid signature');
                $this->redirectToCart();
                return;
            }

            $gatewayData = json_decode($gatewayResponseJson, true);
            if (!is_array($gatewayData) || empty($gatewayData)) {
                $this->log->write('HPP Final: Invalid response data');
                $this->redirectToCart();
                return;
            }

            $result = $this->processHppRedirectFallback($gatewayData);

            if ($result['status'] === 'success') {
                return;
            }

            $this->log->write('HPP Final processing failed: ' . $result['message']);
            $this->redirectToFailure($result['message']);
        } catch (Exception $e) {
            $this->log->write('HPP Final Error: ' . $e->getMessage());
            $this->redirectToCart();
        }
    }

    /**
     * Redirect to success page
     */
    private function redirectToSuccess(string $orderId, ?string $message = null): void
    {
        $url = $this->url->link('checkout/success', 'order_id=' . $orderId, true);

        if ($message) {
            $this->session->data['success'] = $message;
        }

        $this->cart->clear();
        $this->log->write("HPP Redirect: Redirecting to success page: $url");
        $this->response->redirect($url);
    }

    /**
     * Redirect to failure page
     */
    private function redirectToFailure(string $message = 'Payment failed'): void
    {
        $this->session->data['error'] = $message;
        $url = $this->url->link('checkout/cart', '', true);

        $this->log->write("HPP Redirect: Redirecting to failure page: $url - Message: $message");
        $this->response->redirect($url);
    }

    /**
     * Redirect to cart page
     */
    private function redirectToCart(): void
    {
        $url = $this->url->link('checkout/cart', '', true);

        $this->log->write("HPP Redirect: Redirecting to cart page: $url");
        $this->response->redirect($url);
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
     * Fallback HPP processing when HPPPayment class is not available
     * Adapted from webhook logic for redirect handling
     */
    private function processHppRedirectFallback(array $requestData): array
    {
        try {
            $orderId = $this->extractOrderIdFromRequest($requestData);

            if (!$orderId) {
                return [
                    'status' => 'error',
                    'message' => 'Could not extract order ID from request data'
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

            $paymentStatus = $this->extractPaymentStatusFromRequest($requestData);
            $transactionId = $this->extractTransactionIdFromRequest($requestData);

            $this->updateOrderFromRedirect($order, $paymentStatus, $transactionId);

            return [
                'status' => 'success',
                'message' => 'HPP redirect processed successfully',
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'HPP redirect fallback processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract order ID from request data (generic method for redirect requests)
     */
    private function extractOrderIdFromRequest(array $data): ?int
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
     * Extract payment status from request data
     */
    private function extractPaymentStatusFromRequest(array $data): string
    {
        $possibleFields = ['status', 'result'];

        foreach ($possibleFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return trim($data[$field]);
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Extract transaction ID from request data
     */
    private function extractTransactionIdFromRequest(array $data): ?string
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
     * Update order and redirect user based on payment status
     */
    private function updateOrderFromRedirect(
        array $order,
        string $paymentStatus,
        ?string $transactionId
    ): void {
        $orderId = $order['order_id'];
        $statusUpper = strtoupper($paymentStatus);

        $comment = "Redirect: Payment status: $paymentStatus";
        if ($transactionId) {
            $comment .= ", Transaction ID: $transactionId";
        }

        switch ($statusUpper) {
            case 'CAPTURED':
            case 'COMPLETED':
            case 'SUCCESS':
            case 'APPROVED':
                $orderStatusId = 2;
                $notify = true;
                $redirectAction = 'success';
                break;
            case 'DECLINED':
            case 'FAILED':
            case 'CANCELLED':
            case 'REJECTED':
                $orderStatusId = 10;
                $notify = true;
                $redirectAction = 'failure';
                break;
            case 'PENDING':
            case 'PROCESSING':
                $orderStatusId = 1;
                $notify = false;
                $redirectAction = 'pending';
                break;
            default:
                $this->log->write("HPP Redirect: Unknown status '$paymentStatus' for order $orderId");
                $this->redirectToCart();
                return;
        }

        $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $comment, $notify);

        switch ($redirectAction) {
            case 'success':
                $this->redirectToSuccess($orderId);
                break;
            case 'pending':
                $this->redirectToSuccess($orderId, 'Your HPP payment is being processed.');
                break;
            case 'failure':
                $this->redirectToFailure('HPP payment was ' . strtolower($paymentStatus));
                break;
        }
    }

    /**
     * Get comprehensive redirect request data
     * Combines GET, POST, and JSON body data (same pattern as webhook)
     */
    private function getRedirectRequestData(): array
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

    /**
     * Render the HPP return page with auto-submit form
     * Based on WordPress implementation pattern
     */
    private function renderHppReturnPage(string $signature, string $inputData): void
    {
        $signature = htmlspecialchars($signature, ENT_QUOTES, 'UTF-8');
        $inputData = $this->sanitizeHppJsonInput($inputData);

        $server = $this->config->get('config_secure')
            ? $this->config->get('config_ssl')
            : $this->config->get('config_url');
        $finalUrl = $server . 'index.php?route=extension/payment/hpp/redirect&action=hpp_final';

        echo '<!DOCTYPE html>';
        echo '<html><head><title>Processing Payment...</title></head><body>';
        echo '<h1>Processing your payment...</h1>';
        echo '<form id="hpp_final_form" method="POST" action="' . htmlspecialchars($finalUrl, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="X-GP-Signature" value="' . $signature . '">';
        echo '<input type="hidden" name="gateway_response" value="' . htmlspecialchars($inputData, ENT_QUOTES, 'UTF-8') . '">';
        echo '</form>';
        echo '<script>';
        echo 'document.getElementById("hpp_final_form").submit();';
        echo '</script>';
        echo '</body></html>';
        exit;
    }

    /**
     * Validate final signature from POST data
     */
    private function validateFinalSignature(string $gatewayResponseJson, string $signature): bool
    {
        if (empty($gatewayResponseJson) || empty($signature)) {
            $this->log->write('HPP Final: Empty signature or response data');
            return false;
        }

        $isProduction = $this->config->get('payment_globalpayments_ucp_is_production');

        if ($isProduction == 1) {
            $appKey = $this->config->get('payment_globalpayments_ucp_app_key');
        } else {
            $appKey = $this->config->get('payment_globalpayments_ucp_sandbox_app_key');
        }

        if (empty($appKey)) {
            $this->log->write('HPP Final: App key not found in gateway settings');
            return false;
        }

        $expectedSignature = hash('sha512', $gatewayResponseJson . $appKey);

        return hash_equals($expectedSignature, $signature);
    }
}