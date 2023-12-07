<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Sepa';

// Text
$_['text_globalpayments_sepa'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = 'Enable/Disable';
$_['label_title']          = 'Title';
$_['label_payment_action'] = 'Payment Action';
$_['label_iban']           = 'IBAN';
$_['label_account_name']   = 'Account Name';
$_['label_countries']      = 'Countries';

// Help
$_['help_title']          = 'This controls the title which the user sees during checkout.';
$_['help_payment_action'] = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_sepa_iban'] = 'Key field for bank transfers for Europe-to-Europe transfers. Only required if no bank details are stored on account.';
$_['help_sepa_account_name'] = 'The name of the individual or business on the bank account. Only required if no bank details are stored on account.';
$_['help_sepa_countries'] = 'Allows you to input a COUNTRY or string of COUNTRIES to limit what is shown to the customer. Including a country overrides your default account configuration. <br/><br/>
                     Format: List of ISO 3166-2 (two characters) codes separated by a | <br/><br/>
                     Example: FR|GB|IE';

// Entry
$_['entry_enabled']                      = 'Enable Sepa';
$_['entry_payment_action_authorize']     = 'Authorize only';
$_['entry_payment_action_charge']        = 'Authorize + Capture';

// Placeholder
$_['placeholder_sepa_title'] = 'Pay with Sepa';

// Error
$_['error_permission']    = 'Warning: You do not have permission to modify payment Sepa!';
$_['error_settings_sepa'] = 'Warning: Your Sepa settings were not saved!';

// Success
$_['success_settings_sepa'] = 'Your Sepa settings were saved!';
