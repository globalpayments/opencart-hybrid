<?php
// Heading
$_['heading_title'] = 'Global Payments - Pago bancario';

// Text
$_['text_globalpayments_openbanking'] = '<a href="https://developer.globalpay.com" target"_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4"width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = 'Activar/Desactivar';
$_['label_title']          = 'Título';
$_['label_payment_action'] = 'Acción de Pago';
$_['label_account_number'] = 'Número de cuenta';
$_['label_account_name']   = 'Nombre de cuenta';
$_['label_sort_code']      = 'Código de clasificación';
$_['label_countries']      = 'Países';
$_['label_iban']           = 'IBAN';
$_['label_currencies']     = 'Monedas';
$_['label_sort_order']     = 'Orden de clasificación';

// Help
$_['help_title']                      = 'Esto controla el título que ve el usuario durante el pago.';
$_['help_payment_action']             = 'Elija si desea Captura fondos inmediatamente o autorizar el pago sólo para una captura retrasada.';
$_['help_openbanking_account_number'] = 'Número de cuenta, para transferencias bancarias dentro del Reino Unido (banco del Reino Unido a Reino Unido). Sólo es necesario si no se almacenan datos bancarios en la cuenta.';
$_['help_openbanking_account_name']   = 'El nombre de la persona o empresa en la cuenta bancaria. Sólo es necesario si no se almacenan datos bancarios en la cuenta.';
$_['help_openbanking_sort_code']      = 'Seis dígitos que identifican el banco y la sucursal de una cuenta. Incluido con el número de cuenta para transferencias bancarias del Reino Unido a Reino Unido. Sólo es necesario si no se almacenan datos bancarios en la cuenta.';
$_['help_openbanking_iban']           = 'Sólo se requiere para comerciantes que realizan transacciones en EUR.';
$_['help_openbanking_countries']      = 'Le permite ingresar un PAÍS o una cadena de PAÍSES para limitar lo que se muestra al cliente. La inclusión de un país anula la configuración predeterminada de su cuenta. <br/>><br/>
	Formato: Lista de códigos ISO 3166-2 (dos caracteres) separados por | <br/>><br/>
	Ejemplo: FR|GB|IE';
$_['help_openbanking_currencies']     = 'Nota: El método de pago estará disponible al finalizar la compra sólo para las monedas seleccionadas.';


// Entry
$_['entry_enabled']                      = 'Habilitar pago bancario';
$_['entry_payment_action_authorize']     = 'Autorizar sólo';
$_['entry_payment_action_charge']        = 'Autorizar + Captura';
$_['entry_gbp']                          = 'GBP';
$_['entry_eur']                          = 'euros';

// Placeholder
$_['placeholder_openbanking_title'] = 'Pago Bancario';
$_['placeholder_title']             = 'Pago Bancario';
$_['label_currencies_tooltip']      = 'Nota: El método de pago se mostrará al finalizar la compra sólo para las monedas seleccionadas.';

// Error
$_['error_permission']              = 'Advertencia: ¡No tienes permiso para modificar el pago Pago Bancario!';
$_['error_settings_openbanking']    = 'Advertencia: ¡Su configuración de pago bancario no se guardó!';
$_['error_openbanking_currencies']  = 'Por favor proporcione al menos una moneda.';

// Success
$_['success_settings_openbanking'] = '¡Se guardaron sus configuraciones de pago bancario!';
