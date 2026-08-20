<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Unified Payments';

// Tab
$_['tab_ucp']            = 'Unified Payments';
$_['tab_payment']        = 'Payment';
$_['tab_googlepay']      = 'Google Pay';
$_['tab_applepay']       = 'Apple Pay';
$_['tab_clicktopay']     = 'Click To Pay';
$_['tab_affirm']         = 'Affirm';
$_['tab_klarna']         = 'Klarna';
$_['tab_clearpay']       = 'Clearpay';
$_['tab_paypal']         = 'PayPal';
$_['tab_openbanking']    = 'Bank Payment';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = 'Extensions';
$_['text_edit']               = 'Edit GlobalPayments - Unified Payments';
$_['text_success']            = 'Success: You have modified GlobalPayments - Unified Payments account details!';
$_['text_select_all']         = 'Select All';
$_['text_unselect_all']       = 'Unselect All';
$_['text_hpp_installments_title'] = 'Installments Payment Options';
$_['text_hpp_installments_subtitle'] = 'Installments may appear during payment when supported by your Global Payments account and the customer\'s card.</br>Hosted Payment Page Installments is an account‑level feature. This payment option will only display during checkout if it has been enabled on your Global Payments account.</br><b>Contact Global Payments support to learn more about enabling Installments.</b>';
$_['text_hpp_installments_filtering_title'] = "Installments Payment Filtering Options";

// Label
$_['label_enabled']               = 'Enable/Disable';
$_['label_title']                 = 'Title';
$_['label_is_production']         = 'Live Mode';
$_['label_region']                = 'Transaction Region';
$_['label_service_url']           = 'API Endpoint';
$_['label_app_id']                = 'Live App Id';
$_['label_app_key']               = 'Live App Key';
$_['label_account_name']          = 'Live Account Name';
$_['label_sandbox_app_id']        = 'Sandbox App Id';
$_['label_sandbox_app_key']       = 'Sandbox App Key';
$_['label_sandbox_account_name']  = 'Sandbox Account Name';
$_['credentials_check']           = 'Credentials check';
$_['label_debug']                 = 'Enable Logging';
$_['label_contact_url']           = 'Contact Url';
$_['label_payment_action']        = 'Payment Action';
$_['label_allow_card_saving']     = 'Allow Card Saving';
$_['label_txn_descriptor']        = 'Order Transaction Descriptor';
$_['label_enable_three_d_secure'] = 'Enable 3DSecure';
$_['label_integration_type']      = 'Integration Type';
$_['label_enable_installments']   = 'Enable Installments';
$_['label_enable_visa_installments'] = 'Enable Visa Installments';
$_['label_visa_installments_funding_mode'] = 'Visa Installments Funding Mode';
$_['label_visa_installments_max_time_unit_number'] = 'Visa Installments Max Time Unit Number';
$_['label_visa_installments_max_amount'] = 'Visa Installments Max Amount';
$_['label_enable_dcc']            = 'Enable DCC';
$_['label_sort_order']            = 'Sort Order';
$_['label_blik']                  = 'Enable Blik Payment';
$_['label_open_banking']          = 'Enable Open Banking Payment';
$_['label_hpp_installments_plan_types'] = 'Shown Installments Plan Types';
$_['label_hpp_installments_plan_duration'] = 'Shown Installment Plans Maxium duration (Only applicable for Merchant funded Installment plans)';
$_['label_hpp_installments_plan_threshold'] = 'Installments Amount Threshold';

// Help
$_['help_title']                 = 'This controls the title which the user sees during checkout.';
$_['help_is_production']         = 'Get your App Id and App Key from your <a href="https://developer.globalpay.com/user/register" target="_blank">Global Payments Developer Account</a>. ' .
                                 'Please follow the instructions provided in the plugin description.<br/>' .
                                 'When you are ready for Live, please contact <a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">support</a> to get you live credentials.';
