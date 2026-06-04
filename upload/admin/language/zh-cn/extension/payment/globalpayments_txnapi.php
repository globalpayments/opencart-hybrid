<?php
// Heading
$_['heading_title'] = 'GlobalPayments - 交易 API';

// Text
$_['text_globalpayments_txnapi'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']           = '启用/禁用';
$_['label_title']             = '标题';
$_['label_is_production']     = '正式环境模式';
$_['label_region']            = '地区';
$_['label_debug']             = '启用日志记录';
$_['label_payment_action']    = '付款操作';
$_['label_allow_card_saving'] = '允许保存卡片';

$_['label_txnapi_public_key']                 = '正式环境公钥';
$_['label_sandbox_txnapi_public_key']         = '沙盒环境公钥';
$_['label_txnapi_api_key']                    = '正式环境 API 密钥';
$_['label_sandbox_txnapi_api_key']            = '沙盒环境 API 密钥';
$_['label_txnapi_api_secret']                 = '正式环境 API Secret';
$_['label_sandbox_txnapi_api_secret']         = '沙盒环境 API Secret';
$_['label_txnapi_account_credential']         = '正式环境账户凭证';
$_['label_sandbox_txnapi_account_credential'] = '沙盒环境账户凭证';

// Help
$_['help_title']             = '此设置控制用户在结账时看到的标题。';
$_['help_debug']             = '记录所有发送到网关和从网关接收的请求。这也可能记录私人数据，应仅在开发或测试环境中启用。';
$_['help_payment_action']    = '选择您希望立即捕获资金还是仅授权付款以延迟捕获。';
$_['help_allow_card_saving'] = '注意：要使用卡片保存功能，您必须在账户上启用多用途令牌支持。如有任何关于此选项的问题，请联系<a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">支持团队</a>。';

// Entry
$_['entry_enabled']                  = '启用交易 API';
$_['entry_is_production']            = '正式环境模式';
$_['entry_us']                       = '美国';
$_['entry_ca']                       = '加拿大';
$_['entry_debug']                    = '启用日志记录';
$_['entry_payment_action_authorize'] = '仅授权';
$_['entry_payment_action_charge']    = '授权 + 捕获';
$_['entry_allow_card_saving']        = '允许保存卡片';

// Placeholder
$_['placeholder_txnapi_title'] = '使用交易 API 付款';

// Error
$_['error_permission']      = '警告：您没有权限修改交易 API 付款设置！';
$_['error_settings_txnapi'] = '警告：您的交易 API 设置未保存！';

$_['error_live_credentials_txnapi_public_key']            = '请提供正式环境凭证。';
$_['error_sandbox_credentials_txnapi_public_key']         = '请提供沙盒环境凭证。';
$_['error_live_credentials_txnapi_api_key']               = '请提供正式环境凭证。';
$_['error_sandbox_credentials_txnapi_api_key']            = '请提供沙盒环境凭证。';
$_['error_live_credentials_txnapi_api_secret']            = '请提供正式环境凭证。';
$_['error_sandbox_credentials_txnapi_api_secret']         = '请提供沙盒环境凭证。';
$_['error_live_credentials_txnapi_account_credential']    = '请提供正式环境凭证。';
$_['error_sandbox_credentials_txnapi_account_credential'] = '请提供沙盒环境凭证。';

// Success
$_['success_settings_txnapi'] = '您的交易 API 设置已保存！';
