#!/bin/bash

# Attendre que MySQL soit prêt
echo "Attente du démarrage de MySQL..."
sleep 10

# Exécuter le script d'initialisation des données de test
php /var/www/html/scripts/init_test_data.php

echo "Initialisation terminée !"
