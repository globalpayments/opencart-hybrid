<?php
// Heading
$_['heading_title'] = 'GlobalPayments - 交易 API';

// Text
$_['text_globalpayments_txnapi'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']           = '啟用/停用';
$_['label_title']             = '標題';
$_['label_is_production']     = '正式環境模式';
$_['label_region']            = '地區';
$_['label_debug']             = '啟用日誌記錄';
$_['label_payment_action']    = '付款操作';
$_['label_allow_card_saving'] = '允許儲存卡片';

$_['label_txnapi_public_key']                 = '正式環境公鑰';
$_['label_sandbox_txnapi_public_key']         = '沙盒環境公鑰';
$_['label_txnapi_api_key']                    = '正式環境 API 金鑰';
$_['label_sandbox_txnapi_api_key']            = '沙盒環境 API 金鑰';
$_['label_txnapi_api_secret']                 = '正式環境 API 密鑰';
$_['label_sandbox_txnapi_api_secret']         = '沙盒環境 API 密鑰';
$_['label_txnapi_account_credential']         = '正式環境帳戶憑證';
$_['label_sandbox_txnapi_account_credential'] = '沙盒環境帳戶憑證';

// Help
$_['help_title']             = '此設定控制使用者在結帳時看到的標題。';
$_['help_debug']             = '記錄所有發送到閘道和從閘道接收的請求。這也可能記錄私人資料，應僅在開發或測試環境中啟用。';
$_['help_payment_action']    = '選擇您希望立即擷取資金還是僅授權付款以延遲擷取。';
$_['help_allow_card_saving'] = '注意：要使用卡片儲存功能，您必須在帳戶上啟用多用途令牌支援。如有任何關於此選項的問題，請聯絡<a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">支援團隊</a>。';

// Entry
$_['entry_enabled']                  = '啟用交易 API';
$_['entry_is_production']            = '正式環境模式';
$_['entry_us']                       = '美國';
$_['entry_ca']                       = '加拿大';
$_['entry_debug']                    = '啟用日誌記錄';
$_['entry_payment_action_authorize'] = '僅授權';
$_['entry_payment_action_charge']    = '授權 + 擷取';
$_['entry_allow_card_saving']        = '允許儲存卡片';

// Placeholder
$_['placeholder_txnapi_title'] = '使用交易 API 付款';

// Error
$_['error_permission']      = '警告：您沒有權限修改交易 API 付款設定！';
$_['error_settings_txnapi'] = '警告：您的交易 API 設定未儲存！';

$_['error_live_credentials_txnapi_public_key']            = '請提供正式環境憑證。';
$_['error_sandbox_credentials_txnapi_public_key']         = '請提供沙盒環境憑證。';
$_['error_live_credentials_txnapi_api_key']               = '請提供正式環境憑證。';
$_['error_sandbox_credentials_txnapi_api_key']            = '請提供沙盒環境憑證。';
$_['error_live_credentials_txnapi_api_secret']            = '請提供正式環境憑證。';
$_['error_sandbox_credentials_txnapi_api_secret']         = '請提供沙盒環境憑證。';
$_['error_live_credentials_txnapi_account_credential']    = '請提供正式環境憑證。';
$_['error_sandbox_credentials_txnapi_account_credential'] = '請提供沙盒環境憑證。';

// Success
$_['success_settings_txnapi'] = '您的交易 API 設定已儲存！';
