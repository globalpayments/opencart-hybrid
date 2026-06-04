<?php
// Heading
$_['heading_title'] = 'GlobalPayments - 銀行付款';

// Text
$_['text_globalpayments_openbanking'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = '啟用/停用';
$_['label_title']          = '標題';
$_['label_payment_action'] = '付款操作';
$_['label_account_number'] = '帳戶號碼';
$_['label_account_name']   = '帳戶名稱';
$_['label_sort_code']      = '分行代碼';
$_['label_countries']      = '國家';
$_['label_iban']           = 'IBAN';
$_['label_currencies']     = '貨幣';
$_['label_sort_order']     = '排序順序';

// Help
$_['help_title']                      = '此設定控制使用者在結帳時看到的標題。';
$_['help_payment_action']             = '選擇您希望立即擷取資金還是僅授權付款以延遲擷取。';
$_['help_openbanking_account_number'] = '帳戶號碼，用於英國境內銀行轉帳（英國至英國銀行）。僅在帳戶未儲存銀行詳細資料時需要。';
$_['help_openbanking_account_name']   = '銀行帳戶上的個人或企業名稱。僅在帳戶未儲存銀行詳細資料時需要。';
$_['help_openbanking_sort_code']      = '識別帳戶的銀行和分行的六位數字。與帳戶號碼一起用於英國至英國銀行轉帳。僅在帳戶未儲存銀行詳細資料時需要。';
$_['help_openbanking_iban']           = '僅適用於歐元交易商家。';
$_['help_openbanking_countries']      = '允許您輸入國家或國家字串以限制向客戶顯示的內容。包含國家會覆蓋您的預設帳戶配置。<br/><br/>
	格式：以 | 分隔的 ISO 3166-2（兩個字元）代碼列表<br/><br/>
	範例：FR|GB|IE';
$_['help_openbanking_currencies']     = '注意：付款方式僅在結帳時對所選貨幣可用。';


// Entry
$_['entry_enabled']                      = '啟用銀行付款';
$_['entry_payment_action_authorize']     = '僅授權';
$_['entry_payment_action_charge']        = '授權 + 擷取';
$_['entry_gbp']                          = '英鎊';
$_['entry_eur']                          = '歐元';

// Placeholder
$_['placeholder_openbanking_title'] = '銀行付款';
$_['placeholder_title']             = '銀行付款';
$_['label_currencies_tooltip']      = '注意：付款方式僅在結帳時對所選貨幣顯示。';

// Error
$_['error_permission']              = '警告：您沒有權限修改銀行付款設定！';
$_['error_settings_openbanking']    = '警告：您的銀行付款設定未儲存！';
$_['error_openbanking_currencies']  = '請至少提供一種貨幣。';

// Success
$_['success_settings_openbanking'] = '您的銀行付款設定已儲存！';
