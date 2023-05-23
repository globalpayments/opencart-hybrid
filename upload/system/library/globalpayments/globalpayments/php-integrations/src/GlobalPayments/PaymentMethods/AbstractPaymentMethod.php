<?php

namespace GlobalPayments\PaymentGatewayProvider\PaymentMethods;

use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayInterface;

abstract class AbstractPaymentMethod implements PaymentMethodInterface {
	/**
	 * Payment Method ID. Should be overridden by individual implementations.
	 *
	 * @var string
	 */
	public $paymentMethodId;

	/**
	 * Gateway.
	 *
	 * @var GlobalPayments\PaymentGatewayProvider\Gateways\GatewayInterface
	 */
	public $gateway;
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
	 * Payment method default title.
	 *
	 * @var string
	 */
	public $defaultTitle;

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

	public function __construct(GatewayInterface $gateway) {
		$this->gateway = $gateway;
	}

	/**
	 * Required options for proper client-side configuration.
	 *
	 * @return array
	 */
	abstract public function getFrontendPaymentMethodOptions();

	/**
	 * Email address of the first-line support team.
	 *
	 * @return string
	 */
	public function getFirstLineSupportEmail() {
		return $this->gateway->getFirstLineSupportEmail();
	}

	/**
	 * The configuration for the params object.
	 *
	 * @param bool $jsonEncode
	 *
	 * @return array|false|string
	 * @throws \GlobalPayments\Api\Entities\Exceptions\ApiException
	 */
	public function paymentFieldsParams($jsonEncode=true) {
		$params = array(
			'id' => $this->paymentMethodId,
			'paymentMethodOptions' => $this->getFrontendPaymentMethodOptions(),
		);

		return $jsonEncode ? json_encode($params) : $params;
	}
}
