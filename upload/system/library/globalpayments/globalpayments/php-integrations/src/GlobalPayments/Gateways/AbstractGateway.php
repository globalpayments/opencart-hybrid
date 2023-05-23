<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

use GlobalPayments\Api\Entities\Exceptions\ApiException;
use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\PaymentGatewayProvider\Clients\SdkClient;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Requests\RequestInterface;
use GlobalPayments\PaymentGatewayProvider\Requests\Transactions\AuthorizeRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\Transactions\CaptureRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\Transactions\ChargeRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\Transactions\RefundRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\Transactions\ReverseRequest;
use GlobalPayments\PaymentGatewayProvider\Requests\Transactions\VerifyRequest;
use GlobalPayments\PaymentGatewayProvider\Traits\ResponseMessageTrait;
use GlobalPayments\PaymentGatewayProvider\Traits\SecurePaymentFieldsTrait;

/**
 * Shared gateway method implementations.
 */
abstract class AbstractGateway implements GatewayInterface {
	use ResponseMessageTrait;
	use SecurePaymentFieldsTrait;

	/**
	 * Defines production environment.
	 */
	const ENVIRONMENT_PRODUCTION = 'production';

	/**
	 * Defines sandbox environment.
	 */
	const ENVIRONMENT_SANDBOX = 'sandbox';

	const AUTHORIZE = 'authorize';
	const CHARGE = 'charge';
	const VERIFY = 'verify';

	const CAPTURE = 'capture';
	const REFUND = 'refund';
	const REVERSE = 'reverse';
	const VOID = 'void';

	/**
	 * Gateway ID. Should be overridden by individual gateway implementations.
	 *
	 * @var string
	 */
	public $gatewayId;

	/**
	 * Gateway provider. Should be overridden by individual gateway implementations.
	 *
	 * @var string
	 */
	public $gatewayProvider;

	/**
	 * @var bool
	 */
	public $isDigitalWallet = false;

	/**
	 * Payment Gateway supports 3DS.
	 *
	 * @var bool
	 */
	public $supportsThreeDSecure = false;

	/**
	 * Payment Gateway supports Dynamic Currency Conversion.
	 *
	 * @var bool
	 */
	public $supportsDCC = false;

	/**
	 * Payment method enabled status.
	 *
	 * @var bool
	 */
	public $enabled;

	/**
	 * Payment method title shown to consumer.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Should live payments be accepted.
	 *
	 * @var bool
	 */
	public $isProduction;

	/**
	 * Action to perform on checkout.
	 *
	 * Possible actions:
	 *
	 * - `authorize` - authorize the card without auto capturing
	 * - `charge` - authorize the card with auto capturing
	 * - `verify` - verify the card without authorizing
	 *
	 * @var string
	 */
	public $paymentAction;

	/**
	 * Control of Platform's card storage (tokenization) support.
	 *
	 * @var bool
	 */
	public $allowCardSaving;

	/**
	 * Transaction descriptor to list on consumer's bank account statement.
	 *
	 * @var string
	 */
	public $txnDescriptor;

	/**
	 * Control of Platform's logging support.
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * File path to the logging directory.
	 *
	 * @var string
	 */
	public $logDirectory;

	/**
	 * Control of Platform's DCC support.
	 *
	 * @var bool
	 */
	public $allowDCC = false;

	/**
	 * Error response handlers
	 *
	 * @var HandlerInterface[]
	 */
	public $errorHandlers = array();

	/**
	 * Success response handlers
	 *
	 * @var HandlerInterface[]
	 */
	public $successHandlers = array();

	/**
	 * Gateway HTTP client
	 *
	 * @var ClientInterface
	 */
	protected $client;

	/**
	 * AbstractGateway constructor.
	 */
	public function __construct() {
		$this->client = new SdkClient();
	}

	/**
	 * Get the current gateway provider.
	 *
	 * @return string
	 * @throws ApiException
	 */
	public function getGatewayProvider() {
		if ( ! $this->gatewayProvider) {
			// this shouldn't happen outside of our internal development
			throw new ApiException('Missing gateway provider configuration');
		}

		return $this->gatewayProvider;
	}

