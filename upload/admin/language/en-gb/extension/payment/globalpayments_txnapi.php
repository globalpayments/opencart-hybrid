<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Transaction API';

// Text
$_['text_globalpayments_txnapi'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']           = 'Enable/Disable';
$_['label_title']             = 'Title';
$_['label_is_production']     = 'Live Mode';
$_['label_region']            = 'Region';
$_['label_debug']             = 'Enable Logging';
$_['label_payment_action']    = 'Payment Action';
$_['label_allow_card_saving'] = 'Allow Card Saving';

$_['label_txnapi_public_key']                 = 'Live Public Key';
$_['label_sandbox_txnapi_public_key']         = 'Sandbox Public Key';
$_['label_txnapi_api_key']                    = 'Live API Key';
$_['label_sandbox_txnapi_api_key']            = 'Sandbox API Key';
$_['label_txnapi_api_secret']                 = 'Live API Secret';
$_['label_sandbox_txnapi_api_secret']         = 'Sandbox API Secret';
$_['label_txnapi_account_credential']         = 'Live Account Credential';
$_['label_sandbox_txnapi_account_credential'] = 'Sandbox Account Credential';

// Help
$_['help_title']             = 'This controls the title which the user sees during checkout.';
$_['help_debug']             = 'Log all request to and from gateway. This can also log private data and should only be enabled in a development or stage environment.';
$_['help_payment_action']    = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_allow_card_saving'] = 'Note: to use the card saving feature, you must have multi-use token support enabled on your account. Please contact <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">support</a> with any questions regarding this option.';

// Entry
$_['entry_enabled']                  = 'Enable Transaction API';
$_['entry_is_production']            = 'Live Mode';
$_['entry_us']                       = 'United States';
$_['entry_ca']                       = 'Canada';
$_['entry_debug']                    = 'Enable Logging';
$_['entry_payment_action_authorize'] = 'Authorize only';
$_['entry_payment_action_charge']    = 'Authorize + Capture';
$_['entry_allow_card_saving']        = 'Allow Card Saving';

// Placeholder
$_['placeholder_txnapi_title'] = 'Pay with Transaction API';

// Error
$_['error_permission']      = 'Warning: You do not have permission to modify payment Transaction API!';
$_['error_settings_txnapi'] = 'Warning: Your Transaction API settings were not saved!';

$_['error_live_credentials_txnapi_public_key']            = 'Please provide Live Credentials.';
$_['error_sandbox_credentials_txnapi_public_key']         = 'Please provide Sandbox Credentials.';
$_['error_live_credentials_txnapi_api_key']               = 'Please provide Live Credentials.';
$_['error_sandbox_credentials_txnapi_api_key']            = 'Please provide Sandbox Credentials.';
$_['error_live_credentials_txnapi_api_secret']            = 'Please provide Live Credentials.';
$_['error_sandbox_credentials_txnapi_api_secret']         = 'Please provide Sandbox Credentials.';
$_['error_live_credentials_txnapi_account_credential']    = 'Please provide Live Credentials.';
$_['error_sandbox_credentials_txnapi_account_credential'] = 'Please provide Sandbox Credentials.';

// Success
$_['success_settings_txnapi'] = 'Your Transaction API settings were saved!';
