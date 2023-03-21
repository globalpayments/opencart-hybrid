<?php

namespace GlobalPayments\PaymentGatewayProvider\Gateways;

use GlobalPayments\PaymentGatewayProvider\Data\RequestData;

interface GatewayInterface {
	public function getGatewayProvider();

	public function getFrontendGatewayOptions();

	public function getBackendGatewayOptions();

	public function getFirstLineSupportEmail();

	public function processRequest($requestType, RequestData $requestData);

	public function processPayment(RequestData $requestData);

	public function processCapture(RequestData $requestData);

	public function processRefund(RequestData $requestData);

	public function processReverse(RequestData $requestData);

	public function processVerify(RequestData $requestData);
}
