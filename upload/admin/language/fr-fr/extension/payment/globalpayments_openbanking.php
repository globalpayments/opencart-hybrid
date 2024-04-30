<?php
// Heading
$_['heading_title'] = 'GlobalPayments - Bank Payment';

// Text
$_['text_globalpayments_openbanking'] = '<a href="https://developer.globalpay.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" width="40px" height="40px" alt="Global Payments" title="Global Payments" style="border: 1px solid #EEEEEE;"></a>';

// Label
$_['label_enabled']        = 'Activer/désactiver';
$_['label_title']          = 'Titre';
$_['label_payment_action'] = 'Action de paiement';
$_['label_account_number'] = 'Numéro de compte';
$_['label_account_name']   = 'Nom du compte';
$_['label_sort_code']      = 'Code bancaire';
$_['label_countries']      = 'Pays';
$_['label_iban']           = 'IBAN';
$_['label_currencies']     = 'Devises';
$_['label_sort_order']     = 'Ordre de tri';

// Help
$_['help_title']                      = 'Cela contrôle le titre que voit l\'utilisateur durant le paiement.';
$_['help_payment_action']             = 'Choisissez de capturer les fonds immédiatement ou d\'autoriser le paiement seulement et de retarder la capture.';
$_['help_openbanking_account_number'] = 'Numéro de compte, pour les transferts bancaires au R.-U. (R.-U. à banque R.-U.). Requis seulement si aucune donnée bancaire n\'est stockée dans le compte.';
$_['help_openbanking_account_name']   = 'Le nom de la personne ou de l\'entreprise liée au compte bancaire. Requis seulement si aucune donnée bancaire n\'est stockée dans le compte.';
$_['help_openbanking_sort_code']      = 'Six chiffres qui identifient la banque et la succursale d\'un compte. Accompagne le numéro de compte pour les transferts bancaires R.-U. à banque R.-U. Requis seulement si aucune donnée bancaire n\'est stockée dans le compte.';
$_['help_openbanking_iban']           = 'Requis seulement pour les marchands avec transactions EUR.';
$_['help_openbanking_countries']      = 'Vous permet de saisir un PAYS ou une chaîne de PAYS en vue de limiter ce qui est montré au client. L\'ajout d\'un pays annule votre configuration de compte par défaut. <br/><br/>
	Format : Liste de codes ISO 3166-2 (deux caractères) séparés par un | <br/><br/>
	Exemple : FR|GB|IE';
$_['help_openbanking_currencies']     = 'Remarque : La méthode de paiement sera affichée à la caisse seulement pour les devises sélectionnées.';


// Entry
$_['entry_enabled']                      = 'Activer Bank Payment';
$_['entry_payment_action_authorize']     = 'Autoriser seulement';
$_['entry_payment_action_charge']        = 'Autoriser + capturer';
$_['entry_gbp']                          = 'GBP';
$_['entry_eur']                          = 'EUR';

// Placeholder
$_['placeholder_openbanking_title'] = 'Bank Payment';
$_['placeholder_title']             = 'Bank Payment';
$_['label_currencies_tooltip']      = 'Remarque : La méthode de paiement sera affichée à la caisse seulement pour les devises sélectionnées.';

// Error
$_['error_permission']              = 'Attention : Vous n\'avez pas la permission de modifier un paiement Bank Payment!';
$_['error_settings_openbanking']    = 'Attention : Vos paramètres Bank Payment n\'ont pas été sauvegardés!';
$_['error_openbanking_currencies']  = 'Veuillez fournir au moins une devise.';

// Success
$_['success_settings_openbanking'] = 'Vos paramètres Bank Payment ont été sauvegardés!';
