#!/bin/bash

# Script pour démarrer le serveur PHP sur le port alternatif

# Définir le port alternatif
PORT=12001

# Afficher un message de démarrage
echo "Démarrage du serveur PHP sur le port $PORT..."
echo "Accédez au site à l'adresse: https://work-2-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev"

# Démarrer le serveur PHP avec les options pour permettre les requêtes CORS et les iframes
php -S 0.0.0.0:$PORT -t public