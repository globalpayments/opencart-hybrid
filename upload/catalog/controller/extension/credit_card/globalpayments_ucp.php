<?php

use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;

class ControllerExtensionCreditCardGlobalPaymentsUcp extends Controller {
	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->load->library('globalpayments');
		$this->globalpayments->setGateway(GatewayId::GP_API);
	}

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('extension/credit_card/globalpayments_ucp');

		$this->load->model('extension/payment/globalpayments_ucp');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_card'),
			'href' => $this->url->link('extension/credit_card/globalpayments_ucp', '', true)
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		if ($this->config->get('payment_globalpayments_ucp_card')) {
			$data['cards'] = $this->model_extension_payment_globalpayments_ucp->getCards($this->customer->getId(), $this->globalpayments->gateway->gatewayId);
			$data['delete'] = $this->url->link('extension/credit_card/globalpayments_ucp/deletecard', 'card_id=', true);
			$data['default'] = $this->url->link('extension/credit_card/globalpayments_ucp/defaultcard', 'card_id=', true);

			if (isset($this->request->get['page'])) {
				$page = (int)$this->request->get['page'];
			} else {
				$page = 1;
			}

			$cards_total = count($data['cards']);

			$pagination = new Pagination();
			$pagination->total = $cards_total;
			$pagination->page = $page;
			$pagination->limit = 10;
			$pagination->url = $this->url->link('extension/credit_card/globalpayments_ucp', 'page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($cards_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($cards_total - 10)) ? $cards_total : ((($page - 1) * 10) + 10), $cards_total, ceil($cards_total / 10));
		} else {
			$data['cards'] = false;
			$data['pagination'] = false;
			$data['results'] = false;
		}

		$data['back'] = $this->url->link('account/account', '', true);
		$data['add'] = $this->url->link('extension/credit_card/globalpayments_ucp/add', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/credit_card/globalpayments_ucp_list', $data));
	}

	public function add() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('extension/payment/globalpayments_ucp');
		$this->load->language('extension/credit_card/globalpayments_ucp');

		$this->load->model('extension/payment/globalpayments_ucp');

		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['action'] = $this->url->link('extension/credit_card/globalpayments_ucp/addCard', '', true);
		$data['gateway'] = $this->globalpayments->gateway;

		$data['environment_indicator']                       = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields']                       = $this->globalpayments->gateway->getCreditCardFormatFields();
		$data['globalpayments_secure_payment_fields_params'] = $this->globalpayments->gateway->securePaymentFieldsParams();

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_card'),
			'href' => $this->url->link('extension/credit_card/globalpayments_ucp', '', true)
		);


		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		if ($this->config->get('payment_globalpayments_ucp_card')) {
			$data['cards'] = $this->model_extension_payment_globalpayments_ucp->getCards($this->customer->getId(), $this->globalpayments->gateway->gatewayId);
			$data['delete'] = $this->url->link('extension/credit_card/globalpayments_ucp/delete', 'card_id=', true);

			if (isset($this->request->get['page'])) {
				$page = (int)$this->request->get['page'];
			} else {
				$page = 1;
			}

			$cards_total = count($data['cards']);

			$pagination = new Pagination();
			$pagination->total = $cards_total;
			$pagination->page = $page;
			$pagination->limit = 10;
			$pagination->url = $this->url->link('extension/credit_card/globalpayments_ucp', 'page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($cards_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($cards_total - 10)) ? $cards_total : ((($page - 1) * 10) + 10), $cards_total, ceil($cards_total / 10));
		} else {
			$data['cards'] = false;
			$data['pagination'] = false;
			$data['results'] = false;
		}

		$data['back'] = $this->url->link('extension/credit_card/globalpayments_ucp', '', true);
		$data['add'] = $this->url->link('extension/credit_card/globalpayments_ucp/addCard', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/credit_card/globalpayments_ucp_add', $data));
	}

	public function addCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('extension/credit_card/globalpayments_ucp');
		try {
			$this->setOrder();
			if (empty($this->request->post[$this->globalpayments->gateway->gatewayId])) {
				throw new \Exception($this->language->get('error_add_card'));
			}

			$postRequestData = (object)$this->request->post[$this->globalpayments->gateway->gatewayId];

			$requestData = new RequestData();
			$requestData = RequestData::setDataObject($requestData, $postRequestData);
			$requestData->paymentTokenResponse = !empty($postRequestData->paymentTokenResponse) ? htmlspecialchars_decode($postRequestData->paymentTokenResponse) : null;
			$requestData->order = $this->order;

			$gatewayResponse = $this->globalpayments->gateway->processVerify($requestData);

			//succesfull response, store the saved card
			$this->load->model('extension/payment/globalpayments_ucp');

			$is_default = 0;
			$cards = $this->model_extension_payment_globalpayments_ucp->getCards($this->customer->getId(), GatewayId::GP_API);
			if (empty($cards)) {
				$is_default = 1;
			}

			$payment_token = json_decode($requestData->paymentTokenResponse);
			$this->model_extension_payment_globalpayments_ucp->addCard(
				$this->globalpayments->gateway->gatewayId,
				$this->customer->getId(),
				$gatewayResponse->token,
				strtoupper($payment_token->details->cardType),
				$payment_token->details->cardLast4,
				$payment_token->details->expiryYear,
				$payment_token->details->expiryMonth,
				$is_default
			);

			$this->session->data['success'] = $this->language->get('text_success_add_card');
		} catch (\Exception $e) {
			$this->session->data['error_warning'] = $e->getMessage();
		}

		$this->response->redirect($this->url->link('extension/credit_card/globalpayments_ucp', '', true));
	}

	public function deleteCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('extension/credit_card/globalpayments_ucp');

		if (!is_numeric($this->request->get['card_id'])) {
			$this->session->data['error_warning'] = $this->language->get('error_remove_card');
			$this->response->redirect($this->url->link('extension/credit_card/globalpayments_ucp', '', true));
		}

		$this->load->model('extension/payment/globalpayments_ucp');

		$result = $this->model_extension_payment_globalpayments_ucp->deleteCard($this->customer->getId(), $this->request->get['card_id']);
		if ($result) {
			$this->session->data['success'] = $this->language->get('text_success_remove_card');
		} else {
			$this->session->data['error_warning'] = $this->language->get('error_remove_card');
		}

		$this->response->redirect($this->url->link('extension/credit_card/globalpayments_ucp', '', true));
	}

	public function defaultCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('extension/credit_card/globalpayments_ucp');

		if (!is_numeric($this->request->get['card_id'])) {
			$this->session->data['error_warning'] = $this->language->get('error_default_card');
			$this->response->redirect($this->url->link('extension/credit_card/globalpayments_ucp', '', true));
		}

		$this->load->model('extension/payment/globalpayments_ucp');

		$result = $this->model_extension_payment_globalpayments_ucp->defaultCard($this->customer->getId(), $this->request->get['card_id']);
		if ($result) {
			$this->session->data['success'] = $this->language->get('text_success_default_card');
		} else {
			$this->session->data['error_warning'] = $this->language->get('error_default_card');
		}

		$this->response->redirect($this->url->link('extension/credit_card/globalpayments_ucp', '', true));
	}

	private function setOrder() {
		if (empty($this->session->data['currency'])) {
			throw new \Exception('Something went wrong while storing your card. Please try again.');
		}

		$order = new \GlobalPayments\PaymentGatewayProvider\Data\OrderData();
		$order->currency = $this->session->data['currency'];

		$this->order = $order;
	}
}
