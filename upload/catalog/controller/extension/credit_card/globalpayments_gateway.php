<?php

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;
use GlobalPayments\PaymentGatewayProvider\Data\RequestData;
use GlobalPayments\PaymentGatewayProvider\Gateways\GatewayId;

class ControllerExtensionCreditCardGlobalPaymentsBase extends Controller {
	private $gatewayName;
	private $modelName;
	private $extensionCreditCard;
	private $extensionPayment;
	private $allowCardSavingConfig;

	public function __construct($registry, $gatewayName) {
		parent::__construct($registry);
		$this->load->library('globalpayments');
		$this->gatewayName = $gatewayName;
		$this->modelName = 'model_extension_payment_globalpayments_' . $this->gatewayName;
		$this->extensionCreditCard = 'extension/credit_card/globalpayments_' . $this->gatewayName;
		$this->extensionPayment = 'extension/payment/globalpayments_' . $this->gatewayName;
		$this->allowCardSavingConfig = 'payment_globalpayments_' . $this->gatewayName . '_card';
	}

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language($this->extensionCreditCard);

		$this->load->model($this->extensionPayment);

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
			'href' => $this->url->link($this->extensionCreditCard, '', true)
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

		if ($this->config->get($this->allowCardSavingConfig)) {
			$data['cards'] = $this->{$this->modelName}->getCards($this->customer->getId(), $this->globalpayments->gateway->gatewayId);
			$data['delete'] = $this->url->link($this->extensionCreditCard . '/deletecard', 'card_id=', true);
			$data['default'] = $this->url->link($this->extensionCreditCard . '/defaultcard', 'card_id=', true);

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
			$pagination->url = $this->url->link($this->extensionCreditCard, 'page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($cards_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($cards_total - 10)) ? $cards_total : ((($page - 1) * 10) + 10), $cards_total, ceil($cards_total / 10));
		} else {
			$data['cards'] = false;
			$data['pagination'] = false;
			$data['results'] = false;
		}

		$data['back'] = $this->url->link('account/account', '', true);
		$data['add'] = $this->url->link($this->extensionCreditCard . '/add', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/credit_card/globalpayments_card_list', $data));
	}

	public function add() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language($this->extensionPayment);
		$this->load->language($this->extensionCreditCard);

		$this->load->model($this->extensionPayment);

		$this->globalpayments->setSecurePaymentFieldsTranslations();
		$this->globalpayments->setSecurePaymentFieldsStyles();

		$data['action'] = $this->url->link($this->extensionCreditCard . '/addCard', '', true);
		$data['gateway'] = $this->globalpayments->gateway;

		$data['environment_indicator'] = $this->globalpayments->gateway->getEnvironmentIndicator('alert alert-danger');
		$data['secure_payment_fields'] = $this->globalpayments->gateway->getCreditCardFormatFields();
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
			'href' => $this->url->link($this->extensionCreditCard, '', true)
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

		if ($this->config->get($this->allowCardSavingConfig)) {
			$data['cards'] = $this->{$this->modelName}->getCards($this->customer->getId(), $this->globalpayments->gateway->gatewayId);
			$data['delete'] = $this->url->link($this->extensionCreditCard . '/delete', 'card_id=', true);

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
			$pagination->url = $this->url->link($this->extensionCreditCard, 'page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($cards_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($cards_total - 10)) ? $cards_total : ((($page - 1) * 10) + 10), $cards_total, ceil($cards_total / 10));
		} else {
			$data['cards'] = false;
			$data['pagination'] = false;
			$data['results'] = false;
		}

		$data['back'] = $this->url->link($this->extensionCreditCard, '', true);
		$data['add'] = $this->url->link($this->extensionCreditCard . '/addCard', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$data['globalpayments_add_form'] = $this->load->view('extension/credit_card/globalpayments_add_form', $data);
		$this->response->setOutput($this->load->view($this->extensionCreditCard . '_add', $data));
	}

	public function addCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language($this->extensionCreditCard);
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
			$this->load->model($this->extensionPayment);

			$is_default = 0;
			$cards = $this->{$this->modelName}->getCards($this->customer->getId(), GatewayId::GP_API);
			if (empty($cards)) {
				$is_default = 1;
			}

			$payment_token = json_decode($requestData->paymentTokenResponse);
			$this->{$this->modelName}->addCard(
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

		$this->response->redirect($this->url->link($this->extensionCreditCard, '', true));
	}

	public function deleteCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language($this->extensionCreditCard);

		if (!is_numeric($this->request->get['card_id'])) {
			$this->session->data['error_warning'] = $this->language->get('error_remove_card');
			$this->response->redirect($this->url->link($this->extensionCreditCard, '', true));
		}

		$this->load->model($this->extensionPayment);

		$result = $this->{$this->modelName}->deleteCard($this->customer->getId(), $this->request->get['card_id']);
		if ($result) {
			$this->session->data['success'] = $this->language->get('text_success_remove_card');
		} else {
			$this->session->data['error_warning'] = $this->language->get('error_remove_card');
		}

		$this->response->redirect($this->url->link($this->extensionCreditCard, '', true));
	}

	public function defaultCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language($this->extensionCreditCard);

		if (!is_numeric($this->request->get['card_id'])) {
			$this->session->data['error_warning'] = $this->language->get('error_default_card');
			$this->response->redirect($this->url->link($this->extensionCreditCard, '', true));
		}

		$this->load->model($this->extensionPayment);

		$result = $this->{$this->modelName}->defaultCard($this->customer->getId(), $this->request->get['card_id']);
		if ($result) {
			$this->session->data['success'] = $this->language->get('text_success_default_card');
		} else {
			$this->session->data['error_warning'] = $this->language->get('error_default_card');
		}

		$this->response->redirect($this->url->link($this->extensionCreditCard, '', true));
	}

	private function setOrder() {
		if (empty($this->session->data['currency'])) {
			throw new \Exception('Something went wrong while storing your card. Please try again.');
		}

		$order = new OrderData();
		$order->currency = $this->session->data['currency'];

		$this->order = $order;
	}
}
