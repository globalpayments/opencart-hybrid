/**
 * GlobalPayments TransIT for OpenCart - WordPress Implementation Approach
 * Based on WordPress globalpayments-secure-payment-fields.js
 */
var GlobalPaymentsTransIT = {
    config: null,
    gateway: null,
    fields: null,
    form: null,
    cardForm: null,
    isCardFormValid: false,
    isSubmitting: false,

    init: function(params) {
        this.config = params;

        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initialize();
            });
        } else {
            this.initialize();
        }
    },

    initialize: function() {
        try {
            if (typeof GlobalPayments === 'undefined') {
                this.showError('Payment system not available. Please refresh the page.');
                return;
            }

            if (this.config.gatewayOptions.error) {
                this.showError(this.config.gatewayOptions.message);
                return;
            }

            try {
                this.gateway = GlobalPayments.configure({
                    gateway: this.config.gatewayOptions.gateway,
                    env: this.config.gatewayOptions.env,
                    merchantId: this.config.gatewayOptions.merchantId,
                    userId: this.config.gatewayOptions.userId,
                    password: this.config.gatewayOptions.password,
                    deviceId: this.config.gatewayOptions.deviceId,
                    tsepDeviceId: this.config.gatewayOptions.tsepDeviceId,
                    transactionKey: this.config.gatewayOptions.transactionKey
                });

                // Render secure payment fields like WordPress
                this.renderSecurePaymentFields();

            } catch (configError) {
                this.showError('Payment gateway configuration failed. Please contact support.');
                return;
            }

        } catch (error) {
            this.showError('Payment system initialization failed. Please refresh the page.');
        }
    },

    renderSecurePaymentFields: function() {

        try {
            // Check if fields are already rendered (like WordPress does)
            if (this.config.fieldOptions && this.config.fieldOptions['card-number-field']) {
                const cardNumberSelector = '#' + this.config.id + '-' + this.config.fieldOptions['card-number-field'].class;
                if ($(cardNumberSelector).children().length > 0) {
                    return;
                }
            }

            if (!GlobalPayments.configure) {
                this.showError('Payment system not available. Please refresh the page.');
                return;
            }

            var gatewayConfig = this.config.gatewayOptions;
            if (gatewayConfig.error) {
                if (gatewayConfig.hide) {
                    $('.payment_method_globalpayments_transit').hide();
                    return;
                }
                this.showError(gatewayConfig.message);
                return;
            }

            const submitButtonSelector = '#globalpayments_transit-submit-button';
            if ($(submitButtonSelector).length === 0) {
                $('#button-confirm').closest('.buttons').before(
                    '<div id="globalpayments_transit-submit-button" class="globalpayments-submit-target"></div>'
                );
            }


            // Configure GlobalPayments (WordPress approach)
            GlobalPayments.configure(gatewayConfig);
            GlobalPayments.on('error', this.handleErrors.bind(this));


            // Create the card form exactly like WordPress
            this.cardForm = GlobalPayments.ui.form({
                fields: this.getFieldConfiguration(),
                styles: this.getStyleConfiguration()
            });

            if ($(this.getSubmitButtonTargetSelector(this.config.id)).length === 0) {
						this.createSubmitButtonTarget(this.config.id);
					}

            // Set up event handlers (WordPress pattern)
            this.cardForm.on('submit', 'click', this.blockOnSubmit.bind(this));
            this.cardForm.on('token-success', this.handleResponse.bind(this));
            this.cardForm.on('token-error', this.handleErrors.bind(this));
            this.cardForm.on('error', this.handleErrors.bind(this));
            this.cardForm.on('card-form-validity', function(isValid) {
                this.isCardFormValid = !!isValid;
                if (!isValid) {
                    this.unblockOnError();
                }
            }.bind(this));

            var self = this;
            // Match the visibility of our payment form (WordPress pattern)
            this.cardForm.ready(function() {
                self.addFieldLabels();
                self.toggleSubmitButtons();
                self.updateCardMode();

                // Set flag to indicate TransIT is loaded
                window.globalPaymentsTransitLoaded = true;
            });

        } catch (error) {
            this.showError('Payment form setup failed. Please refresh the page.');
        }
    },

    handleResponse: function (response) {
        if (!this.validateTokenResponse(response)) {
            return;
        }

        var self = this;
        // getCvv() retrieves the raw CVV digits from the TSEP hosted iframe
        self.cardForm.frames['card-cvv'].getCvv().then(function(cvv) {
            if (cvv) {
                response.details = response.details || {};
                response.details.cardSecurityCode = cvv;
            }

            var responseDetails = {
                details: {
                    cardType: response.details.cardType,
                    cardLast4: response.details.cardLast4,
                    expiryMonth: response.details.expiryMonth,
                    expiryYear: response.details.expiryYear,
                    cardholderName: response.details.cardholderName,
                    cardSecurityCode: response.details.cardSecurityCode,
                },
                paymentReference: response.paymentReference
            };

            self.paymentTokenResponse = JSON.stringify(responseDetails);
            self.createInputElement('paymentTokenResponse', self.paymentTokenResponse);
            self.submitForm();
        }).catch(function() {
            self.unblockOnError();
            self.showPaymentError('Unable to retrieve card security code. Please try again.');
        });
    },

    /**
     * Creates hidden input element to pass token data to backend
     *
     * @param {string} name Input element name
     * @param {string} value Input element value
     */
    createInputElement: function(name, value) {
        // Remove existing input if it exists
        var existingInput = document.querySelector('input[name="' + name + '"]');
        if (existingInput) {
            existingInput.remove();
        }

        // Create new hidden input
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;

        // Add to the checkout form
        var form = this.getForm();
        if (form) {
            form.appendChild(input);
        }
    },


    /**
     * Gets the checkout form element
     *
     * @returns {HTMLElement|null}
     */
    getForm: function() {
        // Try to find OpenCart checkout form
        return document.getElementById('globalpayments-payment-form') ;
    },

    /**
     * Submits the form to place the order
     */
    submitForm: function() {
        try {
            this.unblockOnError();

            var form = this.getForm();
            if (!form) {
                this.showError('Unable to submit order. Please refresh the page and try again.');
                return;
            }

            // Ensure payment method is set
            var paymentMethodInput = form.querySelector('input[name="payment_method"]:checked') ||
                                    form.querySelector('input[name="payment_method"]');

            if (!paymentMethodInput) {
                // Create payment method input if it doesn't exist
                var methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = 'payment_method';
                methodInput.value = 'globalpayments_transit';
                form.appendChild(methodInput);
            }

            // Set form action to the TransIT confirm endpoint
            if (!form.action || form.action.indexOf('globalpayments_transit/confirm') === -1) {
                form.action = 'index.php?route=extension/payment/globalpayments_transit/confirm';
            }

            this.submitAjaxForm(form);

        } catch (error) {
            this.showError('Failed to place order. Please try again.');
        }
    },

    /**
     * Handle AJAX form submission for payment processing
     *
     * @param {HTMLElement} form The form element
     */
    submitAjaxForm: function(form) {
        var formData = new FormData(form);

        formData.append('ajax', '1');

        this.blockOnSubmit();

        fetch(form.action || window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                this.unblockOnError();
                this.showError(data.error);
            } else if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                this.unblockOnError();
                this.showError('Payment processing completed but no redirect URL received.');
            }
        })
        .catch(error => {
            this.unblockOnError();
            this.showError('Payment processing failed. Please try again.');
        });
    },

    /**
     * Validates the tokenization response
     *
     * @param {object} response tokenization response
     *
     * @returns {boolean} status of validations
     */
    validateTokenResponse: function ( response ) {
        this.resetValidationErrors();

        var result = true;

        if (response.details) {
            var expirationDate = new Date( response.details.expiryYear, response.details.expiryMonth - 1 );
            var now = new Date();
            var thisMonth = new Date( now.getFullYear(), now.getMonth() );

            if ( ! response.details.expiryYear || ! response.details.expiryMonth || expirationDate < thisMonth ) {
                this.showValidationError( 'card-expiration' );
                result = false;
            }
        }

        if ( response.details && ! response.details.cardSecurityCode ) {
            this.showValidationError( 'card-cvv' );
            result = false;
        }

        return result;
    },

    /**
     * Handles errors from the payment field iframes
     *
     * @param {object} error Details about the error
     *
     * @returns
     */
    handleErrors: function ( error ) {
        this.resetValidationErrors();

        if ( ! error.reasons ) {
            this.showPaymentError('Something went wrong. Please contact us to get assistance.');
            return;
        }

        var numberOfReasons = error.reasons.length;
        for ( var i = 0; i < numberOfReasons; i++ ) {
            var reason = error.reasons[i];
            switch ( reason.code ) {
                case 'NOT_AUTHENTICATED':
                    this.showPaymentError('We\'re not able to process this payment. Please refresh the page and try again.');
                    break;
                case 'INVALID_CARD_NUMBER':
                    this.showValidationError( 'card-number' );
                    break;
                case 'INVALID_CARD_EXPIRATION':
                    this.showValidationError( 'card-expiration' );
                    break;
                case 'INVALID_CARD_SECURITY_CODE':
                    this.showValidationError( 'card-cvv' );
                    break;
                case 'INVALID_CARD_HOLDER_NAME':
                case 'TOO_LONG_DATA':
                    this.showValidationError('card-holder-name');
                    break;
                case 'MANDATORY_DATA_MISSING':
                    var n = reason.message.search( "card type" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-number' );
                        break;
                    }
                    var n = reason.message.search( "expiry_year" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-expiration' );
                        break;
                    }
                    var n = reason.message.search( "expiry_month" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-expiration' );
                        break;
                    }
                    var n = reason.message.search( "card.cvn.number" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-cvv' );
                        break;
                    }
                case 'INVALID_REQUEST_DATA':
                    var n = reason.message.search( "number contains unexpected data" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-number' );
                        break;
                    }
                    var n = reason.message.search( "Luhn Check" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-number' );
                        break;
                    }
                    var n = reason.message.search( "cvv contains unexpected data" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-cvv' );
                        break;
                    }
                    var n = reason.message.search( "expiry_year" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-expiration' );
                        break;
                    }
                    var n = reason.message.search("card.number");
                    if (n >= 0) {
                        this.showValidationError('card-number');
                        break;
                    }
                case 'SYSTEM_ERROR_DOWNSTREAM':
                    var n = reason.message.search( "card expdate" );
                    if ( n>=0 ) {
                        this.showValidationError( 'card-expiration' );
                        break;
                    }
                case 'ERROR':
                    if(reason.message == "IframeField: target cannot be found with given selector")
                        break;
                    this.showPaymentError(reason.message);
                    break;
                default:
                    this.showPaymentError(reason.message);
            }
        }
    },

    /**
     * Hides all validation error messages
     *
     * @returns
     */
    resetValidationErrors: function () {
        $('.' + this.config.id + ' .globalpayments-validation-error').hide();
    },

    /**
     * Shows payment error and scrolls to it
     *
     * @param {string} message Error message
     *
     * @returns
     */
    showPaymentError: function (message) {
        var $form = $(this.getForm());

        // Remove notices from all sources
        $('.globalpayments-checkout-error').remove();

        var $error = $('<div>').addClass('alert alert-danger globalpayments-checkout-error').text(message);
        $form.prepend($error);

        $('html, body').animate({
            scrollTop: ($form.offset().top - 100)
        }, 1000);

        this.unblockOnError();
    },

    /**
     * Shows the validation error for a specific payment field
     *
     * @param {string} fieldType Field type to show its validation error
     *
     * @returns
     */
    showValidationError: function (fieldType) {
        var mappedClass = this.getValidationWrapperClass(fieldType);
        var selector = '.' + this.config.id + '.' + mappedClass + ' .globalpayments-validation-error';
        $(selector).addClass('alert alert-danger');
        $(selector).show();
        this.unblockOnError();
    },

    getValidationWrapperClass: function(fieldType) {
        var classMap = {
            'card-number': 'card-number-field',
            'card-expiration': 'card-expiry-field',
            'card-cvv': 'card-cvv-field',
            'card-holder-name': (this.config.fieldOptions && this.config.fieldOptions['card-holder-name-field'])
                ? this.config.fieldOptions['card-holder-name-field'].class
                : 'card-holder-name-field'
        };

        return classMap[fieldType] || fieldType;
    },

    addFieldLabels: function () {
        var self = this;
        var fieldDefinitions = [
            { key: 'card-number-field', label: 'Card Number *' },
            { key: 'card-expiry-field', label: 'Card Expiration *' },
            { key: 'card-cvv-field', label: 'Card CVV *' }
        ];

        if ((this.config.fieldOptions && this.config.fieldOptions['card-holder-name-field']) ||
            (this.config.fieldOptions && this.config.fieldOptions['card-holder-name'])) {
            fieldDefinitions.push({ key: 'card-holder-name-field', fallbackKey: 'card-holder-name', label: 'Card Holder Name *' });
        }

        fieldDefinitions.forEach(function(def) {
            if (!self.config.fieldOptions) {
                return;
            }

            var option = self.config.fieldOptions[def.key] || (def.fallbackKey ? self.config.fieldOptions[def.fallbackKey] : null);
            if (!option || !option.class) {
                return;
            }

            var targetSelector = '#' + self.config.id + '-' + option.class;
            var $target = $(targetSelector);

            if ($target.length && $target.prev('.gp-field-label').length === 0) {
                var $label = $('<label></label>');
                $label.addClass('gp-field-label');
                $label.text(def.label);
                $target.before($label);
            }
        });
    },




    showError: function(message) {
        const existingError = document.querySelector('.globalpayments-error');
        if (existingError) {
            existingError.remove();
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'globalpayments-error alert alert-danger';
        const icon = document.createElement('i');
        icon.className = 'fa fa-exclamation-triangle';
        const strong = document.createElement('strong');
        strong.textContent = 'Payment Error:';
        const text = document.createTextNode(' ' + (message || 'An unexpected error occurred. Please try again.'));
        errorDiv.appendChild(icon);
        errorDiv.appendChild(document.createTextNode(' '));
        errorDiv.appendChild(strong);
        errorDiv.appendChild(text);

        // Find best place to insert error - prioritize the active TransIT checkout form
        const form = this.getForm();
        const transitOverride = document.querySelector('.globalpayments-transit-override');
        const transitForm = document.getElementById('globalpayments-transit-form');
        const paymentMethodContent = document.getElementById('collapse-payment-method');
        const paymentContainer = transitOverride || transitForm ||
                               form || paymentMethodContent ||
                               document.getElementById('globalpayments_transit-card-number')?.closest('.panel-body') ||
                               document.getElementById('payment') ||
                               document.querySelector('.panel-body');

        if (paymentContainer) {
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorDiv, form);
            } else {
                paymentContainer.insertBefore(errorDiv, paymentContainer.firstChild);
            }

            // Scroll to error smoothly
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Auto-hide after 10 seconds
            setTimeout(function() {
                if (errorDiv.parentNode) {
                    errorDiv.style.transition = 'opacity 0.5s';
                    errorDiv.style.opacity = '0';
                    setTimeout(function() {
                        errorDiv.remove();
                    }, 500);
                }
            }, 10000);
        } else {
            // Fallback: ensure users see the message even on custom themes.
            alert(message || 'An unexpected error occurred. Please try again.');
        }
    },

    /**
     * Gets payment field configuration - based on WordPress implementation
     *
     * @returns {object}
     */
    getFieldConfiguration: function() {
        var fields = {
            'card-number': {
                placeholder: this.config.fieldOptions['card-number-field'].placeholder || 'Card Number',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-number-field'].class
            },
            'card-expiration': {
                placeholder: this.config.fieldOptions['card-expiry-field'].placeholder || 'MM / YY',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-expiry-field'].class
            },
            'card-cvv': {
                placeholder: this.config.fieldOptions['card-cvv-field'].placeholder || 'CVV',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-cvv-field'].class
            },
            'submit': {
                text: this.getSubmitButtonText(),
                target: '#globalpayments_transit-submit-button'
            }
        };

        // Add card holder name field if available
        if (this.config.fieldOptions.hasOwnProperty('card-holder-name-field')) {
            fields["card-holder-name"] = {
                placeholder: this.config.fieldOptions['card-holder-name-field'].placeholder || 'Card Holder Name',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-holder-name-field'].class
            };
        }

        return fields;
    },



    /**
     * Creates the parent for the submit button
     *
     * @returns
     */
    createSubmitButtonTarget: function (id) {
        var el = document.createElement('div')
        el.id = this.getSubmitButtonTargetSelector(id).replace('#', '');
        el.className = 'globalpayments ' + id + ' card-submit';
        $(this.getPlaceOrderButtonSelector()).after(el);
        // match the visibility of our payment form
        this.toggleSubmitButtons();
    },
    /**
     * Convenience function to get CSS selector for the custom 'Place Order' button's parent element
     *
     * @param {string} id
     * @returns {string}
     */
    getSubmitButtonTargetSelector: function (id) {
        return '#' + id + '-submit-button';
    },
    /**
        * Convenience function to get CSS selector for the built-in 'Place Order' button
        *
        * @returns {string}
        */
    getPlaceOrderButtonSelector: function () {
    return '#button-confirm';
    },


    /**
     * Gets payment field styles - based on WordPress implementation
     *
     * @returns {object}
     */
    getStyleConfiguration: function() {
        if (this.config.field_styles) {
            return JSON.parse(this.config.field_styles);
        }

        // Default styles if none provided - proper JavaScript object syntax
        return {
            'html': {
                'font-size': '100%',
                '-webkit-text-size-adjust': '100%'
            },
            'body': {},
            '#secure-payment-field-wrapper': {
                'position': 'relative'
            },
            '#secure-payment-field': {
                'background-color': '#fff',
                'border': '1px solid #ccc',
                'border-radius': '4px',
                'display': 'block',
                'font-size': '14px',
                'height': '35px',
                'padding': '6px 12px',
                'width': '100%'
            },
            '#secure-payment-field:focus': {
                'border': '1px solid lightblue',
                'box-shadow': '0 1px 3px 0 #cecece',
                'outline': 'none'
            },
            'button#secure-payment-field.submit': {
                'border': '0',
                'border-radius': '0',
                'background': 'none',
                'background-color': '#333333',
                'border-color': '#333333',
                'color': '#fff',
                'cursor': 'pointer',
                'padding': '.6180469716em 1.41575em',
                'text-decoration': 'none',
                'font-weight': '600',
                'text-shadow': 'none',
                'display': 'inline-block',
                '-webkit-appearance': 'none',
                'height': 'initial',
                'width': '100%',
                'flex': 'auto',
                'position': 'static',
                'margin': '0',
                'white-space': 'pre-wrap',
                'margin-bottom': '0',
                'float': 'none',
                'font': '600 1.41575em/1.618 Source Sans Pro,HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica,Arial,Lucida Grande,sans-serif !important'
            },
            'button#secure-payment-field.submit:disabled': {
                'background-color': '#808080',
                'border-color': '#808080',
                'cursor': 'not-allowed'
            },
            '#secure-payment-field[type=button]:focus': {
                'color': '#fff',
                'background': '#000000'
            },
            '#secure-payment-field[type=button]:hover': {
                'color': '#fff',
                'background': '#000000'
            },
            '#secure-payment-field[type=button]:disabled:focus': {
                'color': '#fff',
                'background': '#808080'
            },
            '#secure-payment-field[type=button]:disabled:hover': {
                'color': '#fff',
                'background': '#808080'
            },
            '.card-cvv': {
                'background': 'transparent url(\'images/cvv.png\') no-repeat right',
                'background-size': '63px 40px'
            },
            '.card-cvv.card-type-amex': {
                'background': 'transparent url(\'images/cvv-amex.png\') no-repeat right',
                'background-size': '63px 40px'
            },
            '.card-number::-ms-clear': {
                'display': 'none'
            },
            'input[placeholder]': {
                'letter-spacing': '.5px'
            },
            'img.card-number-icon': {
                'background': 'transparent url(\'images/logo-unknown@2x.png\') no-repeat',
                'background-size': '100%',
                'width': '65px',
                'height': '40px',
                'position': 'absolute',
                'right': '0',
                'top': '25px',
                'margin-top': '-20px',
                'background-position': '50% 50%'
            },
            'img.card-number-icon[src$=\'/gp-cc-generic.svg\']': {
                'background': 'transparent url(\'images/logo-mastercard@2x.png\') no-repeat',
                'background-size': '100%',
                'background-position-y': 'bottom'
            },
            'img.card-number-icon.card-type-diners': {
                'background': 'transparent url(\'images/gp-cc-diners.svg\') no-repeat',
                'background-size': '80%',
                'background-position-x': '10px',
                'background-position-y': '3px'
            },
            'img.card-number-icon.invalid.card-type-amex': {
                'background': 'transparent url(\'images/logo-amex@2x.png\') no-repeat 140%',
                'background-size': '85%',
                'background-position-y': '87%'
            },
            'img.card-number-icon.invalid.card-type-discover': {
                'background': 'transparent url(\'images/logo-discover@2x.png\') no-repeat',
                'background-size': '110%',
                'background-position-y': '92%',
                'width': '85px'
            },
            'img.card-number-icon.invalid.card-type-jcb': {
                'background': 'transparent url(\'images/logo-jcb@2x.png\') no-repeat 175%',
                'background-size': '95%',
                'background-position-y': '85%'
            },
            'img.card-number-icon.invalid.card-type-mastercard': {
                'background': 'transparent url(\'images/logo-mastercard@2x.png\') no-repeat',
                'background-size': '113%',
                'background-position-y': 'bottom'
            },
            'img.card-number-icon.invalid.card-type-visa': {
                'background': 'transparent url(\'images/logo-visa@2x.png\') no-repeat',
                'background-size': '120%',
                'background-position-y': 'bottom',
                'background-position-x': '-5px'
            },
            'img.card-number-icon.valid.card-type-amex': {
                'background': 'transparent url(\'images/logo-amex@2x.png\') no-repeat 140%',
                'background-size': '85%',
                'background-position-y': '-9px'
            },
            'img.card-number-icon.valid.card-type-discover': {
                'background': 'transparent url(\'images/logo-discover@2x.png\') no-repeat',
                'background-size': '110%',
                'background-position-y': '-4px',
                'width': '85px'
            },
            'img.card-number-icon.valid.card-type-jcb': {
                'background': 'transparent url(\'images/logo-jcb@2x.png\') no-repeat 175%',
                'background-size': '95%',
                'background-position-y': '-7px'
            },
            'img.card-number-icon.valid.card-type-mastercard': {
                'background': 'transparent url(\'images/logo-mastercard@2x.png\') no-repeat',
                'background-size': '113%',
                'background-position-y': '2px'
            },
            'img.card-number-icon.valid.card-type-visa': {
                'background': 'transparent url(\'images/logo-visa@2x.png\') no-repeat',
                'background-size': '120%',
                'background-position-y': '0px',
                'background-position-x': '-5px'
            },
            '#field-validation-wrapper': {
                'color': '#a94442 !important',
                'background-color': '#f2dede',
                'border-color': '#ebccd1',
                'padding': '8px 14px 8px 14px',
                'margin-bottom': '10px',
                'border-radius': '4px',
                'border': '1px solid transparent',
                'font-family': '\'Open Sans\', sans-serif !important',
                'font-size': '12px !important'
            }
        };
    },

    /**
     * Gets submit button text - based on WordPress implementation
     *
     * @returns {string}
     */
    getSubmitButtonText: function() {
        const selector = '#button-confirm';
        const button = $(selector);
        return button.data('value') || button.attr('value') || button.text() || 'Place Order';
    },

    /**
     * Block UI on submit - WordPress helper equivalent
     */
    blockOnSubmit: function() {
        if (this.isSubmitting) {
            return;
        }

        this.isSubmitting = true;

        var $form = $(this.getForm());
        if ($form.length) {
            $form.addClass('disabled-during-ajax');
            $form.css({
                'pointer-events': 'none',
                'opacity': '0.6',
                'position': 'relative'
            });
        }

        var submitBtn = $('#button-confirm');
        if (submitBtn.length) {
            submitBtn.data('original-text', submitBtn.val() || submitBtn.text());
            submitBtn.prop('disabled', true);
            submitBtn.val('Processing...');
            submitBtn.text('Processing...');
        }

        $('#globalpayments_transit-submit-button button, #globalpayments_transit-submit-button input[type="button"], #globalpayments_transit-submit-button input[type="submit"]').prop('disabled', true);

        if (!$('#ajax-overlay').length && $form.length) {
            $form.append('<div id="ajax-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.3); z-index: 1000; cursor: wait;"></div>');
        }
    },

    /**
     * Unblock UI on error - WordPress helper equivalent
     */
    unblockOnError: function() {
        this.isSubmitting = false;

        var $form = $(this.getForm());
        if ($form.length) {
            $form.removeClass('disabled-during-ajax');
            $form.css({
                'pointer-events': 'auto',
                'opacity': '1'
            });
            $('#ajax-overlay').remove();
        }

        const submitBtn = $('#button-confirm');
        if (submitBtn.length && submitBtn.data('original-text')) {
            submitBtn.prop('disabled', false);
            submitBtn.val(submitBtn.data('original-text'));
            submitBtn.text(submitBtn.data('original-text'));
        }

        $('#globalpayments_transit-submit-button button, #globalpayments_transit-submit-button input[type="button"], #globalpayments_transit-submit-button input[type="submit"]').prop('disabled', false);
    },

    /**
     * Toggle submit buttons - WordPress helper equivalent
     */
    toggleSubmitButtons: function () {
			var selectedPaymentGatewayId = this.config.id;

            $( this.getPlaceOrderButtonSelector() ).hide();

            if(selectedPaymentGatewayId == 'globalpayments_transit'){
                    $( this.getPlaceOrderButtonSelector() ).hide();
            }else{
                $( this.getPlaceOrderButtonSelector() ).show();
            }
		},

    processPayment: function() {
        if (this.isSubmitting) {
            return;
        }

        var selectedSavedCardToken = this.getSelectedSavedCardToken();
        if (selectedSavedCardToken && selectedSavedCardToken !== 'new') {
            this.submitForm();
            return;
        }

        this.blockOnSubmit();

        if (this.cardForm && this.cardForm.frames && this.cardForm.frames.submit) {
            this.cardForm.frames.submit.click();
        } else {
            this.unblockOnError();
            this.showPaymentError('Payment form is not ready. Please refresh and try again.');
        }
		},

        getSelectedSavedCardToken: function() {
            var selected = document.querySelector('input[name="saved_card_token"]:checked');
            return selected ? selected.value : 'new';
        },

        updateCardMode: function() {
            var selectedSavedCardToken = this.getSelectedSavedCardToken();
            var useNewCard = !selectedSavedCardToken || selectedSavedCardToken === 'new';

            var newCardFieldsetSelector = '#' + this.config.id + '-card';
            var newCardFieldsSelector = '#' + this.config.id + '-new-card-fields';
            if (useNewCard) {
                $(newCardFieldsetSelector).show();
                $(newCardFieldsSelector).show();
                $('#globalpayments_transit-submit-button').show();
                $('#button-confirm').hide();
            } else {
                $(newCardFieldsetSelector).show();
                $(newCardFieldsSelector).hide();
                $('#globalpayments_transit-submit-button').hide();
                $('#button-confirm').show();
            }
        },


};

// Global initialization function for backward compatibility
window.initGlobalPaymentsTransIT = function(params) {
    GlobalPaymentsTransIT.init(params || window.globalpayments_secure_payment_fields_params);
};


