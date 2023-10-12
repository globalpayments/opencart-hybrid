<?php

namespace GlobalPayments\PaymentGatewayProvider\Traits;

use GlobalPayments\PaymentGatewayProvider\Data\OrderData;

trait SecurePaymentFieldsTrait {
	/**
	 * @var array
	 */
	protected $securePaymentFieldsStyles;

	/**
	 * Override for Translations.
	 *
	 * @var string
	 */
	public $textSandboxWarning = 'This page is currently in sandbox/test mode. Do not use real/active card numbers.';
	public $textCardNumberLabel = 'Credit Card Number';
	public $textCardExpirationLabel = 'Credit Card Expiration Date';
	public $textCardCvvLabel = 'Credit Card Security Code';
	public $textCardHolderLabel = 'Card Holder Name';

	public $errorCardExpiration = 'Please enter a valid Credit Card Expiration Date';
	public $errorCardNumber = 'Please enter a valid Credit Card Number';
	public $errorCardCvv = 'Please enter a valid Credit Card Security Code';
	public $errorCardHolder = 'Please enter a valid Card Holder Name';

	//	abstract public function setSecurePaymentFieldsTranslations();

	public function setSecurePaymentFieldsStyles(array $securePaymentFieldsStyles) {
		$this->securePaymentFieldsStyles = $securePaymentFieldsStyles;
	}

	public function getEnvironmentIndicator($class = null, $style = null) {
		if ($this->isProduction) {
			return;
		}

		if (empty($class)) {
			$class = 'globalpayments-sandbox-warning';
		} else {
			$class = 'globalpayments-sandbox-warning ' . $class;
		}

		if (empty($style)) {
			$style = 'margin-bottom: 10px';
		}

		return <<<SW
<div class="$class" style="$style">
    $this->textSandboxWarning
</div>
SW;
	}

	/**
	 * The HTML template for all secure payment fields.
	 *
	 * @return array
	 */
	public function getCreditCardFormatFields() {
		$fieldFormat = $this->securePaymentFieldHtmlFormat();
		$fields      = $this->securePaymentFieldsConfiguration();
		$result      = array();

		foreach ($fields as $key => $field) {
			$result[$key] = sprintf(
				$fieldFormat,
				$this->gatewayId,
				$field['class'],
				$field['label'],
				$field['messages']['validation']
			);
		}

		return $result;
	}

	/**
	 * The HTML template string for a secure payment field.
	 *
	 * Format directives:
	 *
	 * 1) Gateway ID
	 * 2) Field CSS class
	 * 3) Field label
	 * 4) Field validation message
	 *
	 * @return string
	 */
	public function securePaymentFieldHtmlFormat() {
		return (
		'<div class="globalpayments %1$s %2$s">
            <label for="%1$s-%2$s">%3$s&nbsp;<span class="required">*</span></label>
            <div id="%1$s-%2$s"></div>
            <ul class="globalpayments-validation-error" style="display: none;">
                <li>%4$s</li>
            </ul>
        </div>'
		);
	}

	/**
	 * Configuration for the secure payment fields. Used on server- and
	 * client-side portions of the integration.
	 *
	 * @return mixed[]
	 */
	public function securePaymentFieldsConfiguration() {
		return array(
			'card-number-field' => array(
				'class'       => 'card-number',
				'label'       => $this->textCardNumberLabel,
				'placeholder' => '•••• •••• •••• ••••',
				'messages'    => array(
					'validation' => $this->errorCardNumber,
				),
			),
			'card-expiry-field' => array(
				'class'       => 'card-expiration',
				'label'       => $this->textCardExpirationLabel,
				'placeholder' => 'MM / YYYY',
				'messages'    => array(
					'validation' => $this->errorCardExpiration,
				),
			),
			'card-cvv-field'    => array(
				'class'       => 'card-cvv',
				'label'       => $this->textCardCvvLabel,
				'placeholder' => '•••',
				'messages'    => array(
					'validation' => $this->errorCardCvv,
				),
			),
			'card-holder-field'    => array(
				'class'       => 'card-holder',
				'label'       => $this->textCardHolderLabel,
				'placeholder' => 'Jane Smith',
				'messages'    => array(
					'validation' => $this->errorCardHolder,
				),
			),
		);
	}

