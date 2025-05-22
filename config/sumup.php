<?php

// IMPORTANT : Remplacer par vos identifiants Client ID et Secret SumUp réels
// Il est recommandé d'utiliser des variables d'environnement pour ceux-ci.
define('SUMUP_CLIENT_ID', getenv('SUMUP_CLIENT_ID') ?: 'cc_classic_LyXg1vGEeOPVt3DHOGYp55LMI7cOk');
define('SUMUP_CLIENT_SECRET', getenv('SUMUP_CLIENT_SECRET') ?: 'cc_sk_classic_8zUWJhJqyeWk62q3zCr9lgNYYAf0jvHub3oUCwVRIte2Y6VYvU');
define('SUMUP_API_KEY', getenv('SUMUP_API_KEY') ?: 'sup_sk_wLtsHG37A5Z0CdnvnnpLDRSVLnCcTKPgj'); // Ajoutez votre clé API SumUp

define('SUMUP_MERCHANT_CODE', getenv('SUMUP_MERCHANT_CODE') ?: 'MCAHH3UY'); // Ajoutez votre code marchand SumUp
// URL de base de cette application (utilisée pour les URL de redirection)
// Assurez-vous que ceci est correctement configuré pour votre environnement (ex: https://votredomaine.com)
define('BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost:8000');

// Configuration de l'API SumUp
// NOTE : Vérifiez ces points de terminaison par rapport à la documentation officielle des développeurs SumUp (https://developer.sumup.com/api)
define('SUMUP_API_BASE_URL', 'https://api.sumup.com'); // L'URL de base semble standard
define('SUMUP_AUTH_URL', SUMUP_API_BASE_URL . '/token'); // Point de terminaison standard pour le jeton OAuth
define('SUMUP_CHECKOUT_ENDPOINT', '/v0.1/checkouts'); // VÉRIFIER DOCS : Vérifiez la version de l'API et le chemin pour la création des paiements
define('SUMUP_TRANSACTION_ENDPOINT_PATTERN', '/v0.1/transactions/{id}'); // VÉRIFIER DOCS : Modèle d'URL pour le statut de la transaction (remplacer {id})

// URL complètes construites à partir de la base et des points de terminaison
define('SUMUP_CHECKOUT_URL', SUMUP_API_BASE_URL . SUMUP_CHECKOUT_ENDPOINT);
// Exemple d'utilisation pour le statut de la transaction : str_replace('{id}', $transactionId, SUMUP_API_BASE_URL . SUMUP_TRANSACTION_ENDPOINT_PATTERN)

?>