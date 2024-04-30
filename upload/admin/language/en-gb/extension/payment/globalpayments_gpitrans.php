<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Gpi Transaction';

// Tab
$_['tab_gpitrans'] = 'Unified Payments';
$_['tab_payment']  = 'Payment';
$_['tab_txnapi']   = 'Transaction API';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = 'Extensions';
$_['text_edit']               = 'Edit GlobalPayments - Transaction API';
$_['text_success']            = 'Success: You have modified GlobalPayments - Unified Payments account details!';
$_['text_select_all']         = 'Select All';
$_['text_unselect_all']       = 'Unselect All';

// Label
$_['label_enabled']           = 'Enable/Disable';
$_['label_title']             = 'Title';
$_['label_is_production']     = 'Live Mode';
$_['label_app_id']            = 'Live App Id';
$_['label_app_key']           = 'Live App Key';
$_['label_sandbox_app_id']    = 'Sandbox App Id';
$_['label_sandbox_app_key']   = 'Sandbox App Key';
$_['credentials_check']       = 'Credentials check';
$_['label_debug']             = 'Enable Logging';
$_['label_contact_url']       = 'Contact Url';
$_['label_payment_action']    = 'Payment Action';
$_['label_allow_card_saving'] = 'Allow Card Saving';
$_['label_txn_descriptor']    = 'Order Transaction Descriptor';
$_['entry_sort_order']        = 'Sort Order';

// Help
$_['help_title']                 = 'This controls the title which the user sees during checkout.';
$_['help_is_production']         = 'Get your App Id and App Key from your <a href="https://developer.globalpay.com/user/register" target="_blank">Global Payments Developer Account</a>. ' .
                                 'Please follow the instructions provided in the plugin description.<br/>' .
                                 'When you are ready for Live, please contact <a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">support</a> to get you live credentials.';
$_['help_for_credentials_check'] = 'Please note that Payment Methods will not appear on checkout if the credentials are not correct.';
$_['help_credentials_check']     = 'Make a request to the Unified Payments server to check App Id and App Key credentials.';
$_['help_debug']                 = 'Log all request to and from gateway. This can also log private data and should only be enabled in a development or stage environment.';
$_['help_contact_url']           = 'A link to an About or Contact page on your website with customer care information (maxLength: 256).';
$_['help_payment_action']        = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_allow_card_saving']     = 'Note: to use the card saving feature, you must have multi-use token support enabled on your account. Please contact <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">support</a> with any questions regarding this option.';
$_['help_txn_descriptor']        = 'During a Capture or Authorize payment action, this value will be passed along as the transaction-specific descriptor listed on the customer\'s bank account (maxLength: 25).';
$_['help_txn_descriptor_note']   = 'Please contact <a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">support</a> with any questions regarding this option.';

// Entry
$_['entry_enabled']                  = 'Enable Gateway';
$_['entry_is_production']            = 'Live Mode';
$_['entry_credentials_check']        = 'Credentials Check';
$_['entry_debug']                    = 'Enable Logging';
$_['entry_payment_action_authorize'] = 'Authorize only';
$_['entry_payment_action_charge']    = 'Authorize + Capture';
$_['entry_allow_card_saving']        = 'Allow Card Saving';

// Placeholder
$_['placeholder_title'] = 'Credit or Debit Card';

// Error
$_['error_permission']                  = 'Warning: You do not have permission to modify payment Unified Payments!';
$_['error_gateway_not_enabled']         = 'Gateway not enabled. Please check account details!';
$_['error_settings_ucp']                = 'Warning: Your Unified Payments settings were not saved!';
$_['error_contact_url']                 = 'Please provide a Contact Url (maxLength: 256).';
$_['error_live_credentials_app_id']     = 'Please provide Live Credentials.';
$_['error_live_credentials_app_key']    = 'Please provide Live Credentials.';
$_['error_sandbox_credentials_app_id']  = 'Please provide Sandbox Credentials.';
$_['error_sandbox_credentials_app_key'] = 'Please provide Sandbox Credentials.';
$_['error_txn_descriptor']              = 'Please provide Order Transaction Descriptor (maxLength: 25).';
$_['error_request']                     = 'Unable to perform request. Invalid data.';

// Success
$_['success_settings_ucp']      = 'Your Unified Payments settings were saved!';
$_['success_settings_gpitrans'] = 'Your Unified Payments settings were saved!';
$_['success_credentials_check'] = 'Your credentials were successfully confirmed!';

// Alert
$_['alert_credentials_check'] = 'Please be sure that you have filled AppId and AppKey fields!';
