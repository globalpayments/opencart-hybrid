<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Gpi Transaction';

// Tab
$_['tab_gpitrans'] = 'Unified Payments';
$_['tab_payment']  = 'Paiement';
$_['tab_txnapi']   = 'API de transaction';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = 'Extensions';
$_['text_edit']               = 'Modifier GlobalPayments - API de transaction';
$_['text_success']            = 'C\'est réussi : Vous avez modifié les données de compte GlobalPayments - Unified Payments!';
$_['text_select_all']         = 'Tout sélectionner';
$_['text_unselect_all']       = 'Tout désélectionner';

// Label
$_['label_enabled']           = 'Activer/désactiver';
$_['label_title']             = 'Titre';
$_['label_is_production']     = 'Mode En ligne';
$_['label_app_id']            = 'ID d\'appli En ligne';
$_['label_app_key']           = 'Clé d\'appli En ligne';
$_['label_sandbox_app_id']    = 'ID d\'appli Test';
$_['label_sandbox_app_key']   = 'Clé d\'appli Test';
$_['credentials_check']       = 'Vérification des identifiants';
$_['label_debug']             = 'Activer l\'enregistrement';
$_['label_contact_url']       = 'URL de contact';
$_['label_payment_action']    = 'Action de paiement';
$_['label_allow_card_saving'] = 'Permettre la sauvegarde de carte';
$_['label_txn_descriptor']    = 'Descripteur de transaction de commande';
$_['entry_sort_order']        = 'Ordre de tri';

// Help
$_['help_title']                 = 'Cela contrôle le titre que voit l\'utilisateur durant le paiement.';
$_['help_is_production']         = 'Obtenez votre ID d\'appli et votre clé d\'appli de votre <a href="https://developer.globalpay.com/user/register" target="_blank">Compte de développeur Global Payments</a>. ' .
                                 'Veuillez suivre les instructions de la description du plugiciel.<br/>' .
                                 'Pour passer en mode En ligne, veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">le Soutien</a> pour obtenir vos identifiants.';
$_['help_for_credentials_check'] = 'Veuillez noter que les méthodes de paiement n\'apparaîtront pas lors du paiement si les identifiants sont incorrects.';
$_['help_credentials_check']     = 'Demander au serveur Unified Payments de vérifier les identifiants d\'ID d\'appli et de clé d\'appli.';
$_['help_debug']                 = 'Enregistrer toutes les demandes entrantes et sortantes de la passerelle. Les données privées peuvent également être enregistrées. Cela ne peut être activé que dans un environnement de développement.';
$_['help_contact_url']           = 'Un lien vers une page À propos ou Contact sur votre site Web avec les informations de service à la clientèle (longueur max : 256).';
$_['help_payment_action']        = 'Choisissez de capturer les fonds immédiatement ou d\'autoriser le paiement seulement et de retarder la capture.';
$_['help_allow_card_saving']     = 'Remarque : Pour utiliser la fonction de sauvegarde de carte, vous devez activer la prise en charge de jetons à multiples usages dans votre compte. Veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">le Soutien</a> si vous avez des questions sur cette option.';
$_['help_txn_descriptor']        = 'Lors d\'une capture ou d\'une autorisation de paiement, cette valeur sera transmise en tant que descripteur de transaction énoncé dans le compte bancaire du client (longueur max : 25).';
$_['help_txn_descriptor_note']   = 'Veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">le Soutien</a> si vous avez des questions sur cette option.';

// Entry
$_['entry_enabled']                  = 'Activer la passerelle';
$_['entry_is_production']            = 'Mode En ligne';
$_['entry_credentials_check']        = 'Vérification des identifiants';
$_['entry_debug']                    = 'Activer l\'enregistrement';
$_['entry_payment_action_authorize'] = 'Autoriser seulement';
$_['entry_payment_action_charge']    = 'Autoriser + capturer';
$_['entry_allow_card_saving']        = 'Permettre la sauvegarde de carte';

// Placeholder
$_['placeholder_title'] = 'Carte de crédit ou de débit';

// Error
$_['error_permission']                  = 'Attention : Vous n\'avez pas la permission de modifier un paiement Unified Payments!';
$_['error_gateway_not_enabled']         = 'Passerelle non activée. Veuillez vérifier les données du compte!';
$_['error_settings_ucp']                = 'Attention : Vos paramètres Unified Payments n\'ont pas été sauvegardés!';
$_['error_contact_url']                 = 'Veuillez fournir une URL de contact (longueur max : 256).';
$_['error_live_credentials_app_id']     = 'Veuillez fournir des identifiants En ligne.';
$_['error_live_credentials_app_key']    = 'Veuillez fournir des identifiants En ligne.';
$_['error_sandbox_credentials_app_id']  = 'Veuillez fournir des identifiants Test.';
$_['error_sandbox_credentials_app_key'] = 'Veuillez fournir des identifiants Test.';
$_['error_txn_descriptor']              = 'Veuillez fournir un descripteur de transaction de commande (longueur max : 25).';
$_['error_request']                     = 'Impossible d\'exécuter la demande. Données invalides.';

// Success
$_['success_settings_ucp']      = 'Vos paramètres Unified Payments ont été sauvegardés!';
$_['success_settings_gpitrans'] = 'Vos paramètres Unified Payments ont été sauvegardés!';
$_['success_credentials_check'] = 'Vos identifiants ont été confirmés avec succès!';

// Alert
$_['alert_credentials_check'] = 'Assurez-vous d\'avoir rempli les champs App ID et App Key!';
