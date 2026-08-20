<?php
class ControllerExtensionPaymentGlobalpaymentsAccount extends Controller {

	public function injectCreditCardsHTML(&$route, &$data, &$output): void
	{
		$credit_cards = [];

		$files = glob(DIR_APPLICATION . 'controller/extension/credit_card/*.php');
		if (!$files) {
			return;
		}

		foreach ($files as $file) {
			$code = basename($file, '.php');
			if ($this->config->get('payment_' . $code . '_status') && $this->config->get('payment_' . $code . '_card')) {
				if (!$this->customer->isLogged()) {
					continue;
				}
				$this->load->model('extension/payment/' . $code);
				$model = 'model_extension_payment_' . $code;
				$cards = method_exists($this->$model, 'getCustomerCards')
					? $this->$model->getCustomerCards($this->customer->getId())
					: [];
				if (empty($cards)) {
					continue;
				}
				$this->load->language('extension/credit_card/' . $code, 'extension');
				$credit_cards[] = [
					'name' => $this->language->get('extension')->get('heading_title'),
					'href' => $this->url->link('extension/credit_card/' . $code, '', true)
				];
			}
		}

		if (empty($credit_cards)) {
			return;
		}

		$this->load->language('account/account');
		$heading = $this->language->get('text_credit_card') ?: 'Manage Stored Credit Cards';

		$html = '<h2>' . htmlspecialchars($heading) . '</h2><ul class="list-unstyled">';
		foreach ($credit_cards as $card) {
			$html .= '<li><a href="' . $card['href'] . '">' . htmlspecialchars($card['name']) . '</a></li>';
		}
		$html .= '</ul>';

		// Anchor to the content div first — header also contains route=account/wishlist in its dropdown
		$content_start = strpos($output, 'id="account-account"');
		if ($content_start === false) {
			$content_start = 0;
		}

		$wishlist_pos = strpos($output, 'route=account/wishlist', $content_start);
		if ($wishlist_pos !== false) {
			$ul_end = strpos($output, '</ul>', $wishlist_pos);
			if ($ul_end !== false) {
				$output = substr($output, 0, $ul_end + 5) . $html . substr($output, $ul_end + 5);
			}
		}
	}
}
