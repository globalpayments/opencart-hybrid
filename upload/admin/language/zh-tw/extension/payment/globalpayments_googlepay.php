<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Google Pay';

// Text
$_['text_globalpayments_googlepay'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']                     = '啟用/停用';
$_['label_title']                       = '標題';
$_['label_payment_action']              = '付款操作';
$_['label_gp_merchant_id']              = 'Global Payments 客戶端 ID';
$_['label_merchant_id']                 = 'Google 商家 ID';
$_['label_merchant_name']               = 'Google 商家顯示名稱';
$_['label_accepted_cards']              = '接受的卡片';
$_['label_googlepay_button_color']      = '按鈕顏色';
$_['label_allowed_card_auth_methods']   = '允許的卡片驗證方式';
$_['label_sort_order']                  = '排序順序';

// Help
$_['help_title']          = '此設定控制使用者在結帳時看到的標題。';
$_['help_payment_action'] = '選擇您希望立即擷取資金還是僅授權付款以延遲擷取。';
$_['help_gp_merchant_id'] = '您的 Global Payments 提供的客戶端 ID。';
$_['help_merchant_id']    = '您的 Google 提供的商家 ID。';
$_['help_merchant_name']  = '在 Google Pay 對話框中顯示給客戶。';

// Entry
$_['entry_enabled']                      = '啟用 Google Pay';
$_['entry_payment_action_authorize']     = '僅授權';
$_['entry_payment_action_charge']        = '授權 + 擷取';
$_['entry_card_type_visa']               = 'Visa';
$_['entry_card_type_mastercard']         = 'MasterCard';
$_['entry_card_type_amex']               = 'AMEX';
$_['entry_card_type_discover']           = 'Discover';
$_['entry_card_type_jcb']                = 'JCB';
$_['entry_googlepay_button_color_white'] = '白色';
$_['entry_googlepay_button_color_black'] = '黑色';
$_['entry_method_pan_only']              = 'PAN_ONLY';
$_['entry_method_cryptogram_3ds']        = 'CRYPTOGRAM_3DS';

$_['label_allowed_card_auth_methods_tooltip']='PAN_ONLY：此驗證方式與儲存在使用者 Google 帳戶中的付款卡相關聯。
CRYPTOGRAM_3DS：此驗證方式與作為 Android 裝置令牌儲存的卡片相關聯。

PAN_ONLY 可能會暴露 FPAN，這需要額外的 SCA 步驟進行 3DS 檢查。目前，Global Payments 不支援使用 FPAN 的 Google Pay SCA 驗證。為獲得最佳接受率，我們建議您僅提供 CRYPTOGRAM_3DS 選項。';

// Placeholder
$_['placeholder_googlepay_title'] = '使用 Google Pay 付款';

// Error
$_['error_permission']                          = '警告：您沒有權限修改 Google Pay 付款設定！';
$_['error_settings_googlepay']                  = '警告：您的 Google Pay 設定未儲存！';
$_['error_gp_merchant_id']                      = '請提供 Global Payments 客戶端 ID。';
$_['error_googlepay_accepted_cards']            = '請提供接受的卡片。';
$_['error_googlepay_allowed_card_auth_methods'] = '請至少提供一種方式。';

// Success
$_['success_settings_googlepay'] = '您的 Google Pay 設定已儲存！';
