<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Genius';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified GlobalPayments Genius payment module!';
$_['text_edit'] = 'Edit GlobalPayments Genius';
$_['text_globalpayments_genius'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_authorize'] = 'Authorize';
$_['text_capture'] = 'Authorize + Capture';

// Entry
$_['entry_status'] = 'Enable Gateway';
$_['entry_title'] = 'Title';
$_['entry_live_mode'] = 'Live Mode';
$_['entry_sandbox_merchant_name'] = 'Sandbox Merchant Name';
$_['entry_sandbox_merchant_site_id'] = 'Sandbox Merchant Site ID';
$_['entry_sandbox_merchant_key'] = 'Sandbox Merchant Key';
$_['entry_sandbox_web_api_key'] = 'Sandbox Web API Key';
$_['entry_live_merchant_name'] = 'Live Merchant Name';
$_['entry_live_merchant_site_id'] = 'Live Merchant Site ID';
$_['entry_live_merchant_key'] = 'Live Merchant Key';
$_['entry_live_web_api_key'] = 'Live Web API Key';
$_['entry_payment_action'] = 'Payment Action';
$_['entry_allow_card_saving'] = 'Allow Card Saving';
$_['entry_txn_descriptor'] = 'Order Transaction Descriptor';
$_['entry_check_avs_cvn'] = 'Check AVS/CVN';
$_['entry_debug'] = 'Enable Debug Logging';
$_['entry_avs_reject_conditions'] = 'AVS Reject Conditions';
$_['entry_cvn_reject_conditions'] = 'CVN Reject Conditions';
$_['entry_sort_order'] = 'Sort Order';

// Help
$_['help_title'] = 'This will be displayed at checkout';
$_['help_live_mode'] = 'Get your credentials from your GlobalPayments Genius account';
$_['help_payment_action'] = 'Choose whether Genius should authorize only or authorize and capture the payment during checkout.';
$_['help_allow_card_saving'] = 'Note: to use the card saving feature, you must have multi-use token support enabled on your account. Please contact <a href="mailto:support@e-hps.com">support</a> with any questions regarding this option.';
$_['help_txn_descriptor'] = 'During a Capture or Authorize payment action, this value will be present along in the transaction-specific descriptor field on the customer\'s hard account. Please contact <a href="mailto:support@e-hps.com">support</a> with any questions regarding this option (maximum: 25).';
$_['help_check_avs_cvn'] = 'This will check AVS/CVN result codes and reverse transaction.';
$_['help_debug'] = 'Write Genius diagnostic messages to OpenCart logs.';
$_['help_avs_reject_conditions'] = 'Choose for which AVS result codes, the transaction must be auto reversed.';
$_['help_cvn_reject_conditions'] = 'Choose for which CVN result codes, the transaction must be auto reversed.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify GlobalPayments Genius payment module!';
$_['error_sandbox_merchant_name'] = 'Sandbox Merchant Name is required!';
$_['error_sandbox_merchant_site_id'] = 'Sandbox Merchant Site ID is required!';
$_['error_sandbox_merchant_key'] = 'Sandbox Merchant Key is required!';
$_['error_sandbox_web_api_key'] = 'Sandbox Web API Key is required!';
$_['error_live_merchant_name'] = 'Live Merchant Name is required!';
$_['error_live_merchant_site_id'] = 'Live Merchant Site ID is required!';
$_['error_live_merchant_key'] = 'Live Merchant Key is required!';
$_['error_live_web_api_key'] = 'Live Web API Key is required!';
