<?php
// Heading
$_['heading_title'] = 'GlobalPayments - 统一支付';

// Button
$_['button_capture'] = '捕获';
$_['button_refund']  = '退款';
$_['button_reverse'] = '撤销';

// Text
$_['text_payment_info']           = '付款信息';
$_['text_no_payment_info']        = '无可用的付款信息';
$_['text_success_capture']        = '捕获成功，授权已完全捕获。';
$_['text_success_partial_refund'] = '退款请求已成功提交。';
$_['text_success_full_refund']    = '退款请求已成功提交，金额已完全退款。';
$_['text_success_reverse']        = '交易已成功撤销。';
$_['text_confirm_capture']        = '您确定要继续执行完全捕获吗？';
$_['text_confirm_reverse']        = '您确定要继续执行撤销吗？';
$_['text_confirm_refund']         = '您确定要继续执行退款吗？';

// Error
$_['error_payment_info']                 = '无法获取付款信息。';
$_['error_request']                      = '无法执行请求。数据无效。';
$_['error_invalid_request']              = '无效的请求。';
$_['error_invalid_refund_amount']        = '无效的退款金额。';
$_['error_invalid_refund_amount_format'] = '无效的金额。请使用"."作为小数分隔符，且不要使用千位分隔符。';

// Column
$_['text_column_txn_payment_action'] = '付款操作';
$_['text_column_txn_id']             = '交易 ID';
$_['text_column_txn_status']         = '交易状态';
$_['text_column_txn_amount']         = '金额';
$_['text_column_txn_created']        = '创建时间';
$_['text_column_action']             = '操作';

// Meta
$_['text_meta_billing']  = 'Click To Pay 付款地址';
$_['text_meta_shipping'] = 'Click To Pay 送货地址';
$_['text_meta_email']    = '电子邮件地址';

// Admin BNPL
$_['button_getTransactionDetails']       = '获取交易详情';
$_['text_success_getTransactionDetails'] = '交易详情已成功加载。';
$_['text_confirm_getTransactionDetails'] = '您确定要继续获取交易详情吗？';
