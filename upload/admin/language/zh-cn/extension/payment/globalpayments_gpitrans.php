<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Gpi 交易';

// Tab
$_['tab_gpitrans'] = '统一支付';
$_['tab_payment']  = '付款';
$_['tab_txnapi']   = '交易 API';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = '扩展';
$_['text_edit']               = '编辑 GlobalPayments - 交易 API';
$_['text_success']            = '成功：您已修改 GlobalPayments - 统一支付账户详情！';
$_['text_select_all']         = '全选';
$_['text_unselect_all']       = '取消全选';

// Label
$_['label_enabled']           = '启用/禁用';
$_['label_title']             = '标题';
$_['label_is_production']     = '正式环境模式';
$_['label_app_id']            = '正式环境 App Id';
$_['label_app_key']           = '正式环境 App Key';
$_['label_sandbox_app_id']    = '沙盒环境 App Id';
$_['label_sandbox_app_key']   = '沙盒环境 App Key';
$_['credentials_check']       = '凭证检查';
$_['label_debug']             = '启用日志记录';
$_['label_contact_url']       = '联系网址';
$_['label_payment_action']    = '付款操作';
$_['label_allow_card_saving'] = '允许保存卡片';
$_['label_txn_descriptor']    = '订单交易描述';
$_['entry_sort_order']        = '排序顺序';

// Help
$_['help_title']                 = '此设置控制用户在结账时看到的标题。';
$_['help_is_production']         = '从您的 <a href="https://developer.globalpay.com/user/register" target="_blank">Global Payments 开发者账户</a>获取您的 App Id 和 App Key。' .
                                 '请按照插件描述中提供的说明操作。<br/>' .
                                 '当您准备好上线时，请联系<a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">支持团队</a>以获取正式环境凭证。';
$_['help_for_credentials_check'] = '请注意，如果凭证不正确，付款方式将不会在结账时显示。';
$_['help_credentials_check']     = '向统一支付服务器发送请求以检查 App Id 和 App Key 凭证。';
$_['help_debug']                 = '记录所有发送到网关和从网关接收的请求。这也可能记录私人数据，应仅在开发或测试环境中启用。';
$_['help_contact_url']           = '链接到您网站上的关于或联系页面，包含客户服务信息（最大长度：256）。';
$_['help_payment_action']        = '选择您希望立即捕获资金还是仅授权付款以延迟捕获。';
$_['help_allow_card_saving']     = '注意：要使用卡片保存功能，您必须在账户上启用多用途令牌支持。如有任何关于此选项的问题，请联系<a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">支持团队</a>。';
$_['help_txn_descriptor']        = '在捕获或授权付款操作期间，此值将作为交易特定描述传递，列在客户的银行账户上（最大长度：25）。';
$_['help_txn_descriptor_note']   = '如有任何关于此选项的问题，请联系<a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">支持团队</a>。';

// Entry
$_['entry_enabled']                  = '启用网关';
$_['entry_is_production']            = '正式环境模式';
$_['entry_credentials_check']        = '凭证检查';
$_['entry_debug']                    = '启用日志记录';
$_['entry_payment_action_authorize'] = '仅授权';
$_['entry_payment_action_charge']    = '授权 + 捕获';
$_['entry_allow_card_saving']        = '允许保存卡片';

// Placeholder
$_['placeholder_title'] = '信用卡或借记卡';

// Error
$_['error_permission']                  = '警告：您没有权限修改统一支付付款设置！';
$_['error_gateway_not_enabled']         = '网关未启用。请检查账户详情！';
$_['error_settings_ucp']                = '警告：您的统一支付设置未保存！';
$_['error_contact_url']                 = '请提供联系网址（最大长度：256）。';
$_['error_live_credentials_app_id']     = '请提供正式环境凭证。';
$_['error_live_credentials_app_key']    = '请提供正式环境凭证。';
$_['error_sandbox_credentials_app_id']  = '请提供沙盒环境凭证。';
$_['error_sandbox_credentials_app_key'] = '请提供沙盒环境凭证。';
$_['error_txn_descriptor']              = '请提供订单交易描述（最大长度：25）。';
$_['error_request']                     = '无法执行请求。数据无效。';

// Success
$_['success_settings_ucp']      = '您的统一支付设置已保存！';
$_['success_settings_gpitrans'] = '您的统一支付设置已保存！';
$_['success_credentials_check'] = '您的凭证已成功确认！';

// Alert
$_['alert_credentials_check'] = '请确保您已填写 AppId 和 AppKey 字段！';
