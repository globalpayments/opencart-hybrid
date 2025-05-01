<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Google Pay';

// Text
$_['text_globalpayments_googlepay'] = '<a href="https://developer.globalpay.com" target"_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']                     = 'Activar/Desactivar';
$_['label_title']                       = 'Título';
$_['label_payment_action']              = 'Acción de Pago';
$_['label_gp_merchant_id']              = 'ID de cliente de Global Payments';
$_['label_merchant_id']                 = 'ID de Google Merchant'; 
$_['label_merchant_name']               = 'Display Name de Google Merchant'; 
$_['label_accepted_cards']              = 'Tarjetas aceptadas';
$_['label_googlepay_button_color']      = 'Color de Botón';
$_['label_allowed_card_auth_methods']   = 'Métodos de autenticación de tarjeta permitidos';
$_['label_sort_order']                  = 'Orden de clasificación';

// Help
$_['help_title']          = 'Esto controla el título que ve el usuario durante el pago.';
$_['help_payment_action'] = 'Elija si desea capturar fondos inmediatamente o autorizar el pago sólo para una captura retrasada.';
$_['help_gp_merchant_id'] = 'Su ID de cliente proporcionado por Global Payments.';
$_['help_merchant_id']    = 'Su ID de comerciante proporcionado por Google.';
$_['help_merchant_name']  = 'Se muestra al cliente en el diálogo de Google Pay.';

// Entry
$_['entry_enabled']                      = 'Habilitar Google Pay';
$_['entry_payment_action_authorize']     = 'Autorizar sólo';
$_['entry_payment_action_charge']        = 'Autorizar + Captura';
$_['entry_card_type_visa']               = 'Visa';
$_['entry_card_type_mastercard']         = 'Tarjeta MasterCard';
$_['entry_card_type_amex']               = 'AMEX';
$_['entry_card_type_discover']           = 'Descubrir';
$_['entry_card_type_jcb']                = 'JCB';
$_['entry_googlepay_button_color_white'] = 'Blanco';
$_['entry_googlepay_button_color_black'] = 'Negro';
$_['entry_method_pan_only']              = 'PAN_ONLY';
$_['entry_method_cryptogram_3ds']        = 'CRIPTOGRAMA_3DS';

$_['label_allowed_card_auth_methods_tooltip']='PAN_ONLY: este método de autenticación está asociado con las tarjetas de pago almacenadas en el archivo de la cuenta de Google del usuario.
CRYPTOGRAMA_3DS: este método de autenticación está asociado con tarjetas almacenadas como tokens de dispositivos Android.

PAN_ONLY puede exponer el FPAN, lo que requiere un paso SCA adicional hasta una verificación 3DS. Actualmente, Global Payments no admite el desafío SCA de Google Pay con una FPAN. Para una mejor aceptación, le recomendamos que proporcione sólo la opción CRYPTOGRAM_3DS.';

// Placeholder
$_['placeholder_googlepay_title'] = 'Pagar con Google Pay';

// Error
$_['error_permission']                          = 'Advertencia: ¡No tienes permiso para modificar el pago Google Pay!';
$_['error_settings_googlepay']                  = 'Advertencia: ¡Tu configuración de Google Pay no se guardó!';
$_['error_gp_merchant_id']                      = 'Proporcione el ID de cliente de Global Payments.';
$_['error_googlepay_accepted_cards']            = 'Proporcione las tarjetas aceptadas.';
$_['error_googlepay_allowed_card_auth_methods'] = 'Proporcione al menos un método.';

// Success
$_['success_settings_googlepay'] = '¡Se guardaron tus configuraciones de Google Pay!';
