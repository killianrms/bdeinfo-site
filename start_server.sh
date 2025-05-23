#!/bin/bash

# Script pour démarrer le serveur PHP

# Définir le port (12000 pour work-1, 12001 pour work-2)
PORT=12000

# Afficher un message de démarrage
echo "Démarrage du serveur PHP sur le port $PORT..."
echo "Accédez au site à l'adresse: https://work-1-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev"
echo "Ou utilisez le port 12001 pour accéder via: https://work-2-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev"

# Démarrer le serveur PHP avec les options pour permettre les requêtes CORS et les iframes
php -S 0.0.0.0:$PORT -t public