<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Google Pay';

// Text
$_['text_globalpayments_googlepay'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']                     = '启用/禁用';
$_['label_title']                       = '标题';
$_['label_payment_action']              = '付款操作';
$_['label_gp_merchant_id']              = 'Global Payments 客户端 ID';
$_['label_merchant_id']                 = 'Google 商户 ID';
$_['label_merchant_name']               = 'Google 商户显示名称';
$_['label_accepted_cards']              = '接受的卡片';
$_['label_googlepay_button_color']      = '按钮颜色';
$_['label_allowed_card_auth_methods']   = '允许的卡片验证方式';
$_['label_sort_order']                  = '排序顺序';

// Help
$_['help_title']          = '此设置控制用户在结账时看到的标题。';
$_['help_payment_action'] = '选择您希望立即捕获资金还是仅授权付款以延迟捕获。';
$_['help_gp_merchant_id'] = '您的 Global Payments 提供的客户端 ID。';
$_['help_merchant_id']    = '您的 Google 提供的商户 ID。';
$_['help_merchant_name']  = '在 Google Pay 对话框中显示给客户。';

// Entry
$_['entry_enabled']                      = '启用 Google Pay';
$_['entry_payment_action_authorize']     = '仅授权';
$_['entry_payment_action_charge']        = '授权 + 捕获';
$_['entry_card_type_visa']               = 'Visa';
$_['entry_card_type_mastercard']         = 'MasterCard';
$_['entry_card_type_amex']               = 'AMEX';
$_['entry_card_type_discover']           = 'Discover';
$_['entry_card_type_jcb']                = 'JCB';
$_['entry_googlepay_button_color_white'] = '白色';
$_['entry_googlepay_button_color_black'] = '黑色';
$_['entry_method_pan_only']              = 'PAN_ONLY';
$_['entry_method_cryptogram_3ds']        = 'CRYPTOGRAM_3DS';

$_['label_allowed_card_auth_methods_tooltip']='PAN_ONLY：此验证方式与存储在用户 Google 账户中的付款卡相关联。
CRYPTOGRAM_3DS：此验证方式与作为 Android 设备令牌存储的卡片相关联。

PAN_ONLY 可能会暴露 FPAN，这需要额外的 SCA 步骤进行 3DS 检查。目前，Global Payments 不支持使用 FPAN 的 Google Pay SCA 验证。为获得最佳接受率，我们建议您仅提供 CRYPTOGRAM_3DS 选项。';

// Placeholder
$_['placeholder_googlepay_title'] = '使用 Google Pay 付款';

// Error
$_['error_permission']                          = '警告：您没有权限修改 Google Pay 付款设置！';
$_['error_settings_googlepay']                  = '警告：您的 Google Pay 设置未保存！';
$_['error_gp_merchant_id']                      = '请提供 Global Payments 客户端 ID。';
$_['error_googlepay_accepted_cards']            = '请提供接受的卡片。';
$_['error_googlepay_allowed_card_auth_methods'] = '请至少提供一种方式。';

// Success
$_['success_settings_googlepay'] = '您的 Google Pay 设置已保存！';
