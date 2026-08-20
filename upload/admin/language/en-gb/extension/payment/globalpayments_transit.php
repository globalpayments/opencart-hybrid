<?php
// Heading
$_['heading_title'] = 'GlobalPayments - TransIT';

// Text
$_['text_extension'] = 'Extensions';
$_['text_globalpayments_transit'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_edit'] = 'Edit GlobalPayments TransIT Gateway';
$_['text_success'] = 'Success: You have modified GlobalPayments TransIT payment gateway settings!';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';

// Entry
$_['entry_status'] = 'Status';
$_['entry_title'] = 'Title';
$_['entry_is_production'] = 'Live Mode';
$_['entry_merchant_id'] = 'Live Merchant ID';
$_['entry_user_id'] = 'Live MultiPass User ID';
$_['entry_password'] = 'Live Password';
$_['entry_device_id'] = 'Live Device ID';
$_['entry_tsep_device_id'] = 'Live TSEP Device ID';
$_['entry_transaction_key'] = 'Live Transaction Key';
$_['entry_sandbox_merchant_id'] = 'Sandbox Merchant ID';
$_['entry_sandbox_user_id'] = 'Sandbox MultiPass User ID';
$_['entry_sandbox_password'] = 'Sandbox Password';
$_['entry_sandbox_device_id'] = 'Sandbox Device ID';
$_['entry_sandbox_tsep_device_id'] = 'Sandbox TSEP Device ID';
$_['entry_sandbox_transaction_key'] = 'Sandbox Transaction Key';
$_['entry_allow_card_saving'] = 'Allow Card Saving';
$_['entry_payment_action'] = 'Payment Action';
$_['entry_payment_action_authorize'] = 'Authorize only';
$_['entry_payment_action_charge'] = 'Authorize + Capture';
$_['entry_transaction_descriptor'] = 'Order Transaction Descriptor';
$_['entry_debug'] = 'Enable Logging';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_check_avs_cvn'] = 'Check AVS CVN';
$_['entry_avs_reject_conditions'] = 'AVS Reject Conditions';
$_['entry_cvn_reject_conditions'] = 'CVN Reject Conditions';

// AVS Response Codes
$_['avs_code_a'] = 'A - Address matches, zip No Match';
$_['avs_code_b'] = 'B - Address match, Zip not verified';
$_['avs_code_c'] = 'C - Address and zip mismatch';
$_['avs_code_d'] = 'D - Address and zip match';
$_['avs_code_g'] = 'G - Address not verified for International transaction';
$_['avs_code_i'] = 'I - AVS not verified for International transaction';
$_['avs_code_m'] = 'M - Street address and postal code matches';
$_['avs_code_n'] = 'N - Neither address or zip code match';
$_['avs_code_p'] = 'P - Address and Zip not verified';
$_['avs_code_r'] = 'R - Retry - system unable to respond';
$_['avs_code_s'] = 'S - Master / Amex card AVS not supported';
$_['avs_code_u'] = 'U - Visa / Discover card AVS not supported';
$_['avs_code_w'] = 'W - Master / Amex card 9-digit zip code match, address no match';
$_['avs_code_x'] = 'X - Master / Amex card 5-digit zip code and address match';
$_['avs_code_y'] = 'Y - Visa / Discover card 5-digit zip code and address match';
$_['avs_code_z'] = 'Z - Visa / Discover card 9-digit zip code match, address no match';

// CVN Response Codes
$_['cvn_code_n'] = 'N - Not Matching';
$_['cvn_code_p'] = 'P - Not Processed';
$_['cvn_code_s'] = 'S - Result not present';
$_['cvn_code_u'] = 'U - Issuer not certified';
$_['cvn_code_question'] = '? - CVV unrecognized';

// Help
$_['help_title'] = 'This controls the title which the user sees during checkout.';
$_['help_is_production'] = 'Get your credentials from your GlobalPayments TransIT account. Note: only needed to create transaction key initially. When you are ready for Live, please contact <a href="https://developer.globalpayments.com/support/integration-support" target="_blank" rel="noopener noreferrer">support</a> to get your live credentials.';
$_['help_payment_action'] = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_transaction_descriptor'] = 'This value will be passed along as the transaction-specific descriptor listed on the customer\'s bank account. Maximum 18 characters.';
$_['help_allow_card_saving'] = 'Note: to use the card saving feature, you must have multi-use token support enabled on your account. Please contact <a href="https://developer.globalpayments.com/support/integration-support" target="_blank" rel="noopener noreferrer">support</a> with any questions regarding this option.';
$_['help_debug'] = 'Log all requests to and from the TransIT gateway. This can also log private data and should only be enabled in a development or stage environment.';
$_['help_check_avs_cvn'] = 'This will check AVS/CVN result codes and reverse transaction.';
$_['help_avs_reject_conditions'] = 'Choose for which AVS result codes, the transaction must be auto reversed.';
$_['help_cvn_reject_conditions'] = 'Choose for which CVN result codes, the transaction must be auto reversed.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify GlobalPayments TransIT Gateway!';
$_['error_merchant_id'] = 'Live Merchant ID is required when Live Mode is enabled!';
$_['error_device_id'] = 'Live Device ID is required when Live Mode is enabled!';
$_['error_sandbox_merchant_id'] = 'Please provide valid Sandbox Merchant ID';
$_['error_sandbox_device_id'] = 'Please provide valid Sandbox Device ID';

// Button
$_['button_save'] = 'Save';
$_['button_cancel'] = 'Cancel';