	/**
	 * CSS styles for secure payment fields.
	 *
	 * @return mixed|void
	 */
	public function securePaymentFieldsStyles() {
		if (isset($this->securePaymentFieldsStyles)) {
			return json_encode($this->securePaymentFieldsStyles);
		}

		$imageBase = $this->securePaymentFieldsAssetBaseUrl() . '/images';

		$securePaymentFieldsStyles = array(
			'html' => array(
				'font-size'                => '100%',
				'-webkit-text-size-adjust' => '100%',
			),
			'body' => array(),
			'#secure-payment-field-wrapper' => array(
				'position' => 'relative',
			),
			'#secure-payment-field' => array(
				'background-color' => '#fff',
				'border'           => '1px solid #ccc',
				'border-radius'    => '4px',
				'display'          => 'block',
				'font-size'        => '14px',
				'height'           => '35px',
				'padding'          => '6px 12px',
				'width'            => '100%',
			),
			'#secure-payment-field:focus' => array(
				'border'     => '1px solid lightblue',
				'box-shadow' => '0 1px 3px 0 #cecece',
				'outline'    => 'none',
			),
			'button#secure-payment-field.submit' => array(
				'border'             => '0',
				'border-radius'      => '0',
				'background'         => 'none',
				'background-color'   => '#333333',
				'border-color'       => '#333333',
				'color'              => '#fff',
				'cursor'             => 'pointer',
				'padding'            => '.6180469716em 1.41575em',
				'text-decoration'    => 'none',
				'font-weight'        => '600',
				'text-shadow'        => 'none',
				'display'            => 'inline-block',
				'-webkit-appearance' => 'none',
				'height'             => 'initial',
				'width'              => '100%',
				'flex'               => 'auto',
				'position'           => 'static',
				'margin'             => '0',

				'white-space'        => 'pre-wrap',
				'margin-bottom'      => '0',
				'float'              => 'none',

				'font'               => '600 1.41575em/1.618 Source Sans Pro,HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica,Arial,Lucida Grande,sans-serif !important',
			),
			'button#secure-payment-field.submit:disabled' => array(
				'background-color' => '#808080',
				'border-color'     => '#808080',
				'cursor'           => 'not-allowed',
			),
			'#secure-payment-field[type=button]:focus' => array(
				'color'      => '#fff',
				'background' => '#000000',
			),
			'#secure-payment-field[type=button]:hover' => array(
				'color'      => '#fff',
				'background' => '#000000',
			),
			'#secure-payment-field[type=button]:disabled:focus' => array(
				'color'      => '#fff',
				'background' => '#808080',
			),
			'#secure-payment-field[type=button]:disabled:hover' => array(
				'color'      => '#fff',
				'background' => '#808080',
			),
			'.card-cvv' => array(
				'background'      => 'transparent url('.$imageBase.'/cvv.png) no-repeat right',
				'background-size' => '63px 40px'
			),
			'.card-cvv.card-type-amex' => array(
				'background'      => 'transparent url('.$imageBase.'/cvv-amex.png) no-repeat right',
				'background-size' => '63px 40px'
			),
			'.card-number::-ms-clear' => array(
				'display' => 'none',
			),
			'input[placeholder]' => array(
				'letter-spacing' => '.5px',
			),
			'img.card-number-icon' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-unknown@2x.png) no-repeat',
				'background-size' => '100%',
				'width' => '65px',
				'height' => '40px',
				'position' => 'absolute',
				'right' => '0',
				'top' => '50%',
				'margin-top' => '-20px',
				'background-position' => '50% 50%',
			),
			'img.card-number-icon[src$=\'/gp-cc-generic.svg\']' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-mastercard@2x.png) no-repeat',
				'background-size' => '100%',
				'background-position-y' => 'bottom',
			),
			'img.card-number-icon.card-type-diners' => array(
				'background' => 'transparent url(' . $imageBase . '/gp-cc-diners.svg) no-repeat',
				'background-size' => '80%',
				'background-position-x' => '10px',
				'background-position-y' => '3px',
			),
			'img.card-number-icon.invalid.card-type-amex' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-amex@2x.png) no-repeat 140%',
				'background-size' => '85%',
				'background-position-y' => '87%',
			),
			'img.card-number-icon.invalid.card-type-discover' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-discover@2x.png) no-repeat',
				'background-size' => '110%',
				'background-position-y' => '92%',
				'width' => '85px',
			),
			'img.card-number-icon.invalid.card-type-jcb' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-jcb@2x.png) no-repeat 175%',
				'background-size' => '95%',
				'background-position-y' => '85%',
			),
			'img.card-number-icon.invalid.card-type-mastercard' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-mastercard@2x.png) no-repeat',
				'background-size' => '113%',
				'background-position-y' => 'bottom',
			),
			'img.card-number-icon.invalid.card-type-visa' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-visa@2x.png) no-repeat',
				'background-size' => '120%',
				'background-position-y' => 'bottom',
				'background-position-x' => '-5px',
			),
			'img.card-number-icon.valid.card-type-amex' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-amex@2x.png) no-repeat 140%',
				'background-size' => '85%',
				'background-position-y' => '-9px',
			),
			'img.card-number-icon.valid.card-type-discover' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-discover@2x.png) no-repeat',
				'background-size' => '110%',
				'background-position-y' => '-4px',
				'width' => '85px',
			),
			'img.card-number-icon.valid.card-type-jcb' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-jcb@2x.png) no-repeat 175%',
				'background-size' => '95%',
				'background-position-y' => '-7px',
			),
			'img.card-number-icon.valid.card-type-mastercard' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-mastercard@2x.png) no-repeat',
				'background-size' => '113%',
				'background-position-y' => '2px',
			),
			'img.card-number-icon.valid.card-type-visa' => array(
				'background' => 'transparent url(' . $imageBase . '/logo-visa@2x.png) no-repeat',
				'background-size' => '120%',
				'background-position-y' => '0px',
				'background-position-x' => '-5px',
			),
		);

		return json_encode($securePaymentFieldsStyles);
	}

	/**
	 * Base assets URL for secure payment fields.
	 *
	 * @return string
	 */
	private function securePaymentFieldsAssetBaseUrl() {
		if ($this->isProduction) {
			return 'https://js.globalpay.com/v1';
		}

		return 'https://js-cert.globalpay.com/v1';
	}

	/**
	 * Configuration for the secure payment fields on the client side.
	 *
	 * @return array
	 */
	protected function securePaymentFieldsFrontendConfiguration() {
		try {
			return $this->getFrontendGatewayOptions();
		} catch (\Exception $e) {
			return array(
				'error'    => true,
				'message'  => $e->getMessage(),
			);
		}
	}

	/**
	 * The configuration for the globalpayments_secure_payment_fields_params object.
	 *
	 * @param bool $jsonEncode
	 *
	 * @return array|false|string
	 */
	public function securePaymentFieldsParams($jsonEncode = true) {
		$params = array(
			'id'             => $this->gatewayId,
			'gatewayOptions' => $this->securePaymentFieldsFrontendConfiguration(),
			'fieldOptions'   => $this->securePaymentFieldsConfiguration(),
			'fieldStyles'    => $this->securePaymentFieldsStyles(),
		);

		return $jsonEncode ? json_encode($params) : $params;
	}

	/**
	 * The configuration for the globalpayments_secure_payment_threedsecure_params object.
	 * Can be overridden by individual gateway implementations that support 3DS.
	 *
	 * @param bool $jsonEncode
	 *
	 * @return array|false|string
	 */
	public function securePaymentFieldsThreeDSecureParams(OrderData $order = null, $jsonEncode = true) {

		if (!$this->supportsThreeDSecure) {
			return array();
		}

		$params['threedsecure'] = array(
			'methodNotificationUrl'     => $this->methodNotificationUrl,
			'challengeNotificationUrl'  => $this->challengeNotificationUrl,
			'checkEnrollmentUrl'        => $this->checkEnrollmentUrl,
			'initiateAuthenticationUrl' => $this->initiateAuthenticationUrl,
		);

		if (isset($order)) {
			$params['order'] = $order;
        }

		return $jsonEncode ? json_encode($params) : $params;
	}

	public function showPaymentForm() {
		echo $this->getEnvironmentIndicator();
		$fields = $this->getCreditCardFormatFields();
		foreach ($fields as $field) {
			echo $field;
		}

		$this->loadStyles();
		$this->loadScripts();
	}

	public function loadStyles() {
		?>
        <link rel="stylesheet" href="<?php echo $this->path; ?>../../assets/css/globalpayments-secure-payment-fields.css">
		<?php
	}

	public function loadScripts() {
		$globalpayments_secure_payment_fields_params = $this->securePaymentFieldsParams();
		$globalpayments_secure_payment_threedsecure_params = $this->securePaymentFieldsThreeDSecureParams();
		?>
        <script src="https://js.globalpay.com/v1/globalpayments.js"></script>
		<?php if ($this->supportsThreeDS) { ?>
            <script src="<?php echo $this->path; ?>../../assets/js/globalpayments-3ds.js"></script>
		<?php } ?>
        <script>
			var globalpayments_secure_payment_fields_params = <?php echo $globalpayments_secure_payment_fields_params; ?>;
			var globalpayments_secure_payment_threedsecure_params = <?php echo $globalpayments_secure_payment_threedsecure_params; ?>;
        </script>
        <script src="<?php echo $this->path; ?>../../assets/js/globalpayments-secure-payment-fields.js"></script>
		<?php
	}
}