$_['help_region']                = 'Select where transactions are processed. This controls the GP API host for sandbox and live transactions.';
$_['help_for_credentials_check'] = 'Please note that Payment Methods will not appear on checkout if the credentials are not correct.';
$_['help_credentials_check']     = 'Make a request to the Unified Payments server to check App Id and App Key credentials.';
$_['help_debug']                 = 'Log all request to and from gateway. This can also log private data and should only be enabled in a development or stage environment.';
$_['help_contact_url']           = 'A link to an About or Contact page on your website with customer care information (maxLength: 256).';
$_['help_payment_action']        = 'Choose whether you wish to capture funds immediately or authorize payment only for a delayed capture.';
$_['help_allow_card_saving']     = 'Note: to use the card saving feature, you must have multi-use token support enabled on your account. Please contact <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">support</a> with any questions regarding this option.';
$_['help_txn_descriptor']        = 'During a Capture or Authorize payment action, this value will be passed along as the transaction-specific descriptor listed on the customer\'s bank account (maxLength: 25).';
$_['help_txn_descriptor_note']   = 'Please contact <a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">support</a> with any questions regarding this option.';
$_['help_account_name']          = 'Specify which account to use when processing a transaction. Default account will be used if this is not specified. <br>For assistance locating your account name, please contact our <a href="https://developer.globalpay.com/support/integration-support" target="_blank">Integration Support</a> Team based on location';
$_['help_integration_type']      = 'Select whether your payment form appears on the checkout page or redirects to a hosted page (hosted simplifies enablement of Apple and Google pay).';
$_['help_enable_installments']   = 'Enable Installments payment option for eligible transactions.';
$_['help_enable_visa_installments'] = 'Enable Visa installment payment option for customers';
$_['help_visa_installments_funding_mode'] = 'Set the funding mode for Visa installment payments (for use in Drop-in UI mode only, default is "Any")';
$_['help_visa_installments_max_time_unit_number'] = 'Set the maximum time unit number for Visa installment payments.';
$_['help_visa_installments_max_amount'] = 'Set the maximum amount for Visa installment payments.';
$_['help_hpp_wallets']           = 'HPP Wallets and APMs';
$_['help_hpp_wallets_description'] = 'Select the digital wallets and alternative payment methods you want to accept via Hosted Payment Page. These options are only available when using Hosted Payment Page integration type.';
$_['help_hpp_installments_plan_types'] = 'Limit Shown Plans By Type';
$_['help_hpp_installments_plan_types_tooltip'] = 'Used to filter installment plans based on plan type. MERCHANT_FUNDED (if sent, will return merchant funded plans only) CONSUMER_FUNDED (if sent, will return consumer funded plans only) HYBRID_FUNDED (if sent, will return both merchant and consumer funded plans) BILATERAL (if sent, will return BILATERAL plans only) ANY (if sent, will return all available plans) (default) Note: If not present, request will be sent with default value.';
$_['help_hpp_installments_plan_duration'] = 'Used to retrieve installment plans with specific tenure. Applicable to merchant-funded plans only.';
$_['help_hpp_installments_plan_duration_tooltip'] = 'Used to retrieve installment plans with specific tenure. Applicable to merchant-funded plans only. Example: max_term_months_merchant_funded = 12 — Means that only plans with tenure ≤ 12 months will be retrieved. Merchant-funded plans with tenure > 12 months will not be returned. Default: 1000 Note: If not present, request will be sent with default value. Used to determine whether to return the longest or shortest tenure/duration plans depending on the amount sent.';
$_['help_hpp_installments_plan_threshold'] = 'Used to determine whether to return the longest or shortest tenure/duration plans depending on the amount sent. Setting 0 or nothing will disable this feature and plans will be shown in default sort order
The amount should be sent in the smallest unit of the required currency. Example: 2000 = £20.00.';
$_['help_hpp_installments_plan_threshold_tooltip'] = 'The amount should be sent in the smallest unit of the required currency. Example: 2000 = €20.00 Ex value sent 50000 will mean that: For transactions with an amount less or equal than €500.00, the plans with the shortest tenure will be displayed first. For transactions with an amount greater than €500.00, the plans with the longest tenure will be displayed first. Note: If not present, request will be sent with default value: plans with the longest tenure.';
$_['help_enable_dcc']            = 'Enable or disable Dynamic Currency Conversion (DCC) for transactions processed through Global Payments. Only available with Hosted Payment Page integration.';

