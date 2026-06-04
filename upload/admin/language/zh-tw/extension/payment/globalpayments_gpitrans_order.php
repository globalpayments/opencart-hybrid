<?php
// Heading
$_['heading_title'] = 'GlobalPayments - GPI 交易';

// Button
$_['button_capture'] = '擷取';
$_['button_refund']  = '退款';
$_['button_reverse'] = '撤銷';

// Text
$_['text_payment_info']           = '付款資訊';
$_['text_no_payment_info']        = '無可用的付款資訊';
$_['text_success_capture']        = '擷取成功，授權已完全擷取。';
$_['text_success_partial_refund'] = '退款請求已成功提交。';
$_['text_success_full_refund']    = '退款請求已成功提交，金額已完全退款。';
$_['text_success_reverse']        = '交易已成功撤銷。';
$_['text_confirm_capture']        = '您確定要繼續執行完全擷取嗎？';
$_['text_confirm_reverse']        = '您確定要繼續執行撤銷嗎？';
$_['text_confirm_refund']         = '您確定要繼續執行退款嗎？';

// Error
$_['error_payment_info']                 = '無法取得付款資訊。';
$_['error_request']                      = '無法執行請求。資料無效。';
$_['error_invalid_request']              = '無效的請求。';
$_['error_invalid_refund_amount']        = '無效的退款金額。';
$_['error_invalid_refund_amount_format'] = '無效的金額。請使用「.」作為小數分隔符號，且不要使用千位分隔符號。';

// Column
$_['text_column_txn_payment_action'] = '付款操作';
$_['text_column_txn_id']             = '交易 ID';
$_['text_column_txn_status']         = '交易狀態';
$_['text_column_txn_amount']         = '金額';
$_['text_column_txn_created']        = '建立時間';
$_['text_column_action']             = '操作';

// Meta
$_['text_meta_billing']  = 'Click To Pay 付款地址';
$_['text_meta_shipping'] = 'Click To Pay 送貨地址';
$_['text_meta_email']    = '電子郵件地址';

// Admin BNPL
$_['button_getTransactionDetails']       = '取得交易詳情';
$_['text_success_getTransactionDetails'] = '交易詳情已成功載入。';
$_['text_confirm_getTransactionDetails'] = '您確定要繼續取得交易詳情嗎？';
