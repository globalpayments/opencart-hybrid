<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Bank Payment';

// Text
$_['text_globalpayments_openbanking'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = 'Enable/Disable';
$_['label_title']          = 'Title';
$_['label_payment_action'] = 'Payment Action';
$_['label_account_number'] = 'Account Number';
$_['label_account_name']   = 'Account Name';
$_['label_sort_code']      = 'Sort Code';
$_['label_countries']      = 'Countries';
$_['label_iban']           = 'IBAN';
$_['label_currencies']     = 'Currencies';
$_['label_sort_order']     = 'Sort Order';

// Help
$_['help_title']                      = 'This controls the title which the user sees during checkout.';
$_['help_payment_action']             = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_openbanking_account_number'] = 'Account number, for bank transfers within the UK (UK to UK bank). Only required if no bank details are stored on account.';
$_['help_openbanking_account_name']   = 'The name of the individual or business on the bank account. Only required if no bank details are stored on account.';
$_['help_openbanking_sort_code']      = 'Six digits which identify the bank and branch of an account. Included with the Account Number for UK to UK bank transfers. Only required if no bank details are stored on account.';
$_['help_openbanking_iban']           = 'Only required for EUR transacting merchants.';
$_['help_openbanking_countries']      = 'Allows you to input a COUNTRY or string of COUNTRIES to limit what is shown to the customer. Including a country overrides your default account configuration. <br/><br/>
	Format: List of ISO 3166-2 (two characters) codes separated by a | <br/><br/>
	Example: FR|GB|IE';
$_['help_openbanking_currencies']     = 'Note: The payment method will be available at checkout only for the selected currencies.';


// Entry
$_['entry_enabled']                      = 'Enable Bank Payment';
$_['entry_payment_action_authorize']     = 'Authorize only';
$_['entry_payment_action_charge']        = 'Authorize + Capture';
$_['entry_gbp']                          = 'GBP';
$_['entry_eur']                          = 'EUR';

// Placeholder
$_['placeholder_openbanking_title'] = 'Bank Payment';
$_['placeholder_title']             = 'Bank Payment';
$_['label_currencies_tooltip']      = 'Note: The payment method will be displayed at checkout only for the selected currencies.';

// Error
$_['error_permission']              = 'Warning: You do not have permission to modify payment Bank Payment!';
$_['error_settings_openbanking']    = 'Warning: Your Bank Payment settings were not saved!';
$_['error_openbanking_currencies']  = 'Please provide at least one currency.';

// Success
$_['success_settings_openbanking'] = 'Your Bank Payment settings were saved!';