// Entry
$_['entry_enabled']                  = 'Enable Gateway';
$_['entry_is_production']            = 'Live Mode';
$_['entry_region_global']            = 'Global (default)';
$_['entry_region_europe']            = 'Europe';
$_['entry_credentials_check']        = 'Credentials Check';
$_['entry_debug']                    = 'Enable Logging';
$_['entry_payment_action_authorize'] = 'Authorize only';
$_['entry_payment_action_charge']    = 'Authorize + Capture';
$_['entry_allow_card_saving']        = 'Allow Card Saving';
$_['entry_visa_installments_funding_mode_any'] = 'Any';
$_['entry_visa_installments_funding_mode_consumer_funded'] = 'Consumer funded';
$_['entry_visa_installments_funding_mode_merchant_funded'] = 'Merchant funded';
$_['entry_visa_installments_funding_mode_hybrid_funded'] = 'Hybrid funded';
$_['entry_visa_installments_funding_mode_bilateral'] = 'Bilateral';
$_['entry_integration_type_dropin_ui'] = 'Drop-in UI';
$_['entry_integration_type_hosted_payment'] = 'Hosted Payment Page';
$_['entry_hpp_installments_plan_type_any'] = 'Show All Plans';
$_['entry_hpp_installments_plan_type_customer_funded'] = 'Only Show Customer Funded Installment Plans';
$_['entry_hpp_installments_plan_type_merchant_funded'] = 'Only Show Merchant Funded Installment Plans';
$_['entry_hpp_installments_plan_type_hybrid_funded'] = 'Only Show Hybrid Funded Installment Plans';
$_['entry_hpp_installments_plan_type_bilateral'] = 'Only Show Bilateral Funded Installment Plans';
$_['entry_hpp_installments_plan_duration_any'] = 'Show All plans';
$_['entry_hpp_installments_plan_duration_6_month'] = 'Only Show 6 Months plans';
$_['entry_hpp_installments_plan_duration_12_month'] = 'Only Show 12 Months plans';
$_['entry_hpp_installments_plan_duration_24_month'] = 'Only Show 24 Months plans';
$_['entry_hpp_installments_plan_duration_32_month'] = 'Only Show 32 Months plans';

// Label
$_['label_hpp_wallets'] = 'Accepted Wallets and APMs';
$_['label_hpp_eraty'] = 'HPP: eRaty';

// Help eRaty
$_['help_hpp_eraty_tooltip'] = 'This payment method must be enabled at the merchant account level.';
$_['help_hpp_eraty_description'] = 'eRaty (Installment Financing) is available via Hosted Checkout for eligible merchants.';

// Placeholder
$_['placeholder_title'] = 'Credit or Debit Card';

// Error
$_['error_permission']                  = 'Warning: You do not have permission to modify payment Unified Payments!';
$_['error_gateway_not_enabled']         = 'Gateway not enabled. Please check account details!';
$_['error_settings_ucp']                = 'Warning: Your Unified Payments settings were not saved!';
$_['error_contact_url']                 = 'Please provide a Contact Url (maxLength: 256).';
$_['error_live_credentials_app_id']     = 'Please provide Live Credentials.';
$_['error_live_credentials_app_key']    = 'Please provide Live Credentials.';
$_['error_live_credentials_account_name'] = 'Please provide Live Account Name.';
$_['error_sandbox_credentials_app_id']  = 'Please provide Sandbox Credentials.';
$_['error_sandbox_credentials_app_key'] = 'Please provide Sandbox Credentials.';
$_['error_sandbox_credentials_account_name'] = 'Please provide Sandbox Account Name.';
$_['error_txn_descriptor']              = 'Please provide Order Transaction Descriptor (maxLength: 25).';
$_['error_request']                     = 'Unable to perform request. Invalid data.';

// Success
$_['success_settings_ucp']      = 'Your Unified Payments settings were saved!';
$_['success_credentials_check'] = 'Your credentials were successfully confirmed!';

// Alert
$_['alert_credentials_check'] = 'Please be sure that you have filled AppId and AppKey fields!';

$_['text_success_full_refund']               = 'Payment fully refunded successfully!';
$_['text_success_partial_refund']            = 'Payment partially refunded successfully!';
$_['text_refunded_comment']                  = 'Order has been fully refunded .';

// 3D secure
$_['three_d_secure_required_display_text']      = '3D Secure is required in your country and is enabled automatically';
$_['three_d_secure_not_required_display_text']  = '3D Secure is optional in your country';
