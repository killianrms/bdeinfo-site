<?php

// Configuration Email
define('EMAIL_FROM', getenv('EMAIL_FROM') ?: 'bdeinfomontpellier@gmail.com');
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'BDE Info Montpellier');
define('EMAIL_REPLY_TO', getenv('EMAIL_REPLY_TO') ?: 'bdeinfomontpellier@gmail.com');

// Configuration SMTP (optionnel, pour utilisation future avec PHPMailer)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: '587');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

?>
