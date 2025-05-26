# Site Web du BDE Informatique de Montpellier

## Description

Ce projet est le site web officiel du BDE (Bureau des Étudiants) Info de Montpellier. Il permet aux étudiants de s'informer sur les événements à venir, d'adhérer au BDE, de s'inscrire aux activités et de participer à la vie étudiante du département informatique.

## Informations de contact

* **Email :** bdeinfomontpellier@gmail.com
* **Instagram :** [@bde_info_mtp](https://www.instagram.com/bde_info_mtp/)
* **GitHub :** [https://github.com/killianrms/](https://github.com/killianrms/)
* **Adresse :** 99 Av. d'Occitanie, 34090 Montpellier

## Fonctionnalités principales

* Inscription et connexion des utilisateurs
* Gestion des comptes utilisateurs
* Liste des événements et détails
* Panneau d'administration pour la création et la gestion des événements
* Gestion des adhésions (avec paiement en ligne via SumUp)
* Classement (Leaderboard)
* FAQ pour répondre aux questions fréquentes
* Design adaptatif (responsive design)

## Prérequis

* PHP 7.4 ou supérieur
* Extension PHP SQLite activée
* Composer (gestionnaire de dépendances PHP)

### Installation des prérequis

#### Sur Debian/Ubuntu :
```bash
# Installation de PHP et de l'extension SQLite
sudo apt update
sudo apt install php php-sqlite3 php-mbstring php-xml php-curl

# Installation de Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

#### Sur macOS (avec Homebrew) :
```bash
# Installation de PHP
brew install php

# Installation de Composer
brew install composer
```

## Installation

1. **Cloner le dépôt :**
   ```bash
   git clone https://github.com/killianrms/bdeinfo-site.git
   cd bdeinfo-site
   ```

2. **Installer les dépendances PHP :**
   ```bash
   composer install
   ```

3. **Initialiser la base de données :**
   ```bash
   php scripts/init_database.php
   ```
   
   Si vous souhaitez ajouter des données de test :
   ```bash
   php scripts/init_test_data.php
   ```

## Configuration

* **Base de données :** Les paramètres de connexion à la base de données SQLite sont configurés dans `config/database.php`.
* **Passerelle de paiement :** Si vous utilisez SumUp pour les paiements, configurez les clés API dans `config/sumup.php`.

## Lancement du serveur

Le projet inclut des scripts pour démarrer rapidement un serveur de développement :

### Option 1 : Utiliser le port 12000 (work-1)

```bash
chmod +x start_server.sh
./start_server.sh
```

Le site sera accessible à l'adresse :
* https://work-1-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev

### Option 2 : Utiliser le port 12001 (work-2)

```bash
chmod +x start_server_alt.sh
./start_server_alt.sh
```

Le site sera accessible à l'adresse :
* https://work-2-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev

### Option 3 : Lancement manuel

Vous pouvez également lancer manuellement le serveur PHP intégré :

```bash
# Pour le port 12000
php -S 0.0.0.0:12000 -t public

# Pour le port 12001
php -S 0.0.0.0:12001 -t public
```

## Structure du projet

* `public/` - Point d'entrée de l'application et fichiers accessibles publiquement
* `src/` - Code source PHP de l'application
* `templates/` - Fichiers de templates HTML
* `config/` - Fichiers de configuration
* `database/` - Fichiers de base de données SQLite et schémas
* `scripts/` - Scripts utilitaires pour la maintenance

## Fonctionnalités administratives

Pour accéder au panneau d'administration, connectez-vous avec un compte administrateur et naviguez vers la section "Administration" dans le menu principal.

Le panneau d'administration permet de :
* Gérer les événements (création, modification, suppression)
* Voir les inscriptions aux événements
* Gérer les adhésions
* Administrer les comptes utilisateurs

## Problèmes connus et solutions

### Affichage des événements

Il existe actuellement un problème d'affichage visuel dans la liste des événements. Pour le résoudre :

1. Vérifiez le fichier CSS `public/css/events.css` pour vous assurer que les styles sont correctement appliqués
2. Assurez-vous que les images des événements sont correctement dimensionnées et accessibles
3. Si nécessaire, modifiez le template `templates/events.php` pour améliorer la mise en page

### Système de paiement SumUp

Le système de redirection vers SumUp pour les paiements peut rencontrer des problèmes. Pour les résoudre :

1. Vérifiez que les clés API SumUp dans `config/sumup.php` sont valides et à jour
2. Assurez-vous que les URL de redirection (success_url et cancel_url) sont correctement configurées
3. Vérifiez les logs d'erreur pour identifier les problèmes spécifiques
4. Testez le processus de paiement en mode sandbox avant de passer en production

### Participants aux événements

Si les utilisateurs avec paiement validé ne sont pas ajoutés à la liste des participants :

1. Vérifiez la fonction `updateEventRegistrationStatus` dans `src/Database.php`
2. Assurez-vous que les webhooks SumUp sont correctement configurés pour mettre à jour le statut des paiements
3. Vérifiez les logs pour identifier les erreurs potentielles lors de la mise à jour des statuts

## Mise à jour de la base de données

Si vous devez mettre à jour le schéma de la base de données :

```bash
php scripts/apply_pending_schema.php
```

## Dépannage

### Problèmes de base de données

Si vous rencontrez des erreurs liées à la base de données :

1. Vérifiez que l'extension SQLite est bien activée :
   ```bash
   php -m | grep sqlite
   ```

2. Assurez-vous que les permissions sont correctes sur le dossier `database/` :
   ```bash
   chmod -R 755 database/
   chmod 664 database/*.sqlite database/*.db
   ```

3. Réinitialisez la base de données en cas de corruption :
   ```bash
   rm database/bde_site.sqlite
   php scripts/init_database.php
   ```

### Problèmes de serveur

Si le serveur ne démarre pas :

1. Vérifiez qu'aucun autre processus n'utilise déjà le port :
   ```bash
   lsof -i :12000
   ```

2. Essayez un autre port en modifiant la variable PORT dans le script `start_server.sh`

### Problèmes d'accès

Si vous ne pouvez pas accéder au site :

1. Vérifiez que le serveur est bien démarré et écoute sur l'adresse 0.0.0.0 (toutes les interfaces)
2. Assurez-vous que le pare-feu autorise les connexions sur le port utilisé

## Modifications récentes et à venir

### Modifications effectuées
* ✅ Redirection de la page de contact vers la FAQ
* ✅ Mise à jour des mentions légales avec les informations correctes
* ✅ Amélioration de l'interface d'administration
* ✅ Mise à jour des informations de contact
* ✅ Ajout d'une section "Mes événements" dans le profil utilisateur
* ✅ Correction de l'affichage des événements
* ✅ Amélioration du système de paiement (simulation en mode développement)
* ✅ Ajout de la possibilité d'annuler son inscription à un événement

### Modifications à venir
* 🔄 Intégration complète avec SumUp en production
* 🔄 Amélioration de l'interface mobile
* 🔄 Ajout de notifications par email pour les événements
* 🔄 Système de points de fidélité pour les adhérents
