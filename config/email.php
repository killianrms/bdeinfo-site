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

// Configuration Email
// IMPORTANT : Les valeurs sont chargées depuis le fichier .env
define('EMAIL_FROM', getenv('EMAIL_FROM'));
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME'));
define('EMAIL_REPLY_TO', getenv('EMAIL_REPLY_TO'));

// Configuration SMTP
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT') ?: '587');
define('SMTP_USERNAME', getenv('SMTP_USERNAME'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

?>
