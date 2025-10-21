<?php

// Charger les variables d'environnement depuis le fichier .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parser les lignes KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Définir la variable d'environnement si elle n'existe pas déjà
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// IMPORTANT : Les identifiants sont chargés depuis le fichier .env
// Ne JAMAIS mettre de valeurs par défaut ici pour des raisons de sécurité
define('SUMUP_CLIENT_ID', getenv('SUMUP_CLIENT_ID'));
define('SUMUP_CLIENT_SECRET', getenv('SUMUP_CLIENT_SECRET'));
define('SUMUP_API_KEY', getenv('SUMUP_API_KEY'));
define('SUMUP_MERCHANT_CODE', getenv('SUMUP_MERCHANT_CODE'));
// URL de base de cette application (utilisée pour les URL de redirection)
// Assurez-vous que ceci est correctement configuré pour votre environnement (ex: https://votredomaine.com)
define('BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost:12000');

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