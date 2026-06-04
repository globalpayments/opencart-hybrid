<?php
// Heading
$_['heading_title'] = 'GlobalPayments - 银行付款';

// Text
$_['text_globalpayments_openbanking'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = '启用/禁用';
$_['label_title']          = '标题';
$_['label_payment_action'] = '付款操作';
$_['label_account_number'] = '账户号码';
$_['label_account_name']   = '账户名称';
$_['label_sort_code']      = '分行代码';
$_['label_countries']      = '国家';
$_['label_iban']           = 'IBAN';
$_['label_currencies']     = '货币';
$_['label_sort_order']     = '排序顺序';

// Help
$_['help_title']                      = '此设置控制用户在结账时看到的标题。';
$_['help_payment_action']             = '选择您希望立即捕获资金还是仅授权付款以延迟捕获。';
$_['help_openbanking_account_number'] = '账户号码，用于英国境内银行转账（英国至英国银行）。仅在账户未存储银行详情时需要。';
$_['help_openbanking_account_name']   = '银行账户上的个人或企业名称。仅在账户未存储银行详情时需要。';
$_['help_openbanking_sort_code']      = '识别账户的银行和分行的六位数字。与账户号码一起用于英国至英国银行转账。仅在账户未存储银行详情时需要。';
$_['help_openbanking_iban']           = '仅适用于欧元交易商户。';
$_['help_openbanking_countries']      = '允许您输入国家或国家字符串以限制向客户显示的内容。包含国家会覆盖您的默认账户配置。<br/><br/>
	格式：以 | 分隔的 ISO 3166-2（两个字符）代码列表<br/><br/>
	示例：FR|GB|IE';
$_['help_openbanking_currencies']     = '注意：付款方式仅在结账时对所选货币可用。';


// Entry
$_['entry_enabled']                      = '启用银行付款';
$_['entry_payment_action_authorize']     = '仅授权';
$_['entry_payment_action_charge']        = '授权 + 捕获';
$_['entry_gbp']                          = '英镑';
$_['entry_eur']                          = '欧元';

// Placeholder
$_['placeholder_openbanking_title'] = '银行付款';
$_['placeholder_title']             = '银行付款';
$_['label_currencies_tooltip']      = '注意：付款方式仅在结账时对所选货币显示。';

// Error
$_['error_permission']              = '警告：您没有权限修改银行付款设置！';
$_['error_settings_openbanking']    = '警告：您的银行付款设置未保存！';
$_['error_openbanking_currencies']  = '请至少提供一种货币。';

// Success
$_['success_settings_openbanking'] = '您的银行付款设置已保存！';
