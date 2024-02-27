<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Google Pay';

// Text
$_['text_globalpayments_googlepay'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']                     = 'Enable/Disable';
$_['label_title']                       = 'Title';
$_['label_payment_action']              = 'Payment Action';
$_['label_gp_merchant_id']              = 'Global Payments Client ID';
$_['label_merchant_id']                 = 'Google Merchant ID';
$_['label_merchant_name']               = 'Google Merchant Display Name';
$_['label_accepted_cards']              = 'Accepted Cards';
$_['label_googlepay_button_color']      = 'Button Color';
$_['label_allowed_card_auth_methods']   = 'Allowed Card Auth Methods';
$_['label_sort_order']                  = 'Sort Order';

// Help
$_['help_title']          = 'This controls the title which the user sees during checkout.';
$_['help_payment_action'] = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_gp_merchant_id'] = 'Your Client ID provided by Global Payments.';
$_['help_merchant_id']    = 'Your Merchant ID provided by Google.';
$_['help_merchant_name']  = 'Displayed to the customer in the Google Pay dialog.';

// Entry
$_['entry_enabled']                      = 'Enable Google Pay';
$_['entry_payment_action_authorize']     = 'Authorize only';
$_['entry_payment_action_charge']        = 'Authorize + Capture';
$_['entry_card_type_visa']               = 'Visa';
$_['entry_card_type_mastercard']         = 'MasterCard';
$_['entry_card_type_amex']               = 'AMEX';
$_['entry_card_type_discover']           = 'Discover';
$_['entry_card_type_jcb']                = 'JCB';
$_['entry_googlepay_button_color_white'] = 'White';
$_['entry_googlepay_button_color_black'] = 'Black';
$_['entry_method_pan_only']              = 'PAN_ONLY';
$_['entry_method_cryptogram_3ds']        = 'CRYPTOGRAM_3DS';

$_['label_allowed_card_auth_methods_tooltip']='PAN_ONLY: This authentication method is associated with payment cards stored on file with the user\'s Google Account.
CRYPTOGRAM_3DS: This authentication method is associated with cards stored as Android device tokens.

PAN_ONLY can expose the FPAN, which requires an additional SCA step up to a 3DS check. Currently, Global Payments does not support the Google Pay SCA challenge with an FPAN. For the best acceptance, we recommend that you provide only the CRYPTOGRAM_3DS option.';

// Placeholder
$_['placeholder_googlepay_title'] = 'Pay with Google Pay';

// Error
$_['error_permission']                          = 'Warning: You do not have permission to modify payment Google Pay!';
$_['error_settings_googlepay']                  = 'Warning: Your Google Pay settings were not saved!';
$_['error_gp_merchant_id']                      = 'Please provide Global Payments Client ID.';
$_['error_googlepay_accepted_cards']            = 'Please provide Accepted Cards.';
$_['error_googlepay_allowed_card_auth_methods'] = 'Please provide at least one method.';

// Success
$_['success_settings_googlepay'] = 'Your Google Pay settings were saved!';
