<?php

/**
 * GlobalPayments HPP Webhook Controller
 *
 */
class ControllerExtensionPaymentHppWebhook extends Controller
{
    /**
     * Main webhook handler - receives all GlobalPayments HPP notifications
     *
     * @return void
     */
    public function index() : void
    {
        // Validate request method - webhooks should be POST
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->log('HPP Webhook: Invalid request method');
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => 'Invalid request method'
            ], 405);
            return;
        }

        // Get webhook data and process it
        $requestData = $this->getWebhookRequestData();

        // Process the HPP status notification
        $this->handleHppStatusNotification($requestData);
    }

    /**
     * Handle HPP payment status notifications
     *
     * @param array $requestData Webhook request data
     * @return void
     */
    private function handleHppStatusNotification( array $requestData )
    {
        try {
            $result = $this->processHppWebhook($requestData);

            if ( $result['status'] === 'success' ) {
                $this->sendJsonResponse( $result, 200 );
            } else {
                $this->sendJsonResponse( $result, 400 );
            }
        } catch (Exception $e) {
            $this->log('HPP Webhook: Exception - ' . $e->getMessage());
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => 'HPP webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process HPP webhook notification
     *
     * @param array $requestData Webhook request data
     * @return array Processing result
     */
    private function processHppWebhook( array $requestData ) :array
    {
        try {

               if ( !$this->validateHppResponseHash() ) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid HPP response hash - possible tampering'
                ];
            }

            $orderId = $this->extractOrderIdFromWebhook( $requestData );

            if ( !$orderId ) {
                return [
                    'status' => 'error',
                    'message' => 'Could not extract order ID from webhook data'
                ];
            }

            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder( $orderId );

            if ( !$order ) {
                return [
                    'status' => 'error',
                    'message' => 'Order not found: ' . $orderId
                ];
            }

         

            $paymentStatus = $this->extractPaymentStatus( $requestData );
            $transactionId = $this->extractTransactionId( $requestData );

            $this->updateOrderFromWebhook( $order, $paymentStatus, $transactionId );

            return [
                'status' => 'success',
                'message' => 'HPP webhook processed successfully',
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            $this->log( "HPP webhook processing failed: " . $e->getMessage() );
            return [
                'status' => 'error',
                'message' => 'HPP webhook processing failed'
            ];
        }
    }

    /**
     * Validate HPP response using GlobalPayments signature validation
     *
     * @return bool True if signature is valid
     */
    private function validateHppResponseHash() : bool
    {
        $signature = $this->getGpSignatureFromHeaders();

        if ( empty( $signature ) ) {
            $this->log('HPP Webhook: No X-GP-Signature found in request headers');
            return false;
        }

        $rawInput = file_get_contents( 'php://input' );

        if ( empty( $rawInput ) ) {
            $this->log('HPP Webhook: No POST body data received');
            return false;
        }

        $appKey = $this->getAppKey();

        if ( empty( $appKey ) ) {
            $this->log('HPP Webhook: App key not configured');
            return false;
        }

        $parsedInput = json_decode( $rawInput, true );

        if ( !$parsedInput ) {
            $this->log('HPP Webhook: Failed to parse JSON input for signature validation');
            return false;
        }

        $minifiedInput = json_encode( $parsedInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        $expectedSignature = hash( 'sha512', $minifiedInput . $appKey );
        $isValid = hash_equals( strtolower( $expectedSignature ), strtolower( $signature ) );

        if ( !$isValid ) {
            $this->log('HPP Webhook: Signature validation failed');
        }

        return $isValid;
    }

    /**
     * Get GP signature from request headers
     *
     * @return string Signature or empty string
     */
    private function getGpSignatureFromHeaders() : string
    {
        $signature = "";
        // Try X-GP-Signature first
        if ( isset( $this->request->server['HTTP_X_GP_SIGNATURE'] ) ) 
        {
            $signature = $this->request->server['HTTP_X_GP_SIGNATURE'];
        }
        
        elseif ( function_exists( "getallheaders" ) || function_exists( 'apache_request_headers' ) )
        {
            $headers = function_exists( "getallheaders" ) ? getallheaders() : apache_request_headers();
            $headers = array_change_key_case( $headers );
            if ( isset( $headers['x-gp-signature'] ) ) {
                $signature =  $headers['x-gp-signature'];
            } 
        }

        if ( empty( $signature ) ) {
            return null;
        }

        // trim signature
        $signature = trim( $signature );

        // Validate format (should be hex string)
        if ( !preg_match('/^[a-fA-F0-9]+$/', $signature ) ) {
            $this->log('HPP Redirect: Invalid signature format');
            return null;
        }

        // Validate length (SHA512 = 128 chars)
        if ( strlen( $signature ) !== 128 ) {
            $this->log( 'HPP Redirect: Signature length incorrect (expected 128, got ' . strlen( $signature ) . ')');
            return null;
        }

        return $signature;
    }

    /**
     * Get app key based on environment mode
     *
     * @return string App key or empty string
     */
    private function getAppKey() : string
    {
         // Load UCP config to get app key
        $this->load->model( 'extension/payment/globalpayments_ucp' );
        $isProduction = $this->config->get( 'payment_globalpayments_ucp_is_production' );

        return ( $isProduction == 1 ) ? 
        $this->config->get( 'payment_globalpayments_ucp_app_key' ) :
        $this->config->get( 'payment_globalpayments_ucp_sandbox_app_key' );
    }

    /**
     * Extract order ID from webhook data
     *
     * @param array $data Webhook data
     * @return int|null Order ID or null
     */
    private function extractOrderIdFromWebhook(array $data) : ?int
    {
        if (!empty($data['link_data']['reference'])) {
            $orderId = $this->extractOrderIdFromReference($data['link_data']['reference']);
            if ($orderId) {
                return $orderId;
            }
        }

        return null;
    }

    /**
     * Extract order ID from reference string
     *
     * @param string $reference Reference string
     *
     * @return int|null Order ID or null
     */
    private function extractOrderIdFromReference( string $reference ) : ?int
    {
        // Extract from order_id_ prefix
        if (strpos($reference, 'order_id_') === 0) {
            $orderId = str_replace('order_id_', '', $reference);
            if (is_numeric($orderId)) {
                return (int) $orderId;
            }
        }

        return null;
    }

    /**
     * Extract payment status from webhook data
     *
     * @param array $data Webhook data
     * @return string Payment status
     */
    private function extractPaymentStatus(array $data) : string
    {
        return isset( $data[ 'status' ] ) && !empty( $data[ 'status' ] )
            ? trim( $data['status'] )
            : 'UNKNOWN';
    }

    /**
     * Extract transaction ID from webhook data
     *
     * @param array $data Webhook data
     *
     * @return string|null Transaction ID or null
     */
    private function extractTransactionId( array $data ) : ?string
    {
      if (isset( $data['id'] ) && !empty( $data['id'] ) ) {
        return $data['id'];
      }

      return null;
    }

    /**
     * Update order based on webhook notification
     *
     * @param array       $order          Order data
     * @param string      $paymentStatus  Payment status
     * @param string|null $transactionId Transaction ID
     *
     * @return void
     */
    private function updateOrderFromWebhook( 
        array $order,
        string $paymentStatus,
        ?string $transactionId
    ) : void
    {
        $orderId = $order['order_id'];
        $statusUpper = strtoupper( $paymentStatus );

        $comment = "HPP Webhook: Payment status: $paymentStatus";
        if ( $transactionId ) {
            $comment .= ", Transaction ID: $transactionId";
        }

        $statusConfig = $this->getOrderStatusConfig( $statusUpper );

        if ( !$statusConfig ) {
            $this->log( "HPP Webhook: Unknown status '$statusUpper' for order $orderId" );
            return;
        }

        $this->model_checkout_order->addOrderHistory(
            $orderId,
            $statusConfig['status_id'],
            $comment,
            $statusConfig['notify']
        );
    }

    /**
     * Get order status configuration based on payment status
     *
     * @param string $statusUpper Uppercase payment status
     *
     * @return array|null Status configuration or null
     */
    private function getOrderStatusConfig( string $statusUpper ) : ?array
    {
        $statusMap = [
            'CAPTURED' => ['status_id' => 2, 'notify' => true],
            'COMPLETED' => ['status_id' => 2, 'notify' => true],
            'SUCCESS' => ['status_id' => 2, 'notify' => true],
            'DECLINED' => ['status_id' => 10, 'notify' => true],
            'FAILED' => ['status_id' => 10, 'notify' => true],
            'CANCELLED' => ['status_id' => 10, 'notify' => true],
            'PENDING' => ['status_id' => 1, 'notify' => false],
        ];

        return $statusMap[ $statusUpper ] ?? null;
    }

    /**
     * Get comprehensive webhook request data
     *
     * @return array Request data
     */
    private function getWebhookRequestData() : array
    {
        $rawInput = file_get_contents( 'php://input' );
        $requestData = array();

        if ( !empty( $rawInput ) ) {
            $jsonData = json_decode( $rawInput, true );
            if ( json_last_error() === JSON_ERROR_NONE && $jsonData ) {
                $requestData = $jsonData;
            }
        }

        // Merge with OpenCart request object
        if ( !empty( $this->request->get ) ) {
            $requestData = array_merge( $this->request->get, $requestData );
        }

        // Also include POST form data as fallback
        if ( !empty( $this->request->post ) ) {
            $requestData = array_merge( $this->request->post, $requestData );
        }

        return $requestData;
    }

    /**
     * Send JSON response with proper headers
     *
     * @param array $data       Response data
     * @param int   $statusCode HTTP status code
     *
     * @return void
     */
    private function sendJsonResponse(array $data, $statusCode = 200) : void
    {
        $this->response->addHeader( 'Content-Type: application/json' );
        $this->response->addHeader( 'HTTP/1.1 ' . $statusCode );
        $this->response->setOutput( json_encode( $data ) );
    }

    /**
     * Write to log if debug is enabled
     * @param String msg Error message 
     * @return void
     */

    private function log( string $msg ) : void
    {
        $this->load->model('extension/payment/globalpayments_ucp');
        $debugEnabled = !empty($this->config->get('payment_globalpayments_ucp_debug'));
        if( $debugEnabled && !empty( $msg ) )
        {
            $this->log->write($msg);
        }
    }
}
