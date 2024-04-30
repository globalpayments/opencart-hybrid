<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Google Pay';

// Text
$_['text_globalpayments_googlepay'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']                     = 'Activer/désactiver';
$_['label_title']                       = 'Titre';
$_['label_payment_action']              = 'Action de paiement';
$_['label_gp_merchant_id']              = 'ID de client Global Payments';
$_['label_merchant_id']                 = 'ID de marchand Google';
$_['label_merchant_name']               = 'Nom de marchand Google affiché';
$_['label_accepted_cards']              = 'Cartes acceptées';
$_['label_googlepay_button_color']      = 'Couleur de bouton';
$_['label_allowed_card_auth_methods']   = 'Méthodes d\'autorisation de carte permises';
$_['label_sort_order']                  = 'Ordre de tri';

// Help
$_['help_title']          = 'Cela contrôle le titre que voit l\'utilisateur durant le paiement.';
$_['help_payment_action'] = 'Choisissez de capturer les fonds immédiatement ou d\'autoriser le paiement seulement et de retarder la capture.';
$_['help_gp_merchant_id'] = 'Votre ID de client, fourni par Global Payments.';
$_['help_merchant_id']    = 'Votre ID de marchand, fourni par Google.';
$_['help_merchant_name']  = 'Ce que voit le client dans le dialogue Google Pay.';

// Entry
$_['entry_enabled']                      = 'Activer Google Pay';
$_['entry_payment_action_authorize']     = 'Autoriser seulement';
$_['entry_payment_action_charge']        = 'Autoriser + capturer';
$_['entry_card_type_visa']               = 'Visa';
$_['entry_card_type_mastercard']         = 'MasterCard';
$_['entry_card_type_amex']               = 'AMEX';
$_['entry_card_type_discover']           = 'Discover';
$_['entry_card_type_jcb']                = 'JCB';
$_['entry_googlepay_button_color_white'] = 'Blanc';
$_['entry_googlepay_button_color_black'] = 'Noir';
$_['entry_method_pan_only']              = 'PAN_SEULEMENT';
$_['entry_method_cryptogram_3ds']        = 'CRYPTOGRAMME_3DS';

$_['label_allowed_card_auth_methods_tooltip']='PAN_SEULEMENT : Cette méthode d\'authentification est associée aux cartes de paiement sauvegardées dans le compte Google de l\'utilisateur.
CRYPTOGRAMME_3DS : Cette méthode d\'authentification est associée aux cartes stockées sous forme de jetons d\'appareil Android.

PAN_SEULEMENT peut exposer le FPAN, qui requiert une étape de sécurité additionnelle à la vérification 3DS. Actuellement, Global Payments ne prend pas en charge cette étape de sécurité Google Pay avec un FPAN. Pour optimiser l\'acceptation, nous vous recommandons de n\'offrir que l\'option CRYPTOGRAMME_3DS.';

// Placeholder
$_['placeholder_googlepay_title'] = 'Payer avec Google Pay';

// Error
$_['error_permission']                          = 'Attention : Vous n\'avez pas la permission de modifier un paiement Google Pay!';
$_['error_settings_googlepay']                  = 'Attention : Vos paramètres Google Pay n\'ont pas été sauvegardés!';
$_['error_gp_merchant_id']                      = 'Veuillez fournir un ID de client Global Payments.';
$_['error_googlepay_accepted_cards']            = 'Veuillez fournir les cartes acceptées.';
$_['error_googlepay_allowed_card_auth_methods'] = 'Veuillez fournir au moins une méthode.';

// Success
$_['success_settings_googlepay'] = 'Vos paramètres Google Pay ont été sauvegardés!';
