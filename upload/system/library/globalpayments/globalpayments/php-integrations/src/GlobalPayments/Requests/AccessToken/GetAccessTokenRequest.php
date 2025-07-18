<?php

namespace GlobalPayments\PaymentGatewayProvider\Requests\AccessToken;

use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Requests\AbstractRequest;

class GetAccessTokenRequest extends AbstractRequest {
	public function __construct(array $config = array(), RequestData $requestData = null) {
		parent::__construct($config, $requestData);

		if(!empty($_POST) && $_POST['app_id'] !== null && $_POST['app_key'] !== null ) {
			$this->config['permissions'] = [];
		} else {
			$this->config['permissions'] = [
				'PMT_POST_Create_Single',
			];
		}
		// @TODO: Currently we request an access token every time we load hosted fields.
		// @TODO: Should we set access token expiration?
		// @TODO: How we should handle an expired access token?
		// @TODO: Should we cache the access token?
		//$this->config['secondsToExpire'] = 60;
		//$this->config['intervalToExpire'] = '5_MINUTES';
	}

	public function execute() {
		return ServicesContainer::instance()->getClient('default')->getAccessToken();
	}
}
