<?php
/**
 * GlobalPayments BLIK Redirect Controller for OpenCart
 * Handles redirects from payment gateways back to the store
 */

class ControllerExtensionPaymentBlikRedirect extends Controller 
{
    /**
     * Main index method - handles redirect requests
     */
    public function index(): void
    {
        // Get action and order_id parameters
        $action = $this->request->get['action'] ?? '';
        $order_id = $this->request->get['order_id'] ?? '';
        
        // Log the incoming request for debugging
        $this->log->write('BLIK Redirect: action=' . $action . ', order_id=' . $order_id);
        try {
            switch ($action) {
                case 'blik_redirect_handler':
                    $this->handleBlikRedirect($order_id);
                    break;
                case 'ob_redirect_handler':
                    $this->handleObRedirect($order_id);
                    break;
                default:
                    $this->log->write('BLIK Redirect: Unknown action: ' . $action);
                    $this->redirectToCart();
                    break;
            }
        } catch (Exception $e) {
            $this->log->write('BLIK Redirect Controller Error: ' . $e->getMessage());
            $this->redirectToCart();
        }
    }
    
    /**
     * Handle BLIK payment redirects
     * 
     * @param string $order_id
     */
    private function handleBlikRedirect(string $order_id): void
    {
        if (!$order_id) {
            $this->log->write('BLIK Redirect: Missing order_id');
            $this->redirectToCart();
            return;
        }
        
        try {
            // Load order model to validate order exists
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);
         
            $this->log->write(json_encode($order));
            if (!$order) {
                $this->log->write('BLIK Redirect: Order not found with ID: ' . $order_id);
                $this->redirectToCart();
                return;
            }
            
            // Get redirect parameters from GlobalPayments
            $status = $this->request->get['status'] ?? '';
            $transaction_id = $this->request->get['id'] ?? '';
            $reference = $this->request->get['reference'] ?? '';
          
            // Process the redirect based on status
            $this->processBlikRedirect($order, $status, $transaction_id, $reference);
            
        } catch (Exception $e) {
            $this->log->write('BLIK Redirect Error: ' . $e->getMessage());
            $this->redirectToCart();
        }
    }
    
    /**
     * Handle Open Banking payment redirects
     * 
     * @param string $order_id
     */
    private function handleObRedirect(string $order_id): void
    {
        if (!$order_id) {
            $this->log->write('Open Banking Redirect: Missing order_id');
            $this->redirectToCart();
            return;
        }
        
        try {
            // Load order model to validate order exists
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);
            
            if (!$order) {
                $this->log->write('Open Banking Redirect: Order not found with ID: ' . $order_id);
                $this->redirectToCart();
                return;
            }
            
            // Get redirect parameters
            $status = $this->request->get['status'] ?? '';
            $transaction_id = $this->request->get['id'] ?? '';
            $reference = $this->request->get['reference'] ?? '';
            
            // Process the Open Banking redirect
            $this->processObRedirect($order, $status, $transaction_id, $reference);
            
        } catch (Exception $e) {
            $this->log->write('Open Banking Redirect Error: ' . $e->getMessage());
            $this->redirectToCart();
        }
    }
    
    /**
     * Process BLIK payment redirect response
     * 
     * @param array $order
     * @param string $status
     * @param string $transaction_id
     * @param string $reference
     */
    private function processBlikRedirect(array $order, string $status, string $transaction_id, string $reference): void
    {
        $order_id = $order['order_id'];
        
        $this->log->write("Processing BLIK redirect for order: $order_id, status: $status, txn: $transaction_id");
        // Debug: Log all GET parameters (remove in production)
        if (defined('DEBUG_PAYMENTS') && DEBUG_PAYMENTS) {
            $this->log->write(json_encode($_GET));
        }
        
        switch (strtoupper($status)) {
            case 'SUCCESS':
            case 'COMPLETED':
            case 'CAPTURED':
                // Payment successful - redirect to success page
                $this->updateOrderStatus($order_id, $status, $transaction_id, 'success');
                $this->redirectToSuccess($order_id);
                break;
                
            case 'PENDING':
                // Payment pending - redirect to pending page or success with message
                $this->updateOrderStatus($order_id, $status, $transaction_id, 'pending');
                $this->redirectToSuccess($order_id, 'Your BLIK payment is being processed.');
                break;
                
            case 'FAILED':
            case 'DECLINED':
            case 'CANCELLED':
                // Payment failed - redirect to failure page
                $this->updateOrderStatus($order_id, $status, $transaction_id, 'failed');
                //$this->redirectToFailure($order_id, 'BLIK payment was ' . strtolower($status));
                $this->redirectToCart();
                break;
                
            default:
                // Unknown status - redirect to cart
                $this->log->write("BLIK Redirect: Unknown status '$status' for order $order_id");
                $this->redirectToCart();
                break;
        }
    }
    
    /**
     * Process Open Banking payment redirect response
     * 
     * @param array $order
     * @param string $status
     * @param string $transaction_id
     * @param string $reference
     */
    private function processObRedirect(array $order, string $status, string $transaction_id, string $reference): void
    {
        $order_id = $order['order_id'];
        
        $this->log->write("Processing Open Banking redirect for order: $order_id, status: $status, txn: $transaction_id");
        
        switch (strtoupper($status)) {
            case 'SUCCESS':
            case 'COMPLETED':
            case 'CAPTURED':
                // Payment successful
                $this->updateOrderStatus($order_id, $status, $transaction_id, 'success');
                $this->redirectToSuccess($order_id);
                break;
                
            case 'PENDING':
                // Payment pending
                $this->updateOrderStatus($order_id, $status, $transaction_id, 'pending');
                $this->redirectToSuccess($order_id, 'Your Open Banking payment is being processed.');
                break;
                
            case 'FAILED':
            case 'DECLINED':
            case 'CANCELLED':
                // Payment failed
                $this->updateOrderStatus($order_id, $status, $transaction_id, 'failed');
                $this->redirectToFailure($order_id, 'Open Banking payment was ' . strtolower($status));
                break;
                
            default:
                // Unknown status
                $this->log->write("Open Banking Redirect: Unknown status '$status' for order $order_id");
                $this->redirectToCart();
                break;
        }
    }
    
    /**
     * Update order status based on payment result
     * 
     * @param string $order_id
     * @param string $payment_status
     * @param string $transaction_id
     * @param string $result_type
     */
    private function updateOrderStatus(string $order_id, string $payment_status, string $transaction_id, string $result_type): void
    {
        $this->load->model('checkout/order');
        
        $comment = sprintf(
            'Payment redirect received - Status: %s, Transaction ID: %s',
            $payment_status,
            $transaction_id ?: 'N/A'
        );
       
        switch ($result_type) {
            case 'success':
                $order_status_id = 2; // Processing
                $notify = true;
                break;
                
            case 'pending':
                $order_status_id = 1; // Pending
                $notify = false;
                break;
                
            case 'failed':
                $order_status_id = 7; // Canceled
                $notify = true;
                break;
                
            default:
                $order_status_id = 1; // Pending
                $notify = false;
                break;
        }
       
        // Update the order status using OpenCart's standard method
        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, $notify);
        
        $this->log->write("Updated order $order_id status to $order_status_id ($result_type): $comment");
    }
    
    /**
     * Redirect to success page
     * 
     * @param string $order_id
     * @param string|null $message Optional success message
     */
    private function redirectToSuccess(string $order_id, ?string $message = null): void
    {
        $url = $this->url->link('checkout/success', 'order_id=' . $order_id, true);
        
        if ($message) {
            $this->session->data['success'] = $message;
        }
        $this->cart->clear();
        $this->log->write("Redirecting to success page: $url");
        $this->response->redirect($url);
    }
    
    /**
     * Redirect to failure page
     * 
     * @param string $order_id
     * @param string $message Error message
     */
    private function redirectToFailure(string $order_id, string $message = 'Payment failed'): void
    {
        // Set error message in session
        $this->session->data['error'] = $message;
        
        // Redirect to checkout failure page
        $url = $this->url->link('checkout/cart', '', true);
        
        $this->log->write("Redirecting to failure page: $url - Message: $message");
        $this->response->redirect($url);
    }
    
    /**
     * Redirect to cart page
     */
    private function redirectToCart(): void
    {
        $url = $this->url->link('checkout/cart', '', true);
        
        $this->log->write("Redirecting to cart page: $url");
        $this->response->redirect($url);
    }
    
    /**
     * Direct redirect handler for backward compatibility
     */
    public function blikHandler(): void
    {
        $order_id = $this->request->get['order_id'] ?? '';
        $this->handleBlikRedirect($order_id);
    }
    
    /**
     * Direct Open Banking redirect handler for backward compatibility
     */
    public function obHandler(): void
    {
        $order_id = $this->request->get['order_id'] ?? '';
        $this->handleObRedirect($order_id);
    }
}
