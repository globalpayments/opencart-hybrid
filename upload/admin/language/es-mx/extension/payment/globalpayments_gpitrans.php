<?php
// Heading
$_['heading_title'] = 'Global Payments - Transacción Gpi';

// Tab
$_['tab_gpitrans'] = 'Unified Payments';
$_['tab_payment']  = 'Pago';
$_['tab_txnapi']   = 'API de transacciones';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target"_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = 'Extensiones';
$_['text_edit']               = 'Editar Global Payments - API de transacciones';
$_['text_success']            = 'Éxito: Ha modificado Global Payments - ¡Los detalles de la cuenta de Unified Payments!';
$_['text_select_all']         = 'Seleccionar todo';
$_['text_unselect_all']       = 'Deseleccionar todo';

// Label
$_['label_enabled']           = 'Activar/Desactivar';
$_['label_title']             = 'Título';
$_['label_is_production']     = 'Modo en vivo';
$_['label_app_id']            = 'ID de aplicación en vivo';
$_['label_app_key']           = 'Clave de aplicación en vivo';
$_['label_sandbox_app_id']    = 'ID de la aplicación Sandbox';
$_['label_sandbox_app_key']   = 'Clave de aplicación Sandbox';
$_['credentials_check']       = 'Verificación de credenciales';
$_['label_debug']             = 'Habilitar registro';
$_['label_contact_url']       = 'URL de contacto';
$_['label_payment_action']    = 'Acción de Pago';
$_['label_allow_card_saving'] = 'Permitir guardar tarjeta';
$_['label_txn_descriptor']    = 'Descriptor de transacción de pedido';
$_['entry_sort_order']        = 'Orden de clasificación';

// Help
$_['help_title']                 = 'Esto controla el título que ve el usuario durante el pago.';
$_['help_is_production']         = 'Obtenga su ID de aplicación y clave de aplicación de su <a href="https://developer.globalpay.com/user/register" target="_blank">Cuenta de desarrollador de Global Payments</a>. ' .
                                 'Siga las instrucciones proporcionadas en la descripción del complemento.<br/>' .
                                 'Cuando esté listo para Live, comuníquese con <a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">soporte</a> para obtener sus credenciales Live.';
$_['help_for_credentials_check'] = 'Tenga en cuenta que los métodos de pago no aparecerán al finalizar la compra si las credenciales no son correctas.';
$_['help_credentials_check']     = 'Realice una solicitud al servidor de Unified Payments para verificar las credenciales de ID de aplicación y clave de aplicación.';
$_['help_debug']                 = 'Registre todas las solicitudes hacia y desde la puerta de enlace. Esto también puede registrar datos privados y sólo debe habilitarse en un entorno de desarrollo o etapa.';
$_['help_contact_url']           = 'Un enlace a una página Acerca de o Contacto en su sitio web con información de atención al cliente (maxLength: 256).';
$_['help_payment_action']        = 'Elija si desea Captura fondos inmediatamente o autorizar el pago sólo para una captura retrasada.';
$_['help_allow_card_saving']     = 'Nota: para utilizar la función de ahorro de tarjeta, debe tener habilitada la compatibilidad con tokens de usos múltiples en su cuenta. Comuníquese con el <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">support</a> si tiene alguna pregunta sobre esta opción.';
$_['help_txn_descriptor']        = 'Durante una acción de Captura o Autorizar pago, este valor se transmitirá como el descriptor específico de la transacción que figura en la cuenta bancaria del cliente (maxLength: 25).';
$_['help_txn_descriptor_note']   = 'Favor de contactar <a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">support</a> with any questions regarding this option.';

// Entry
$_['entry_enabled']                  = 'Habilitar puerta de enlace';
$_['entry_is_production']            = 'Modo en vivo';
$_['entry_credentials_check']        = 'Verificación de credenciales';
$_['entry_debug']                    = 'Habilitar registro';
$_['entry_payment_action_authorize'] = 'Autorizar sólo';
$_['entry_payment_action_charge']    = 'Autorizar + Captura';
$_['entry_allow_card_saving']        = 'Permitir guardar tarjeta';

// Placeholder
$_['placeholder_title'] = 'Tarjeta de Crédito o Débito';

// Error
$_['error_permission']                  = 'Advertencia: ¡No tienes permiso para modificar el pago Unified Payments!';
$_['error_gateway_not_enabled']         = 'Puerta de enlace no habilitada. ¡Por favor verifique los detalles de la cuenta!';
$_['error_settings_ucp']                = 'Advertencia: ¡Su configuración de Unified Payments no se guardó!';
$_['error_contact_url']                 = 'Proporcione una URL de contacto (maxLength: 256).';
$_['error_live_credentials_app_id']     = 'Proporcione credenciales activas.';
$_['error_live_credentials_app_key']    = 'Proporcione credenciales activas.';
$_['error_sandbox_credentials_app_id']  = 'Proporcione las credenciales de Sandbox.';
$_['error_sandbox_credentials_app_key'] = 'Proporcione las credenciales de Sandbox.';
$_['error_txn_descriptor']              = 'Proporcione el descriptor de transacción del pedido (maxLength: 25).';
$_['error_request']                     = 'No se puede realizar la solicitud. Datos no válidos.';

// Success
$_['success_settings_ucp']      = '¡Se guardaron sus configuraciones de Unified Payments!';
$_['success_settings_gpitrans'] = '¡Se guardaron sus configuraciones de Unified Payments!';
$_['success_credentials_check'] = '¡Sus credenciales fueron confirmadas exitosamente!';

// Alert
$_['alert_credentials_check'] = '¡Asegurse de que ha llenado los campos de ID de la App y de la clave de la App!';
