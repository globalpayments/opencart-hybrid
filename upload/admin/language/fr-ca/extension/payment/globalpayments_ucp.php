<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Unified Payments';

// Tab
$_['tab_ucp']            = 'Unified Payments';
$_['tab_payment']        = 'Paiement';
$_['tab_googlepay']      = 'Google Pay';
$_['tab_applepay']       = 'Apple Pay';
$_['tab_clicktopay']     = 'Click To Pay';
$_['tab_affirm']         = 'Affirm';
$_['tab_klarna']         = 'Klarna';
$_['tab_clearpay']       = 'Clearpay';
$_['tab_paypal']         = 'PayPal';
$_['tab_openbanking']    = 'Bank Payment';

// Text
$_['text_globalpayments_ucp'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';
$_['text_extension']          = 'Extensions';
$_['text_edit']               = 'Modifier GlobalPayments - Unified Payments';
$_['text_success']            = 'C\'est réussi : Vous avez modifié les données de compte GlobalPayments - Unified Payments!';
$_['text_select_all']         = 'Tout sélectionner';
$_['text_unselect_all']       = 'Tout désélectionner';
$_['text_hpp_installments_title'] = 'Options de paiement par versements';
$_['text_hpp_installments_subtitle'] = "Les paiements par versements peuvent apparaître lors du paiement lorsqu'ils sont pris en charge par votre compte Global Payments et la carte du client.</br>Les paiements par versements de la page de paiement hébergée sont une fonctionnalité au niveau du compte. Cette option de paiement s'affichera uniquement lors du paiement si elle a été activée dans votre compte Global Payments.</br><b>Contactez le soutien de Global Payments pour en savoir plus sur l'activation des paiements par versements.</b>";
$_['text_hpp_installments_filtering_title'] = "Options de filtrage des paiements en plusieurs versements";
// Label
$_['label_enabled']               = 'Activer/désactiver';
$_['label_title']                 = 'Titre';
$_['label_is_production']         = 'Mode En ligne';
$_['label_region']                = 'Région de transaction';
$_['label_app_id']                = 'ID d\'appli En ligne';
$_['label_app_key']               = 'Clé d\'appli En ligne';
$_['label_account_name']          = 'Nom de compte En ligne';
$_['label_sandbox_app_id']        = 'ID d\'appli Test';
$_['label_sandbox_app_key']       = 'Clé d\'appli Test';
$_['label_sandbox_account_name']  = 'Nom de compte En ligne';
$_['credentials_check']           = 'Vérification des identifiants';
$_['label_debug']                 = 'Activer l\'enregistrement';
$_['label_contact_url']           = 'URL de contact';
$_['label_payment_action']        = 'Action de paiement';
$_['label_allow_card_saving']     = 'Permettre la sauvegarde de carte';
$_['label_txn_descriptor']        = 'Descripteur de transaction de commande';
$_['label_enable_three_d_secure'] = 'Activer 3DSecure';
$_['label_enable_installments']   = 'Activer les versements';
$_['label_sort_order']            = 'Ordre de tri';

// Help
$_['help_title']                 = 'Cela contrôle le titre que voit l\'utilisateur durant le paiement.';
$_['help_is_production']         = 'Obtenez votre ID d\'appli et votre clé d\'appli de votre <a href="https://developer.globalpay.com/user/register" target="_blank">Compte de développeur Global Payments</a>. ' .
                                 'Veuillez suivre les instructions de la description du plugiciel.<br/>' .
                                 'Pour passer en mode En ligne, veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Live%%20Credentials">le Soutien</a> pour obtenir vos identifiants.';
$_['help_region']                = 'Sélectionnez l’endroit où les transactions sont traitées. Cela contrôle l’hôte GP API pour le sandbox et le live.';
$_['help_for_credentials_check'] = 'Veuillez noter que les méthodes de paiement n\'apparaîtront pas lors du paiement si les identifiants sont incorrects.';
$_['help_credentials_check']     = 'Demander au serveur Unified Payments de vérifier les identifiants d\'ID d\'appli et de clé d\'appli.';
$_['help_debug']                 = 'Enregistrer toutes les demandes entrantes et sortantes de la passerelle. Les données privées peuvent également être enregistrées. Cela ne peut être activé que dans un environnement de développement.';
$_['help_contact_url']           = 'Un lien vers une page À propos ou Contact sur votre site Web avec les informations de service à la clientèle (longueur max : 256).';
$_['help_payment_action']        = 'Choisissez de capturer les fonds immédiatement ou d\'autoriser le paiement seulement et de retarder la capture.';
$_['help_allow_card_saving']     = 'Remarque : Pour utiliser la fonction de sauvegarde de carte, vous devez activer la prise en charge de jetons à multiples usages dans votre compte. Veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Card%%20Saving%%20Option">le Soutien</a> si vous avez des questions sur cette option.';
$_['help_txn_descriptor']        = 'Lors d\'une capture ou d\'une autorisation de paiement, cette valeur sera transmise en tant que descripteur de transaction énoncé dans le compte bancaire du client (longueur max : 25).';
$_['help_txn_descriptor_note']   = 'Veuillez contacter <a href="mailto:%s?Subject=OpenCart%%20Transaction%%20Descriptor%%20Option">le Soutien</a> si vous avez des questions sur cette option.';
$_['help_account_name']          = 'Spécifiez quel compte utiliser lorsque vous traitez une transaction. Compte défaut serait utilisé si pas spécifiée.';
$_['help_enable_installments']   = 'Activez l\'option de paiement échelonné pour les transactions admissibles.';
$_['help_hpp_wallets']           = 'Portefeuilles et méthodes de paiement alternatives des pages de paiement hébergées';
$_['help_hpp_wallets_description'] = 'Select the digital wallets and alternative payment methods you want to accept via Hosted Payment Page. These options are only available when using Hosted Payment Page integration type.';
$_['help_hpp_installments_plan_types'] = 'Limiter les plans affichés par type';
$_['help_hpp_installments_plan_types_tooltip'] = 'Utilisé pour filtrer les plans de versements en fonction du type de plan. MERCHANT_FUNDED (si envoyé, ne retournera que les plans financés par le marchand) CONSUMER_FUNDED (si envoyé, ne retournera que les plans financés par le client) HYBRID_FUNDED (si envoyé, retournera les plans financés par le marchand et le client) BILATERAL (si envoyé, ne retournera que les plans BILATERAL) ANY (si envoyé, retournera tous les plans disponibles) (par défaut) Remarque : Si absent, la demande sera envoyée avec la valeur par défaut.';
$_['help_hpp_installments_plan_duration'] = 'Utilisé pour récupérer les plans de versements avec une durée spécifique. <b>Applicable uniquement aux plans financés par le marchand.</b>';
$_['help_hpp_installments_plan_duration_tooltip'] = 'Utilisé pour récupérer les plans de versements avec une durée spécifique. <b>Applicable uniquement aux plans financés par le marchand.</b>';
$_['help_hpp_installments_plan_threshold'] = "Utilisé pour récupérer les plans de versements avec une durée spécifique. Applicable uniquement aux plans financés par le marchand. Exemple : max_term_months_merchant_funded = 12 — Signifie que seuls les plans d'une durée ≤ 12 mois seront récupérés. Les plans financés par le marchand d'une durée > 12 mois ne seront pas retournés. Défaut : 1000 Remarque : Si absent, la demande sera envoyée avec la valeur par défaut. Utilisé pour déterminer s'il faut retourner les plans de durée la plus longue ou la plus courte en fonction du montant envoyé.";
$_['help_hpp_installments_plan_threshold_tooltip'] = "Utilisé pour déterminer s'il faut retourner les plans de durée la plus longue ou la plus courte en fonction du montant envoyé.  <b>Définir 0 ou ne rien définir désactivera cette fonctionnalité et les plans seront affichés dans l'ordre de tri par défaut</b></br> Le montant doit être envoyé dans la plus petite unité de la devise requise. </br><b>Exemple : 2000 = $20.00<b/>";


// Entry
$_['entry_enabled']                  = 'Activer la passerelle';
$_['entry_is_production']            = 'Mode En ligne';
$_['entry_region_global']            = 'Global (par défaut)';
$_['entry_region_europe']            = 'Europe';
$_['entry_credentials_check']        = 'Vérification des identifiants';
$_['entry_debug']                    = 'Activer l\'enregistrement';
$_['entry_payment_action_authorize'] = 'Autoriser seulement';
$_['entry_payment_action_charge']    = 'Autoriser + capturer';
$_['entry_allow_card_saving']        = 'Permettre la sauvegarde de carte';
$_['entry_integration_type_dropin_ui'] = 'Interface utilisateur intégrée';
$_['entry_integration_type_hosted_payment'] = 'page de paiement hébergée';
$_['entry_hpp_installments_plan_type_any'] = 'Afficher tous les plans';
$_['entry_hpp_installments_plan_type_customer_funded'] = 'Afficher uniquement les plans financés par le client';
$_['entry_hpp_installments_plan_type_merchant_funded'] = 'Afficher uniquement les plans financés par le marchand';
$_['entry_hpp_installments_plan_type_hybrid_funded'] = 'Afficher uniquement les plans à financement hybride';
$_['entry_hpp_installments_plan_type_bilateral'] = 'Afficher uniquement les plans à financement bilatéral';
$_['entry_hpp_installments_plan_duration_any'] = 'Afficher tous les plans';
$_['entry_hpp_installments_plan_duration_6_month'] = 'Afficher uniquement les plans de 6 mois';
$_['entry_hpp_installments_plan_duration_12_month'] = 'Afficher uniquement les plans de 12 mois';
$_['entry_hpp_installments_plan_duration_24_month'] = 'Afficher uniquement les plans de 24 mois';
$_['entry_hpp_installments_plan_duration_32_month'] = 'Afficher uniquement les plans de 32 mois';

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
$_['success_credentials_check'] = 'Vos identifiants ont été confirmés avec succès!';

// Alert
$_['alert_credentials_check'] = 'Assurez-vous d\'avoir rempli les champs App ID et App Key!';


// 3D secure
$_['three_d_secure_required_display_text']      = '3D Secure est requis dans votre pays et est activé automatiquement';
$_['three_d_secure_not_required_display_text']  = '3D Secure est facultatif dans votre pays';
