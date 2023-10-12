<?php

namespace GlobalPayments\PaymentGatewayProvider\Clients;

use GlobalPayments\Api\Entities\Enums\GatewayProvider;
use GlobalPayments\Api\ServiceConfigs\AcceptorConfig;
use GlobalPayments\Api\ServiceConfigs\Gateways\GeniusConfig;
use GlobalPayments\Api\ServiceConfigs\Gateways\GpApiConfig;
use GlobalPayments\Api\ServiceConfigs\Gateways\PorticoConfig;
use GlobalPayments\Api\ServiceConfigs\Gateways\TransitConfig;
use GlobalPayments\Api\ServiceConfigs\Gateways\TransactionApiConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\Api\Utils\Logging\SampleRequestLogger;
use GlobalPayments\PaymentGatewayProvider\Requests\RequestInterface;
use Psr\Log\LogLevel;

class SdkClient implements ClientInterface {
	/**
	 * @param RequestInterface $request
	 *
	 * @return $this|ClientInterface
	 */
	public function setRequest(RequestInterface $request) {
		$this->request = $request;

		return $this;
	}

	/**
	 * @return \GlobalPayments\Api\Entities\Transaction|mixed
	 * @throws \Exception
	 */
	public function execute() {
		$this->configureSdk();

		return $this->request->execute();
	}

	/**
	 * @throws \Exception
	 */
	protected function configureSdk() {
		$gatewayConfig = null;

		switch ($this->request->config['gatewayProvider']) {
			case GatewayProvider::GP_API:
				$gatewayConfig = new GpApiConfig();
				break;
			case GatewayProvider::PORTICO:
				$gatewayConfig = new PorticoConfig();
				break;
			case GatewayProvider::TRANSIT:
				$gatewayConfig = new TransitConfig();
				// @phpstan-ignore-next-line
				$gatewayConfig->acceptorConfig = new AcceptorConfig(); // defaults should work here
				break;
			case GatewayProvider::GENIUS:
				$gatewayConfig = new GeniusConfig();
				break;
            		case GatewayProvider::TRANSACTION_API:
                		$gatewayConfig = new TransactionApiConfig();
               			break;
			default:
				break;
		}

		if (null === $gatewayConfig) {
			return;
		}

		$config = $this->setObjectData(
			$gatewayConfig,
			$this->request->config
		);

		if ( ! empty($this->request->config['debug'])) {
			if ( empty($this->request->config['logDirectory'])) {
				throw new \Exception('Unable to log request. Log directory not set.');
			}
			$config->requestLogger = new SampleRequestLogger(new Logger(
				$this->request->config['logDirectory']
			));
		}

		ServicesContainer::configureService($config);
	}

	/**
	 * @param object $obj
	 * @param Array<string,mixed> $data
	 *
	 * @return object
	 */
	protected function setObjectData($obj, array $data) {
		foreach ($data as $key => $value) {
			if (property_exists($obj, $key)) {
				$obj->{$key} = $value;
			}
		}

		return $obj;
	}
}
