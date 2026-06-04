<?php
// Heading
$_['heading_title'] = 'GlobalPayments - 统一支付';

// Tab
$_['tab_ucp']            = '统一支付';
$_['tab_payment']        = '付款';
$_['tab_googlepay']      = 'Google Pay';
$_['tab_applepay']       = 'Apple Pay';
$_['tab_clicktopay']     = 'Click To Pay';
$_['tab_affirm']         = 'Affirm';
$_['tab_klarna']         = 'Klarna';
$_['tab_clearpay']       = 'Clearpay';
$_['tab_paypal']         = 'PayPal';
$_['tab_openbanking']    = '银行付款';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = '扩展';
$_['text_edit']               = '编辑 GlobalPayments - 统一支付';
$_['text_success']            = '成功：您已修改 GlobalPayments - 统一支付账户详情！';
$_['text_select_all']         = '全选';
$_['text_unselect_all']       = '取消全选';

// Label
$_['label_enabled']               = '启用/禁用';
$_['label_title']                 = '标题';
$_['label_is_production']         = '正式环境模式';
$_['label_app_id']                = '正式环境 App Id';
$_['label_app_key']               = '正式环境 App Key';
$_['label_account_name']          = '正式环境账户名称';
$_['label_sandbox_app_id']        = '沙盒环境 App Id';
$_['label_sandbox_app_key']       = '沙盒环境 App Key';
$_['label_sandbox_account_name']  = '沙盒环境账户名称';
$_['credentials_check']           = '凭证检查';
$_['label_debug']                 = '启用日志记录';
$_['label_contact_url']           = '联系网址';
$_['label_payment_action']        = '付款操作';
$_['label_allow_card_saving']     = '允许保存卡片';
$_['label_txn_descriptor']        = '订单交易描述';
$_['label_enable_three_d_secure'] = '启用 3DSecure';
$_['label_integration_type']      = '集成类型';
$_['label_sort_order']            = '排序顺序';
$_['label_blik']                  = '启用 Blik 付款';
$_['label_open_banking']          = '启用开放银行付款';

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
$_['help_account_name']          = '指定处理交易时使用的账户。如未指定，将使用默认账户。<br>如需协助找到您的账户名称，请根据您的地区联系我们的<a href="https://developer.globalpay.com/support/integration-support" target="_blank">集成支持</a>团队';
$_['help_integration_type']      = '选择您的付款表单是显示在结账页面上还是重定向到托管页面（托管方式可简化 Apple 和 Google Pay 的启用）。';

// Entry
$_['entry_enabled']                  = '启用网关';
$_['entry_is_production']            = '正式环境模式';
$_['entry_credentials_check']        = '凭证检查';
$_['entry_debug']                    = '启用日志记录';
$_['entry_payment_action_authorize'] = '仅授权';
$_['entry_payment_action_charge']    = '授权 + 捕获';
$_['entry_allow_card_saving']        = '允许保存卡片';
$_['entry_integration_type_dropin_ui'] = '嵌入式 UI';
$_['entry_integration_type_hosted_payment'] = '托管付款页面';

// Placeholder
$_['placeholder_title'] = '信用卡或借记卡';

// Error
$_['error_permission']                  = '警告：您没有权限修改统一支付付款设置！';
$_['error_gateway_not_enabled']         = '网关未启用。请检查账户详情！';
$_['error_settings_ucp']                = '警告：您的统一支付设置未保存！';
$_['error_contact_url']                 = '请提供联系网址（最大长度：256）。';
$_['error_live_credentials_app_id']     = '请提供正式环境凭证。';
$_['error_live_credentials_app_key']    = '请提供正式环境凭证。';
$_['error_live_credentials_account_name'] = '请提供正式环境账户名称。';
$_['error_sandbox_credentials_app_id']  = '请提供沙盒环境凭证。';
$_['error_sandbox_credentials_app_key'] = '请提供沙盒环境凭证。';
$_['error_sandbox_credentials_account_name'] = '请提供沙盒环境账户名称。';
$_['error_txn_descriptor']              = '请提供订单交易描述（最大长度：25）。';
$_['error_request']                     = '无法执行请求。数据无效。';

// Success
$_['success_settings_ucp']      = '您的统一支付设置已保存！';
$_['success_credentials_check'] = '您的凭证已成功确认！';

// Alert
$_['alert_credentials_check'] = '请确保您已填写 AppId 和 AppKey 字段！';

$_['text_success_full_refund']               = '付款已完全退款成功！';
$_['text_success_partial_refund']            = '付款已部分退款成功！';
$_['text_refunded_comment']                  = '订单已完全退款。';
