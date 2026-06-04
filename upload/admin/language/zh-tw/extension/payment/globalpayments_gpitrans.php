<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Gpi 交易';

// Tab
$_['tab_gpitrans'] = '整合支付';
$_['tab_payment']  = '付款';
$_['tab_txnapi']   = '交易 API';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = '擴充功能';
$_['text_edit']               = '編輯 GlobalPayments - 交易 API';
$_['text_success']            = '成功：您已修改 GlobalPayments - 整合支付帳戶詳細資料！';
$_['text_select_all']         = '全選';
$_['text_unselect_all']       = '取消全選';

// Label
$_['label_enabled']           = '啟用/停用';
$_['label_title']             = '標題';
$_['label_is_production']     = '正式環境模式';
$_['label_app_id']            = '正式環境 App Id';
$_['label_app_key']           = '正式環境 App Key';
$_['label_sandbox_app_id']    = '沙盒環境 App Id';
$_['label_sandbox_app_key']   = '沙盒環境 App Key';
$_['credentials_check']       = '憑證檢查';
$_['label_debug']             = '啟用日誌記錄';
$_['label_contact_url']       = '聯絡網址';
$_['label_payment_action']    = '付款操作';
$_['label_allow_card_saving'] = '允許儲存卡片';
$_['label_txn_descriptor']    = '訂單交易描述';
$_['entry_sort_order']        = '排序順序';

// Help
$_['help_title']                 = '此設定控制使用者在結帳時看到的標題。';
$_['help_is_production']         = '從您的 <a href="https://developer.globalpay.com/user/register" target="_blank">Global Payments 開發者帳戶</a>取得您的 App Id 和 App Key。' .
                                 '請按照外掛描述中提供的說明操作。<br/>' .
                                 '當您準備好上線時，請聯絡<a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">支援團隊</a>以取得正式環境憑證。';
$_['help_for_credentials_check'] = '請注意，如果憑證不正確，付款方式將不會在結帳時顯示。';
$_['help_credentials_check']     = '向整合支付伺服器發送請求以檢查 App Id 和 App Key 憑證。';
$_['help_debug']                 = '記錄所有發送到閘道和從閘道接收的請求。這也可能記錄私人資料，應僅在開發或測試環境中啟用。';
$_['help_contact_url']           = '連結到您網站上的關於或聯絡頁面，包含客戶服務資訊（最大長度：256）。';
$_['help_payment_action']        = '選擇您希望立即擷取資金還是僅授權付款以延遲擷取。';
$_['help_allow_card_saving']     = '注意：要使用卡片儲存功能，您必須在帳戶上啟用多用途令牌支援。如有任何關於此選項的問題，請聯絡<a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">支援團隊</a>。';
$_['help_txn_descriptor']        = '在擷取或授權付款操作期間，此值將作為交易特定描述傳遞，列在客戶的銀行帳戶上（最大長度：25）。';
$_['help_txn_descriptor_note']   = '如有任何關於此選項的問題，請聯絡<a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">支援團隊</a>。';

// Entry
$_['entry_enabled']                  = '啟用閘道';
$_['entry_is_production']            = '正式環境模式';
$_['entry_credentials_check']        = '憑證檢查';
$_['entry_debug']                    = '啟用日誌記錄';
$_['entry_payment_action_authorize'] = '僅授權';
$_['entry_payment_action_charge']    = '授權 + 擷取';
$_['entry_allow_card_saving']        = '允許儲存卡片';

// Placeholder
$_['placeholder_title'] = '信用卡或金融卡';

// Error
$_['error_permission']                  = '警告：您沒有權限修改整合支付付款設定！';
$_['error_gateway_not_enabled']         = '閘道未啟用。請檢查帳戶詳細資料！';
$_['error_settings_ucp']                = '警告：您的整合支付設定未儲存！';
$_['error_contact_url']                 = '請提供聯絡網址（最大長度：256）。';
$_['error_live_credentials_app_id']     = '請提供正式環境憑證。';
$_['error_live_credentials_app_key']    = '請提供正式環境憑證。';
$_['error_sandbox_credentials_app_id']  = '請提供沙盒環境憑證。';
$_['error_sandbox_credentials_app_key'] = '請提供沙盒環境憑證。';
$_['error_txn_descriptor']              = '請提供訂單交易描述（最大長度：25）。';
$_['error_request']                     = '無法執行請求。資料無效。';

// Success
$_['success_settings_ucp']      = '您的整合支付設定已儲存！';
$_['success_settings_gpitrans'] = '您的整合支付設定已儲存！';
$_['success_credentials_check'] = '您的憑證已成功確認！';

// Alert
$_['alert_credentials_check'] = '請確保您已填寫 AppId 和 AppKey 欄位！';
