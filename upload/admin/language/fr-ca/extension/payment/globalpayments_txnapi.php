<?php
// Heading
$_['heading_title'] = 'GlobalPayments - API de transaction';

// Text
$_['text_globalpayments_txnapi'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://developer.globalpay.com/static/media/logo.dab7811d.svg" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']           = 'Activer/désactiver';
$_['label_title']             = 'Titre';
$_['label_is_production']     = 'Mode En ligne';
$_['label_region']            = 'Région';
$_['label_debug']             = 'Activer l\'enregistrement';
$_['label_payment_action']    = 'Action de paiement';
$_['label_allow_card_saving'] = 'Permettre la sauvegarde de carte';

$_['label_txnapi_public_key']                 = 'Clé publique En ligne';
$_['label_sandbox_txnapi_public_key']         = 'Clé publique Test';
$_['label_txnapi_api_key']                    = 'Clé API En ligne';
$_['label_sandbox_txnapi_api_key']            = 'Clé API Test';
$_['label_txnapi_api_secret']                 = 'API secrète En ligne';
$_['label_sandbox_txnapi_api_secret']         = 'API secrète Test';
$_['label_txnapi_account_credential']         = 'Identifiants de compte En ligne';
$_['label_sandbox_txnapi_account_credential'] = 'Identifiants de compte Test';

// Help
$_['help_title']             = 'Cela contrôle le titre que voit l\'utilisateur durant le paiement.';
$_['help_debug']             = 'Enregistrer toutes les demandes entrantes et sortantes de la passerelle. Les données privées peuvent également être enregistrées. Cela ne peut être activé que dans un environnement de développement.';
$_['help_payment_action']    = 'Choisissez de capturer les fonds immédiatement ou d\'autoriser le paiement seulement et de retarder la capture.';
$_['help_allow_card_saving'] = 'Remarque : Pour utiliser la fonction de sauvegarde de carte, vous devez activer la prise en charge de jetons à multiples usages dans votre compte. Veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">le Soutien</a> si vous avez des questions sur cette option.';

// Entry
$_['entry_enabled']                  = 'Activer API de transaction';
$_['entry_is_production']            = 'Mode En ligne';
$_['entry_us']                       = 'États-Unis';
$_['entry_ca']                       = 'Canada';
$_['entry_debug']                    = 'Activer l\'enregistrement';
$_['entry_payment_action_authorize'] = 'Autoriser seulement';
$_['entry_payment_action_charge']    = 'Autoriser + capturer';
$_['entry_allow_card_saving']        = 'Permettre la sauvegarde de carte';

// Placeholder
$_['placeholder_txnapi_title'] = 'Payer avec API de transaction';

// Error
$_['error_permission']      = 'Attention : Vous n\'avez pas la permission de modifier un paiement API de transaction!';
$_['error_settings_txnapi'] = 'Attention : Vos paramètres API de transaction n\'ont pas été sauvegardés!';

$_['error_live_credentials_txnapi_public_key']            = 'Veuillez fournir des identifiants En ligne.';
$_['error_sandbox_credentials_txnapi_public_key']         = 'Veuillez fournir des identifiants Test.';
$_['error_live_credentials_txnapi_api_key']               = 'Veuillez fournir des identifiants En ligne.';
$_['error_sandbox_credentials_txnapi_api_key']            = 'Veuillez fournir des identifiants Test.';
$_['error_live_credentials_txnapi_api_secret']            = 'Veuillez fournir des identifiants En ligne.';
$_['error_sandbox_credentials_txnapi_api_secret']         = 'Veuillez fournir des identifiants Test.';
$_['error_live_credentials_txnapi_account_credential']    = 'Veuillez fournir des identifiants En ligne.';
$_['error_sandbox_credentials_txnapi_account_credential'] = 'Veuillez fournir des identifiants Test.';

// Success
$_['success_settings_txnapi'] = 'Vos paramètres API de transaction ont été sauvegardés!';
