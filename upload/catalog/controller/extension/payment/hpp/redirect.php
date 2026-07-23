<?php

/**
 * GlobalPayments HPP Redirect Controller
 *
 * Handles redirects from GlobalPayments Hosted Payment Page back to the store
 */
class ControllerExtensionPaymentHppRedirect extends Controller
{
    /**
     * Main index method - handles redirect requests
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $this->handleHppRedirect();
        } catch (Exception $e) {
            $this->log('HPP Redirect Controller Error: ' . $e->getMessage());
            $this->renderErrorHtml('An error occurred processing your payment. Please contact support.');
        }
    }

    /**
     * Handle HPP payment redirects
     *
     * HPP sends POST request with JSON body containing payment data
     * and X-GP-Signature header for validation
     *
     * @return void
     */
    private function handleHppRedirect(): void
    {
        try {
            // Validate that this is a POST request
            if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
                $this->log('HPP Redirect: Invalid request method');
                $this->renderErrorHtml('Invalid request method');
                return;
            }

            // Get and validate payment data from POST body
            $paymentData = $this->validateAndParsePaymentData();

            if (isset($paymentData['error'])) {
                $this->log('HPP Redirect: Payment data validation failed');
                $this->renderErrorHtml($paymentData['error']);
                return;
            }

            // Extract order ID from payment data
            $orderId = $this->extractOrderId($paymentData);

            if (!$orderId) {
                $this->log('HPP Redirect: Could not extract order ID from payment data');
                $this->renderErrorHtml('Order ID not found in payment data');
                return;
            }

            // Load order
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($orderId);

            if (!$order) {
                $this->log('HPP Redirect: Order not found with ID: ' . $orderId);
                $this->renderErrorHtml('Order not found');
                return;
            }

            // Extract payment status and transaction data
            $status = isset($paymentData['status']) ? strtoupper($paymentData['status']) : 'UNKNOWN';
            $transactionId = isset($paymentData['id']) ? $paymentData['id'] : '';
            $reference = isset($paymentData['reference']) ? $paymentData['reference'] : '';
            $message = isset($paymentData['message']) ? $paymentData['message'] : '';
            $hasInstallments = $this->hasInstallments($paymentData);



            $this->processHppRedirect($order, $status, $transactionId, $message, $paymentData, $hasInstallments);

        } catch (Exception $e) {
            $this->log('HPP Redirect Error: ' . $e->getMessage());
            $this->renderErrorHtml('An error occurred processing your payment. Please contact support.');
        }
    }

    /**
     * Validate signature and parse payment data from POST body
     *
     * @return array Parsed payment data or error array
     */
    private function validateAndParsePaymentData(): array
    {
        try {
            // Get raw input from POST body
            $rawInput = file_get_contents('php://input');

            if (empty($rawInput)) {
                $this->log('HPP Redirect: No POST body data received');
                return ['error' => 'No payment data received from HPP'];
            }

            // Get X-GP-Signature header
            $gpSignature = $this->getGpSignature();

            if (!$gpSignature) {
                $this->log('HPP Redirect: Missing or invalid X-GP-Signature header');
                return ['error' => 'Invalid or missing signature in headers'];
            }


            // Get app key for validation
            $appKey = $this->getAppKey();

            if (empty($appKey)) {
                $this->log('HPP Redirect: App key not configured');
                return ['error' => 'HPP configuration is incomplete. App key is missing.'];
            }

            // Validate signature
            if (!$this->validateSignature($rawInput, $gpSignature, $appKey)) {
                $this->log('HPP Redirect: Signature validation failed');
                return ['error' => 'Signature validation failed for HPP payment data'];
            }

            // Parse JSON input
            $paymentData = json_decode($rawInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log('HPP Redirect: Invalid JSON data - ');
                return ['error' => 'Invalid JSON payment data received from HPP'];
            }

            if (empty($paymentData)) {
                return ['error' => 'Invalid payment data format received from HPP'];
            }

            return $paymentData;

        } catch (Exception $e) {
            $this->log('HPP Redirect: Exception in validateAndParsePaymentData');
            return ['error' => 'Error processing payment data: ' . $e->getMessage()];
        }
    }

    /**
     * Get X-GP-Signature header from request
     *
     * @return string|null Signature or null if not found
     */
    private function getGpSignature(): ?string
    {
        // Try X-GP-Signature first
        if (isset($this->request->server['HTTP_X_GP_SIGNATURE'])) {
            $signature = $this->request->server['HTTP_X_GP_SIGNATURE'];
        } elseif (function_exists("getallheaders") || function_exists('apache_request_headers')) {
            $headers = function_exists("getallheaders") ? getallheaders() : apache_request_headers();
            $headers = array_change_key_case($headers);
            if (isset($headers['x-gp-signature'])) {
                $signature = array_change_key_case($headers['x-gp-signature']);
            }
        }

        if (empty($signature)) {
            return null;
        }

        // trim signature
        $signature = trim($signature);

        // Validate format (should be hex string)
        if (!preg_match('/^[a-fA-F0-9]+$/', $signature)) {
            $this->log('HPP Redirect: Invalid signature format');
            return null;
        }

        // Validate length (SHA512 = 128 chars)
        if (strlen($signature) !== 128) {
            $this->log('HPP Redirect: Signature length incorrect (expected 128, got ' . strlen($signature) . ')');
            return null;
        }

        return $signature;
    }

    /**
     * Get App Key from configuration
     *
     * @return string|null App key or null
     */
    private function getAppKey(): ?string
    {
        // Load UCP config to get app key
        $this->load->model('extension/payment/globalpayments_ucp');

        $isProduction = $this->config->get('payment_globalpayments_ucp_is_production');

        return ($isProduction == 1) ?
            $this->config->get('payment_globalpayments_ucp_app_key') :
            $this->config->get('payment_globalpayments_ucp_sandbox_app_key');
    }

    /**
     * Validate signature using SHA512 hash
     *
     * @param string $rawInput  Raw JSON input
     * @param string $signature Signature from header
     * @param string $appKey    App key for validation
     *
     * @return bool True if valid
     */
    private function validateSignature(
        string $rawInput,
        string $signature,
        string $appKey
    ): bool {
        try {
            if (empty($rawInput) || empty($signature) || empty($appKey)) {
                return false;
            }

            $parsedInput = json_decode($rawInput, true);

            if (!$parsedInput) {
                $this->log('HPP Redirect: Failed to parse JSON for signature validation');
                return false;
            }

            // Minify JSON (no spaces, unescaped slashes/unicode)
            $minifiedInput = json_encode($parsedInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Create expected signature using SHA512 hash (HPP specific)
            $expectedSignature = hash('sha512', $minifiedInput . $appKey);

            // Compare signatures (case-insensitive, time-safe)
            return hash_equals(strtolower($expectedSignature), strtolower($signature));

        } catch (Exception $e) {
            $this->log('HPP Redirect: Signature validation exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract order ID from payment data
     *
     * @param array $paymentData Payment data from HPP
     *
     * @return string|null Order ID or null
     */
    private function extractOrderId(array $paymentData): ?string
    {
        if (!empty($paymentData['link_data']['reference'])) {
            $reference = $paymentData['link_data']['reference'];

            if (strpos($reference, 'order_id_') === 0) {
                $orderId = str_replace('order_id_', '', $reference);
                return $orderId;
            }
        }

        return null;
    }

    /**
     * Process HPP payment redirect response
     *
     * @param array  $order         Order data
     * @param string $status        Payment status from HPP
     * @param string $transactionId Transaction ID
     * @param string $message       Response message
     * @param array  $paymentData   Full payment data for reference
     *
     * @return void
     */
    private function processHppRedirect(
        array $order,
        string $status,
        string $transactionId,
        string $message,
        array $paymentData,
        bool $hasInstallments
    ): void {
        $orderId = $order['order_id'];


        switch (strtoupper($status)) {
            case 'INITIATED':
            case 'PREAUTHORIZED':
            case 'CAPTURED':
            case 'SUCCESS':
            case 'COMPLETED':
            case 'APPROVED':
                // Successful payment
                $this->logHppTransaction($order, $status, $transactionId, $message);
                $this->updateOrderStatus($order, $status, $transactionId, 'success', $message, $paymentData, $hasInstallments);

                // Clear cart and fire events
                $this->cart->clear();

                // Render branded success page with auto-redirect
                $this->renderSuccessHtml($orderId);
                break;

            case 'PENDING':
            case 'PROCESSING':
                // Payment pending
                $this->logHppTransaction($order, $status, $transactionId, $message);
                $this->updateOrderStatus($order, $status, $transactionId, 'pending', $message);

                // Redirect to success page with message
                $this->redirectToSuccess($orderId, 'Your payment is being processed.');
                break;

            case 'DECLINED':
            case 'FAILED':
            case 'CANCELLED':
            case 'REJECTED':
            case 'ERROR':
                // Payment failed
                $this->log("HPP Redirect: Payment failed for order $orderId - Status: $status");
                $this->updateOrderStatus($order, $status, $transactionId, 'failed', $message);

                // Render branded error page with auto-redirect back to cart
                $errorMessage = 'Payment was ' . strtolower($status);
                if ($message) {
                    $errorMessage .= ': ' . $message;
                }
                $this->renderErrorHtml($errorMessage);
                break;

            default:
                // Unknown status
                $this->log("HPP Redirect: Unknown status '$status' for order $orderId");
                $this->renderErrorHtml('Unexpected payment status. Please contact support.');
                break;
        }
    }

    /**
     * Log HPP transaction in database
     *
     * @param array  $order         Order data
     * @param string $status        Payment status
     * @param string $transactionId Transaction ID
     * @param string $message       Response message
     *
     * @return void
     */
    private function logHppTransaction(
        array $order,
        string $status,
        string $transactionId,
        string $message
    ): void {
        try {
            $this->load->model('extension/payment/globalpayments_ucp');

            $gatewayResponse = (object) [
                'transactionReference' => (object) [
                    'transactionId' => $transactionId ?: 'HPP-' . time(),
                    'clientTransactionId' => 'ORDER-' . $order['order_id']
                ],
                'responseCode' => $status,
                'responseMessage' => $message ?: $status,
                'timestamp' => date('c')
            ];

            $this->model_extension_payment_globalpayments_ucp->addTransaction(
                $order['order_id'],
                'globalpayments_ucp',
                'charge',
                $order['total'] * $order['currency_value'],
                $order['currency_code'],
                $gatewayResponse
            );

        } catch (Exception $e) {
            $this->log('HPP Redirect: Failed to log transaction: ' . $e->getMessage());
        }
    }

    /**
     * Update order status based on payment result
     *
     * @param array $order       Order ID
     * @param string $paymentStatus Payment status
     * @param string $transactionId Transaction ID
     * @param string $resultType    Result type (success/pending/failed)
     * @param string $message       Response message
     *
     * @return void
     */
    private function updateOrderStatus(
        array $order,
        string $paymentStatus,
        string $transactionId,
        string $resultType,
        string $message = '',
        array $paymentData = [],
        bool $hasInstallments = false
    ): void {
        $this->load->model('checkout/order');

        $comment = sprintf(
            'HPP redirect received - Status: %s, Transaction ID: %s',
            $paymentStatus,
            $transactionId ?: 'N/A'
        );
        if ($hasInstallments) {
            if (isset($paymentData['installment']['terms'])) {
                // Add installments data to the order comment, 
                // this will also show on the customer success emails
                $comment .= $this->buildInstallmentOrderNote($paymentData['installment']['terms']);
                $this->addInstallmentsData($order, $paymentData['installment']['terms']);
            } else {
                $this->log("Hpp redirect: Has installments but no terms");
            }
        }

        if ($message) {
            $comment .= ', Message: ' . $message;
        }

        $statusMap = [
            'success' => ['status_id' => 2, 'notify' => true],
            'pending' => ['status_id' => 1, 'notify' => false],
            'failed' => ['status_id' => 7, 'notify' => true],
        ];

        $config = $statusMap[$resultType] ?? ['status_id' => 1, 'notify' => false];

        $this->model_checkout_order->addOrderHistory(
            $order['order_id'],
            $config['status_id'],
            $comment,
            $config['notify']
        );
    }

    /**
     * Redirect to success page
     *
     * @param string      $orderId Order ID
     * @param string|null $message Optional success message
     *
     * @return void
     */
    private function redirectToSuccess(string $orderId, ?string $message = null): void
    {
        $url = $this->url->link('checkout/success', 'order_id=' . $orderId, true);

        if ($message) {
            $this->session->data['success'] = $message;
        }

        $this->cart->clear();
        $this->response->redirect($url);
    }




    /**
     * Create branded success response HTML page
     *
     * @param string $order_id Order ID for success redirect
     * @return void
     */
    private function renderSuccessHtml(string $order_id): void
    {
        $storeName = $this->config->get('config_name') ?: 'Store';
        $storeLogo = $this->getStoreLogo();
        $successUrl = html_entity_decode($this->url->link('checkout/success', 'order_id=' . $order_id, true));

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .header {
            margin-bottom: 2rem;
        }

        .logo {
            max-height: 60px;
            margin: 0 1rem 1rem;
        }

        .store-name {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1rem;
        }

        .status-icon {
            font-size: 4rem;
            margin: 1rem 0;
            color: #28a745;
        }

        h2 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        p {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .powered-by {
            color: #999;
            font-size: 0.875rem;
        }

        .redirect-message {
            color: #666;
            font-style: italic;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">';

        if ($storeLogo) {
            $html .= '<img src="' . htmlspecialchars($storeLogo, ENT_QUOTES) . '" alt="' . htmlspecialchars($storeName, ENT_QUOTES) . '" class="logo">';
        } else {
            $html .= '<h1 class="store-name">' . htmlspecialchars($storeName, ENT_QUOTES) . '</h1>';
        }

        $html .= '
        </div>

        <div class="content">
            <div class="status-icon">&#x2714;</div>
            <h2>Payment Successful</h2>
            <p>Your payment has been processed successfully.</p>

            <p class="redirect-message">Redirecting to order confirmation in <span id="countdown">2</span> seconds...</p>
        </div>

        <div class="footer">
            <div class="powered-by">Powered by GlobalPayments</div>
        </div>
    </div>

    <script>
        let countdown = 2;
        const countdownElement = document.getElementById("countdown");
        const timer = setInterval(function() {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(timer);
            }
        }, 1000);

        setTimeout(function() {
            console.log("HPP: Redirecting to success page");
            window.location.href = "' . htmlspecialchars($successUrl, ENT_QUOTES) . '";
        }, 2000);
    </script>
</body>
</html>';

        $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
        $this->response->setOutput($html);
    }

    /**
     * Create branded error response HTML page
     *
     * @param string $message      Error message to display
     * @param bool   $doRedirect Whether to auto-redirect (default: true)
     *
     * @return void
     */
    private function renderErrorHtml(string $message, bool $doRedirect = true)
    {
        $storeName = $this->config->get('config_name') ?: 'Store';
        $storeLogo = $this->getStoreLogo();
        $cartUrl = html_entity_decode($this->url->link('checkout/cart', '', true));

        $redirectScript = '';
        if ($doRedirect) {
            $redirectScript = '<script>
let countdown = 3;
const countdownElement = document.getElementById("countdown");
const timer = setInterval(function() {
    countdown--;
    if (countdownElement) {
        countdownElement.textContent = countdown;
    }
    if (countdown <= 0) {
        clearInterval(timer);
    }
}, 1000);

setTimeout(function() {
    window.location.href = "' . htmlspecialchars($cartUrl, ENT_QUOTES) . '";
}, 3000);
</script>';
        }

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .header {
            margin-bottom: 2rem;
        }

        .logo {
            max-height: 60px;
            margin: 0 1rem 1rem;
        }

        .store-name {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1rem;
        }

        .status-icon {
            font-size: 4rem;
            margin: 1rem 0;
            color: #dc3545;
        }

        h2 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        p {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .powered-by {
            color: #999;
            font-size: 0.875rem;
        }

        .redirect-message {
            color: #666;
            font-style: italic;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">';

        if ($storeLogo) {
            $html .= '<img src="' . htmlspecialchars($storeLogo, ENT_QUOTES) . '" alt="' . htmlspecialchars($storeName, ENT_QUOTES) . '" class="logo">';
        } else {
            $html .= '<h1 class="store-name">' . htmlspecialchars($storeName, ENT_QUOTES) . '</h1>';
        }

        $html .= '
        </div>

        <div class="content">
            <div class="status-icon">&#x2716;</div>
            <h2>Payment Error</h2>
            <p>' . htmlspecialchars($message, ENT_QUOTES) . '</p>';

        if ($doRedirect) {
            $html .= '
            <p class="redirect-message">Redirecting back to checkout in <span id="countdown">3</span> seconds...</p>';
        }

        $html .= '
        </div>

        <div class="footer">
            <div class="powered-by">Powered by GlobalPayments</div>
        </div>
    </div>

    ' . $redirectScript . '
</body>
</html>';

        $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
        $this->response->setOutput($html);
    }

    /**
     * Get store logo URL for branding
     *
     * @return string|null Logo URL or null
     */
    private function getStoreLogo(): ?string
    {
        $logo = $this->config->get('config_logo');

        if ($logo && file_exists(DIR_IMAGE . $logo)) {
            // Use proper OpenCart HTTP/HTTPS detection
            if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
                return $this->config->get('config_ssl') . 'image/' . $logo;
            } else {
                return $this->config->get('config_url') . 'image/' . $logo;
            }
        }

        return null;
    }

    /**
     * Write to log if debug is enabled
     * @param String msg Error message 
     * @return void
     */

    private function log(string $msg): void
    {
        $this->load->model('extension/payment/globalpayments_ucp');
        $debugEnabled = !empty($this->config->get('payment_globalpayments_ucp_debug'));
        if ($debugEnabled && !empty($msg)) {
            $this->log->write($msg);
        }
    }
    
    /**
     * Checks if payment data contains installments information
     * 
     * @param array $paymentData
     * @return bool
     */
    private function hasInstallments( array $paymentData ): bool
    {
        return isset($paymentData['installment']) &&
            !empty($paymentData['installment']) &&
            isset($paymentData['installment']) &&
            !empty($paymentData['installment']['terms']);
    }


    /**
     * Build an order note from installment terms data.
     *
     * @param array $terms
     * @return string
     */
    private function buildInstallmentOrderNote(array $terms): string
    {
        $count = $terms['count'] ?? 0;
        $timeUnit = strtolower($terms['time_unit'] ?? 'period');
        $costPercentage = $terms['cost_percentage'] ?? '0.00';
        $totalAmount = $terms['total_amount'] ?? 0;
        $totalPlanCost = $terms['total_plan_cost'] ?? 0;
        $currency = $terms['currency'] ?? '';
        $description = $terms['description'] ?? '';
        $termsUrl = $terms['terms_and_conditions_url'] ?? '';
        $feeTotal = $terms['fees']['total_amount'] ?? 0;
        $feeSubsequent = $terms['fees']['subsequent_amount'] ?? 0;

        return sprintf(
            "\r\nInstallment plan selected: %d %s(s). " .
            "\r\nTotal purchase amount: %s %.2f. " .
            "\r\nTotal plan cost: %s %.2f. " .
            "\r\nAPR / fee percentage: %s%%. " .
            "\r\nUpfront fee: %s %.2f, subsequent installments: %s %.2f. " .
            "\r\nDetails: %s " .
            "\r\nTerms & Conditions: %s",
            $count,
            $timeUnit,
            $currency,
            $totalAmount / 100,
            $currency,
            $totalPlanCost / 100,
            $costPercentage,
            $currency,
            $feeTotal / 100,
            $currency,
            $feeSubsequent / 100,
            $description,
            $termsUrl
        );
    }

    /**
     * Adds installments data to the payment_custom_field and updates the DB
     * 
     * @param array $order
     * @param array $installmentsData
     * @return void
     */
    private function addInstallmentsData(array $order, array $installmentsData)
    {
        $currentPaymentData = $order['payment_custom_field'];
        $newInstallmentsData['hpp_installments_data'] = json_encode($installmentsData);
        $newPaymentData = array_merge($currentPaymentData, $newInstallmentsData);

        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order` SET 
                payment_custom_field = '" . $this->db->escape(json_encode($newPaymentData)) . "' 
                WHERE order_id = '" . (int) $order['order_id'] . "'"
        );
    }
}