# Site BDE Info

## Description

Ce projet est le site web officiel du BDE (Bureau des Étudiants) Info. Il fournit des informations sur les événements, les adhésions et permet l'interaction des utilisateurs via l'inscription et la connexion.

## Fonctionnalités Clés

*   Inscription et Connexion des utilisateurs
*   Gestion des comptes utilisateurs
*   Liste des événements et détails
*   Panneau d'administration pour la création et la gestion des événements (y compris la visualisation des inscriptions)
*   Gestion des adhésions (potentiellement avec paiement en ligne via SumUp)
*   Classement (Leaderboard)
*   Design Adaptatif (Responsive Design - sous-entendu par l'utilisation de CSS/JS)

## Technologies Utilisées

*   **Backend :** PHP
*   **Base de données :** SQLite
*   **Gestion des dépendances :** Composer
*   **Frontend :** HTML, CSS, JavaScript

## Installation et Configuration

1.  **Cloner le dépôt :**
    ```bash
    git clone <url-du-depot>
    cd bdeinfo-site
    ```
2.  **Installer les dépendances PHP :**
    ```bash
    composer install
    ```
3.  **Configurer la base de données :**
    *   Assurez-vous que l'extension PHP SQLite est activée.
    *   Le schéma de la base de données est défini dans `database/schema.sql`.
    *   Vous devrez peut-être initialiser ou mettre à jour la base de données en utilisant le schéma. Le script `scripts/apply_pending_schema.php` peut vous y aider. Le fichier de base de données se trouve à l'adresse `database/bde_site.sqlite`.
4.  **Configurer votre serveur web :**
    *   Définissez la racine du document (par exemple, `DocumentRoot` dans Apache ou `root` dans Nginx) sur le répertoire `public/` du projet.
    *   Assurez-vous que la réécriture d'URL est activée si nécessaire pour le routage de l'application.

## Configuration

*   **Base de données :** Les détails de connexion à la base de données (chemin vers le fichier SQLite) sont configurés dans `config/database.php`.
*   **Passerelle de paiement (SumUp) :** Si vous utilisez SumUp pour les paiements, configurez les clés API et les paramètres dans `config/sumup.php`.

## Lancer le Site

Une fois l'installation et la configuration terminées, accédez au site via l'URL configurée dans votre serveur web (par exemple, `http://localhost/` ou `http://votre-domaine-local.test/`).