	/**
	 * Required options for proper client-side configuration.
	 *
	 * @return Array<string,string>
	 */
	abstract public function getFrontendGatewayOptions();

	/**
	 * Required options for proper server-side configuration.
	 *
	 * @return Array<string,string>
	 */
	abstract public function getBackendGatewayOptions();

	/**
	 * Email address of the first-line support team.
	 *
	 * @return string
	 */
	abstract public function getFirstLineSupportEmail();

	/**
	 * Get credential setting value based on environment.
	 *
	 * @param string $setting
	 *
	 * @return mixed
	 */
	protected function getCredentialSetting( $setting ) {
		return $this->isProduction ? $this->{$setting} : $this->{'sandbox' . ucfirst($setting)};
	}

	/**
	 * @param RequestData $requestData
	 *
	 * @return Transaction|mixed
	 * @throws ApiException
	 */
	public function processPayment(RequestData $requestData) {
		//$this->handleResponse($request, $response);
		return $this->processRequest($requestData->requestType, $requestData);
	}

	public function processCapture(RequestData $requestData) {
		$response = $this->processRequest(CaptureRequest::class, $requestData);

		return $response;
	}

	public function processRefund(RequestData $requestData) {
		$response = $this->processRequest(RefundRequest::class, $requestData);

		return $response;
	}

	public function processReverse(RequestData $requestData) {
		$response = $this->processRequest(ReverseRequest::class, $requestData);

		return $response;
	}

	public function processVerify(RequestData $requestData) {
		$response = $this->processRequest(VerifyRequest::class, $requestData);

		return $response;
	}

	/**
	 * Process Gateway request.
	 *
	 * @param string $requestType
	 * @param RequestData $requestData
	 *
	 * @return Transaction|mixed
	 * @throws ApiException
	 */
	public function processRequest($requestType, RequestData $requestData = null) {
		$request = $this->prepareRequest($requestType, $requestData);

		$gatewayResponse = $this->client
			->setRequest($request)
			->execute();

		if (!isset($gatewayResponse->responseCode)) {
			return $gatewayResponse;
		}
		if ('SUCCESS' !== $gatewayResponse->responseCode && '00' !== $gatewayResponse->responseCode) {
			throw new \Exception($this->mapResponseCodeToFriendlyMessage($gatewayResponse->responseCode));
		}

		return $gatewayResponse;
	}

	/**
	 * Prepare Gateway request.
	 *
	 * @param string $requestType
	 * @param RequestData|null $requestData
	 *
	 * @return mixed
	 * @throws ApiException
	 */
	protected function prepareRequest($requestType, RequestData $requestData = null) {
		if (!class_exists($requestType)) {
			throw new ApiException('Request undefined. Unable to perform request.');
		}

		return new $requestType(
			$this->getBackendGatewayOptions(),
			$requestData
		);
	}

	/**
	 * Reacts to the transaction response.
	 *
	 * @param RequestInterface $request
	 * @param Transaction|TransactionSummary|string $response
	 * @return bool
	 * @throws ApiException
	 */
	protected function handleResponse(RequestInterface $request, $response) {
		if (!($response instanceof Transaction)) {
			throw new ApiException("Unexpected transaction response");
		}

		/**
		 * @var HandlerInterface[]
		 */
		$handlers = $this->successHandlers;

		if ('00' !== $response->responseCode || 'SUCCESS' !== $response->responseCode) {
			$handlers = $this->errorHandlers;
		}

		foreach ($handlers as $handler) {
			/**
			 * Current handler
			 *
			 * @var HandlerInterface $h
			 */
			$h = new $handler($request, $response);
			$h->handle();
		}

		return true;
	}

	/**
	 * Get request type based on payment action.
	 *
	 * @return string
	 */
	public static function getRequestType($paymentAction) {
		switch ($paymentAction) {
			case self::AUTHORIZE:
				$requestType = AuthorizeRequest::class;
				break;
			case self::CHARGE:
				$requestType = ChargeRequest::class;
				break;
			default:
				$requestType = '';
		}

		return $requestType;
	}

	/**
	 * Internal logger.
	 *
	 * @param $message
	 */
	public function log($message) {
		if ($this->debug) {
			$this->logger = new Logger($this->logDirectory);
			$this->logger->info($message);
		}
	}
}
