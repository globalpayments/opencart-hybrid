<?php

use GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\BlikPayment;
use GlobalPayments\PaymentGatewayProvider\Gateways\DiUiApms\OpenBankingPayment;
use GlobalPayments\PaymentGatewayProvider\Utils\Utils;

/**
 * GlobalPayments BLIK Webhook Controller
 * Handles webhook notifications from GlobalPayments for BLIK and Open Banking
 */
class ControllerExtensionPaymentBlikWebhook extends Controller
{

    public function __construct( $registry ) {
        // Loads the globalpayments SDK
        parent::__construct( $registry );
        $this->load->library('globalpayments');
	}
    
    /**
     * Main webhook handler - receives all GlobalPayments notifications
     */
    public function index(): void
    {
        $appKey = $this->getAppKey();
        Utils::validateSignature($appKey);
        
        // Set response headers for webhook
        $this->response->addHeader('Content-Type: application/json');
        
        // Get action parameter
        $action = $this->request->get['action'] ?? $this->request->post['action'] ?? '';
        
        // Get comprehensive request data (PrestaShop pattern)
        $request_data = $this->getWebhookRequestData();
        
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
            
            // TODO: these params look wrong (not sure we need registry in there JIMI)
            $result = BlikPayment::handle_blik_status_notification(
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
            $result = OpenBankingPayment::handle_ob_status_notification(
                $this->registry
            );
                
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
                'message' => 'Open Banking webhook processing failed: ' . $e->getMessage()
            ]));
            $this->response->addHeader('HTTP/1.1 500 Internal Server Error');
        }
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

    //TODO duplicate of that from redirect, move to utils
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
}
