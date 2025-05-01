<?php
// Heading
$_['heading_title'] = 'Global Payments - API de transacciones';

// Text
$_['text_globalpayments_txnapi'] = '<a href="https://developer.globalpay.com" target"_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']           = 'Activar/Desactivar';
$_['label_title']             = 'Título';
$_['label_is_production']     = 'Modo en vivo';
$_['label_region']            = 'Región';
$_['label_debug']             = 'Habilitar registro';
$_['label_payment_action']    = 'Acción de Pago';
$_['label_allow_card_saving'] = 'Permitir guardar tarjeta';

$_['label_txnapi_public_key']                 = 'Clave pública activa';
$_['label_sandbox_txnapi_public_key']         = 'Clave pública de la zona de pruebas';
$_['label_txnapi_api_key']                    = 'Clave API en vivo';
$_['label_sandbox_txnapi_api_key']            = 'Clave API de zona de pruebas';
$_['label_txnapi_api_secret']                 = 'Live API Secret';
$_['label_sandbox_txnapi_api_secret']         = 'Sandbox API Secret';
$_['label_txnapi_account_credential']         = 'Credencial de cuenta real';
$_['label_sandbox_txnapi_account_credential'] = 'Credencial de cuenta Sandbox';

// Help
$_['help_title']             = 'Esto controla el título que ve el usuario durante el pago.';
$_['help_debug']             = 'Registre todas las solicitudes hacia y desde la puerta de enlace. Esto también puede registrar datos privados y sólo debe habilitarse en un entorno de desarrollo o etapa.';
$_['help_payment_action']    = 'Elija si desea Captura fondos inmediatamente o autorizar el pago sólo para una captura retrasada.';
$_['help_allow_card_saving'] = 'Nota: para utilizar la función de ahorro de tarjeta, debe tener habilitada la compatibilidad con tokens de usos múltiples en su cuenta. Comuníquese con el <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">soporte</a> si tiene alguna pregunta sobre esta opción.';

// Entry
$_['entry_enabled']                  = 'Habilitar API de transacciones';
$_['entry_is_production']            = 'Modo en vivo';
$_['entry_us']                       = 'Estados Unidos';
$_['entry_ca']                       = 'Canadá';
$_['entry_debug']                    = 'Habilitar registro';
$_['entry_payment_action_authorize'] = 'Autorizar sólo';
$_['entry_payment_action_charge']    = 'Autorizar + Captura';
$_['entry_allow_card_saving']        = 'Permitir guardar tarjeta';

// Placeholder
$_['placeholder_txnapi_title'] = 'Pagar con API de transacciones';

// Error
$_['error_permission']      = 'Advertencia: ¡No tienes permiso para modificar la API de transacciones de pago!';
$_['error_settings_txnapi'] = 'Advertencia: ¡La configuración de tu API de transacciones no se guardó!';

$_['error_live_credentials_txnapi_public_key']            = 'Proporcione credenciales activas.';
$_['error_sandbox_credentials_txnapi_public_key']         = 'Proporcione las credenciales de Sandbox.';
$_['error_live_credentials_txnapi_api_key']               = 'Proporcione credenciales activas.';
$_['error_sandbox_credentials_txnapi_api_key']            = 'Proporcione las credenciales de Sandbox.';
$_['error_live_credentials_txnapi_api_secret']            = 'Proporcione credenciales activas.';
$_['error_sandbox_credentials_txnapi_api_secret']         = 'Proporcione las credenciales de Sandbox.';
$_['error_live_credentials_txnapi_account_credential']    = 'Proporcione credenciales activas.';
$_['error_sandbox_credentials_txnapi_account_credential'] = 'Proporcione las credenciales de Sandbox.';

// Success
$_['success_settings_txnapi'] = '¡Se guardaron tus configuraciones de la API de Transacciones!';
