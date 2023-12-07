<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Faster Payments';

// Text
$_['text_globalpayments_fasterpayments'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = 'Enable/Disable';
$_['label_title']          = 'Title';
$_['label_payment_action'] = 'Payment Action';
$_['label_account_number'] = 'Account Number';
$_['label_account_name']   = 'Account Name';
$_['label_sort_code']      = 'Sort Code';
$_['label_countries']      = 'Countries';

// Help
$_['help_title']          = 'This controls the title which the user sees during checkout.';
$_['help_payment_action'] = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_fasterpayments_account_number'] = 'Account number, for bank transfers within the UK (UK to UK bank). Only required if no bank details are stored on account.';
$_['help_fasterpayments_account_name'] = 'The name of the individual or business on the bank account. Only required if no bank details are stored on account.';
$_['help_fasterpayments_sort_code'] = 'Six digits which identify the bank and branch of an account. Included with the Account Number for UK to UK bank transfers. Only required if no bank details are stored on account.';
$_['help_fasterpayments_countries'] = 'Allows you to input a COUNTRY or string of COUNTRIES to limit what is shown to the customer. Including a country overrides your default account configuration. <br/><br/>
                     Format: List of ISO 3166-2 (two characters) codes separated by a | <br/><br/>
                     Example: FR|GB|IE';

// Entry
$_['entry_enabled']                      = 'Enable Faster Payments';
$_['entry_payment_action_authorize']     = 'Authorize only';
$_['entry_payment_action_charge']        = 'Authorize + Capture';

// Placeholder
$_['placeholder_fasterpayments_title'] = 'Pay with Faster Payments';

// Error
$_['error_permission']              = 'Warning: You do not have permission to modify payment Faster Payments!';
$_['error_settings_fasterpayments'] = 'Warning: Your Faster Payments settings were not saved!';

// Success
$_['success_settings_fasterpayments'] = 'Your Faster Payments settings were saved!';
