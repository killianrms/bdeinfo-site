<?php

define('DB_DRIVER', 'mysql');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'bdeinfo_site');
define('DB_USER', getenv('DB_USER') ?: 'bdeinfo');
define('DB_PASS', getenv('DB_PASS') ?: 'bdeinfo123');
define('DB_CHARSET', 'utf8mb4');





define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY');

?>