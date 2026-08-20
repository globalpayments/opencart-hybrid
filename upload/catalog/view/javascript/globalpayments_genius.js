/**
 * GlobalPayments Genius for OpenCart
 * Based on WordPress implementation approach with Genius-specific configuration
 */
var GlobalPaymentsGenius = {
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
                // Configure Genius gateway with webApiKey
                this.gateway = GlobalPayments.configure({
                    gateway: this.config.gatewayOptions.gateway,
                    env: this.config.gatewayOptions.env,
                    webApiKey: this.config.gatewayOptions.webApiKey
                });

                // Render secure payment fields
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
            // Check if fields are already rendered
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
                    $('.payment_method_globalpayments_genius').hide();
                    return;
                }
                this.showError(gatewayConfig.message);
                return;
            }

            const submitButtonSelector = '#globalpayments_genius-submit-button';
            if ($(submitButtonSelector).length === 0) {
                $('#button-confirm').closest('.buttons').before(
                    '<div id="globalpayments_genius-submit-button" class="globalpayments-submit-target"></div>'
                );
            }

            // Configure GlobalPayments
            GlobalPayments.configure(gatewayConfig);
            GlobalPayments.on('error', this.handleErrors.bind(this));

            // Create the card form
            this.cardForm = GlobalPayments.ui.form({
                fields: this.getFieldConfiguration(),
                styles: this.getStyleConfiguration()
            });

            if ($(this.getSubmitButtonTargetSelector(this.config.id)).length === 0) {
                this.createSubmitButtonTarget(this.config.id);
            }

            // Set up event handlers
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
            this.cardForm.ready(function() {
                self.addFieldLabels();
                self.toggleSubmitButtons();
                self.updateCardMode();
                window.globalPaymentsGeniusLoaded = true;
            });

        } catch (error) {
            this.showError('Payment form setup failed. Please refresh the page.');
        }
    },

    handleResponse: function (response) {
        if (!this.validateTokenResponse(response)) {
            return;
        }

        var responseDetails = {
            details: {
                cardType: response.details.cardType,
                cardLast4: response.details.cardLast4,
                expiryMonth: response.details.expiryMonth,
                expiryYear: response.details.expiryYear,
                cardholderName: response.details.cardholderName,
            },
            paymentReference: response.paymentReference
        }

        this.paymentTokenResponse = JSON.stringify(responseDetails);

        this.createInputElement('payment_token', response.paymentReference);
        this.createInputElement('paymentTokenResponse', this.paymentTokenResponse);
        this.submitForm();
    },

    createInputElement: function(name, value) {
        var existingInput = document.querySelector('input[name="' + name + '"]');
        if (existingInput) {
            existingInput.remove();
        }

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;

        var form = this.getForm();
        if (form) {
            form.appendChild(input);
        }
    },

    getForm: function() {
        return document.getElementById('globalpayments-payment-form');
    },

    submitForm: function() {
        try {
            var form = this.getForm();
            if (!form) {
                this.showError('Unable to submit order. Please refresh the page and try again.');
                return;
            }

            var paymentMethodInput = form.querySelector('input[name="payment_method"]:checked') ||
                                    form.querySelector('input[name="payment_method"]');

            if (!paymentMethodInput) {
                var methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = 'payment_method';
                methodInput.value = 'globalpayments_genius';
                form.appendChild(methodInput);
            }

            if (!form.action || form.action.indexOf('globalpayments_genius/confirm') === -1) {
                form.action = 'index.php?route=extension/payment/globalpayments_genius/confirm';
            }

            this.submitAjaxForm(form);

        } catch (error) {
            this.showError('Failed to place order. Please try again.');
        }
    },

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

    validateTokenResponse: function (response) {
        this.resetValidationErrors();

        var result = true;

        if (response.details) {
            var expirationDate = new Date(response.details.expiryYear, response.details.expiryMonth - 1);
            var now = new Date();
            var thisMonth = new Date(now.getFullYear(), now.getMonth());

            if (!response.details.expiryYear || !response.details.expiryMonth || expirationDate < thisMonth) {
                this.showValidationError('card-expiration');
                result = false;
            }
        }

        if (response.details && !response.details.cardSecurityCode) {
            this.showValidationError('card-cvv');
            result = false;
        }

        return result;
    },

    handleErrors: function (error) {
        this.resetValidationErrors();

        if (!error.reasons) {
            this.showPaymentError('Something went wrong. Please contact us to get assistance.');
            return;
        }

        var numberOfReasons = error.reasons.length;
        for (var i = 0; i < numberOfReasons; i++) {
            var reason = error.reasons[i];
            switch (reason.code) {
                case 'NOT_AUTHENTICATED':
                    this.showPaymentError('We\'re not able to process this payment. Please refresh the page and try again.');
                    break;
                case 'INVALID_CARD_NUMBER':
                    this.showValidationError('card-number');
                    break;
                case 'INVALID_CARD_EXPIRATION':
                    this.showValidationError('card-expiration');
                    break;
                case 'INVALID_CARD_SECURITY_CODE':
                    this.showValidationError('card-cvv');
                    break;
                case 'INVALID_CARD_HOLDER_NAME':
                case 'TOO_LONG_DATA':
                    this.showValidationError('card-holder-name');
                    break;
                case 'MANDATORY_DATA_MISSING':
                    if (reason.message.search("card type") >= 0) {
                        this.showValidationError('card-number');
                    } else if (reason.message.search("expiry_year") >= 0 || reason.message.search("expiry_month") >= 0) {
                        this.showValidationError('card-expiration');
                    } else if (reason.message.search("card.cvn.number") >= 0) {
                        this.showValidationError('card-cvv');
                    }
                    break;
                case 'INVALID_REQUEST_DATA':
                    if (reason.message.search("number contains unexpected data") >= 0 ||
                        reason.message.search("Luhn Check") >= 0 ||
                        reason.message.search("card.number") >= 0) {
                        this.showValidationError('card-number');
                    } else if (reason.message.search("cvv contains unexpected data") >= 0) {
                        this.showValidationError('card-cvv');
                    } else if (reason.message.search("expiry_year") >= 0) {
                        this.showValidationError('card-expiration');
                    }
                    break;
                case 'SYSTEM_ERROR_DOWNSTREAM':
                    if (reason.message.search("card expdate") >= 0) {
                        this.showValidationError('card-expiration');
                    }
                    break;
                case 'ERROR':
                    if (reason.message != "IframeField: target cannot be found with given selector") {
                        this.showPaymentError(reason.message);
                    }
                    break;
                default:
                    this.showPaymentError(reason.message);
            }
        }
    },

    resetValidationErrors: function () {
        $('.' + this.config.id + ' .globalpayments-validation-error').hide();
    },

    showPaymentError: function (message) {
        var $form = $(this.getForm());

        $('.globalpayments-checkout-error').remove();

        var $error = $('<div>').addClass('alert alert-danger globalpayments-checkout-error').text(message);
        $form.prepend($error);

        $('html, body').animate({
            scrollTop: ($form.offset().top - 100)
        }, 1000);

        this.unblockOnError();
    },

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

        const form = this.getForm();
        const paymentMethodContent = document.getElementById('collapse-payment-method');
        const paymentContainer = form || paymentMethodContent ||
                               document.getElementById('globalpayments_genius-card-number')?.closest('.panel-body') ||
                               document.getElementById('payment') ||
                               document.querySelector('.panel-body');

        if (paymentContainer) {
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorDiv, form);
            } else {
                paymentContainer.insertBefore(errorDiv, paymentContainer.firstChild);
            }
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

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
            alert(message || 'An unexpected error occurred. Please try again.');
        }
    },

    getFieldConfiguration: function() {
        var fields = {
            'card-number': {
                placeholder: this.config.fieldOptions['card-number-field'].placeholder || 'Card Number',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-number-field'].class
            },
            'card-expiration': {
                placeholder: this.config.fieldOptions['card-expiry-field'].placeholder || 'MM / YYYY',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-expiry-field'].class
            },
            'card-cvv': {
                placeholder: this.config.fieldOptions['card-cvv-field'].placeholder || 'CVV',
                target: '#' + this.config.id + '-' + this.config.fieldOptions['card-cvv-field'].class
            }
        };

        if (this.config.fieldOptions['card-holder-name-field'] || this.config.fieldOptions['card-holder-name']) {
            var holderOption = this.config.fieldOptions['card-holder-name-field'] || this.config.fieldOptions['card-holder-name'];
            fields['card-holder-name'] = {
                placeholder: holderOption.placeholder || 'Card Holder Name',
                target: '#' + this.config.id + '-' + holderOption.class
            };
        }

        fields.submit = {
            text: this.getSubmitButtonText(),
            target: this.getSubmitButtonTargetSelector(this.config.id)
        };

        return fields;
    },

    getSubmitButtonTargetSelector: function(id) {
        return '#' + id + '-submit-button';
    },

    createSubmitButtonTarget: function(id) {
        const $button = $('#button-confirm');
        if ($button.length) {
            $button.after('<div id="' + id + '-submit-button"></div>');
        }
    },

    getPlaceOrderButtonSelector: function() {
        return '#button-confirm';
    },

    getStyleConfiguration: function() {
        if (this.config.fieldStyles) {
            return JSON.parse(this.config.fieldStyles);
        }

        return {
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
            }
        };
    },

    getSubmitButtonText: function() {
        const selector = '#button-confirm';
        const button = $(selector);
        return button.data('value') || button.attr('value') || button.text() || 'Place Order';
    },

    blockOnSubmit: function() {
        if (this.isSubmitting) {
            return;
        }

        this.isSubmitting = true;

        var $form = $(this.getForm());
        if ($form.length) {
            $form.addClass('disabled-during-ajax');
            // Apply subtle fade to match UCP implementation
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

        // Also disable rendered secure submit button target to prevent repeated clicks.
        $('#globalpayments_genius-submit-button button, #globalpayments_genius-submit-button input[type="button"], #globalpayments_genius-submit-button input[type="submit"]').prop('disabled', true);

        // Add subtle overlay on top of form (like UCP implementation)
        if (!$('#ajax-overlay').length) {
            $form.append('<div id="ajax-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.3); z-index: 1000; cursor: wait;"></div>');
        }
    },

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

        $('#globalpayments_genius-submit-button button, #globalpayments_genius-submit-button input[type="button"], #globalpayments_genius-submit-button input[type="submit"]').prop('disabled', false);
    },

    toggleSubmitButtons: function () {
        var selectedPaymentGatewayId = this.config.id;

        $(this.getPlaceOrderButtonSelector()).hide();

        if (selectedPaymentGatewayId == 'globalpayments_genius') {
            $(this.getPlaceOrderButtonSelector()).hide();
        } else {
            $(this.getPlaceOrderButtonSelector()).show();
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

        // Show loading state immediately - let SDK handle validation
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

        var newCardFieldsSelector = '#' + this.config.id + '-new-card-fields';
        if (useNewCard) {
            $(newCardFieldsSelector).show();
            $('#globalpayments_genius-submit-button').show();
            $('#button-confirm').hide();
        } else {
            $(newCardFieldsSelector).hide();
            $('#globalpayments_genius-submit-button').hide();
            $('#button-confirm').show();
        }
    }
};

// Global initialization function
window.initGlobalPaymentsGenius = function(params) {
    GlobalPaymentsGenius.init(window.globalpayments_secure_payment_fields_params);
};
