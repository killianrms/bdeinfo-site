#!/bin/bash

# Script pour démarrer le serveur PHP

# Définir le port
PORT=12000

# Afficher un message de démarrage
echo "Démarrage du serveur PHP sur le port $PORT..."
echo "Accédez au site à l'adresse: https://work-1-liloqoxsuqxvevka.prod-runtime.all-hands.dev"

# Démarrer le serveur PHP
php -S 0.0.0.0:$PORT -t public