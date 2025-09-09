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
* Gestion des profils et comptes utilisateurs
* Liste des événements avec détails et inscriptions
* Panneau d'administration complet
* Gestion des adhésions avec système de paiement
* Classement (Leaderboard) des membres actifs
* FAQ interactive
* Design adaptatif (responsive) sur tous les appareils
* Section "Mes événements" dans le profil utilisateur
* Possibilité d'annuler son inscription aux événements

## Stack technologique

* **Backend :** PHP 8.0+
* **Base de données :** MySQL 5.7+ / MariaDB
* **Paiements :** SumUp SDK + Stripe (configuration disponible)
* **HTTP Client :** Guzzle HTTP
* **Dépendances :** Composer

## Prérequis

* PHP 8.0 ou supérieur
* MySQL 5.7+ ou MariaDB
* Extensions PHP requises :
  - `pdo_mysql`
  - `mbstring`
  - `xml`
  - `curl`
  - `json`
* Composer (gestionnaire de dépendances PHP)
* Serveur web (Apache/Nginx) ou serveur PHP intégré pour le développement

### Installation des prérequis

#### Sur Debian/Ubuntu :
```bash
# Installation de PHP et extensions
sudo apt update
sudo apt install php8.0 php8.0-mysql php8.0-mbstring php8.0-xml php8.0-curl php8.0-json

# Installation de MySQL
sudo apt install mysql-server

# Installation de Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Sur macOS (avec Homebrew) :
```bash
# Installation de PHP
brew install php

# Installation de MySQL
brew install mysql

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

3. **Configurer la base de données :**
   - Créer une base de données MySQL nommée `bdeinfo_site`
   - Modifier les paramètres de connexion dans `config/database.php`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'bdeinfo_site');
   define('DB_USER', 'votre_utilisateur');
   define('DB_PASS', 'votre_mot_de_passe');
   ```

4. **Initialiser la base de données :**
   ```bash
   php scripts/init_database.php
   ```
   
   Pour ajouter des données de test :
   ```bash
   php scripts/init_test_data.php
   ```

## Configuration

### Base de données
Les paramètres de connexion MySQL sont configurés dans `config/database.php` :
- Serveur : localhost:3306 (par défaut)
- Base de données : `bdeinfo_site`
- Encodage : UTF-8

### Système de paiement
Le projet supporte plusieurs systèmes de paiement :
- **SumUp :** Configuration dans `config/sumup.php`
- **Stripe :** Clés de test configurées dans `config/database.php`

## Lancement du serveur

### Développement local

#### Option 1 : Script automatisé (port 12000)
```bash
chmod +x start_server.sh
./start_server.sh
```

#### Option 2 : Script alternatif (port 12001)
```bash
chmod +x start_server_alt.sh
./start_server_alt.sh
```

#### Option 3 : Lancement manuel
```bash
# Serveur PHP intégré
php -S localhost:8000 -t public

# Ou sur toutes les interfaces
php -S 0.0.0.0:8000 -t public
```

### Production

Pour un environnement de production, configurez votre serveur web (Apache/Nginx) pour pointer vers le dossier `public/` comme document root.

## Structure du projet

```
bdeinfo-site/
├── public/              # Point d'entrée et assets publics
│   ├── index.php        # Point d'entrée principal
│   ├── css/             # Feuilles de style
│   ├── js/              # Scripts JavaScript
│   └── images/          # Images et médias
├── src/                 # Code source PHP
│   ├── Database.php     # Couche d'accès aux données
│   ├── Router.php       # Système de routage
│   └── controllers/     # Contrôleurs de l'application
├── templates/           # Templates HTML/PHP
├── config/              # Fichiers de configuration
│   ├── database.php     # Configuration BDD et paiements
│   └── sumup.php        # Configuration SumUp
├── database/            # Schémas et migrations MySQL
├── scripts/             # Scripts utilitaires
├── vendor/              # Dépendances Composer
└── composer.json        # Configuration Composer
```

## Fonctionnalités administratives

### Accès au panneau d'administration
Connectez-vous avec un compte administrateur et accédez à la section "Administration".

### Fonctionnalités disponibles
* **Gestion des événements :** Création, modification, suppression
* **Gestion des inscriptions :** Suivi des participants et paiements
* **Gestion des adhésions :** Administration des membres du BDE
* **Gestion des utilisateurs :** Administration des comptes
* **Statistiques :** Tableau de bord avec métriques

## Développement

### Contribution
1. Fork du projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit de vos changements (`git commit -am 'Ajout d'une nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalité`)
5. Créer une Pull Request

### Standards de code
* PSR-4 pour l'autoloading
* PSR-12 pour le style de code
* Utilisation de prepared statements pour les requêtes SQL
* Validation des données côté client et serveur

## Sécurité

* Validation et sanitisation de toutes les entrées utilisateur
* Protection CSRF sur les formulaires
* Hachage sécurisé des mots de passe
* Sessions sécurisées
* Protection contre l'injection SQL

## Dépannage

### Problèmes de base de données

**Erreur de connexion MySQL :**
```bash
# Vérifier que MySQL est démarré
sudo systemctl status mysql

# Vérifier les extensions PHP
php -m | grep pdo_mysql
```

**Problème de permissions :**
```bash
# Donner les bonnes permissions au dossier database
chmod -R 755 database/
```

### Problèmes de serveur

**Port déjà utilisé :**
```bash
# Vérifier les ports occupés
netstat -tlnp | grep :8000

# Tuer un processus si nécessaire
sudo kill -9 PID
```

**Erreurs PHP :**
```bash
# Vérifier les logs d'erreur
tail -f /var/log/php_errors.log
```

### Problèmes de paiement

**Configuration SumUp :**
1. Vérifier les clés API dans `config/sumup.php`
2. Tester en mode sandbox avant production
3. Vérifier les webhooks pour les notifications de paiement

**Configuration Stripe :**
1. Mettre à jour les clés dans `config/database.php`
2. Tester avec les clés de test avant production

## Changelog

### Version actuelle
* ✅ Migration vers MySQL/MariaDB
* ✅ Intégration SumUp SDK
* ✅ Interface utilisateur modernisée
* ✅ Section "Mes événements" dans le profil
* ✅ Système d'annulation d'inscription
* ✅ FAQ interactive
* ✅ Administration complète

### Roadmap
* 🔄 Notifications email automatiques
* 🔄 Application mobile PWA
* 🔄 Système de points et récompenses
* 🔄 Intégration calendrier externe
* 🔄 Chat en temps réel pour les événements

## Support

Pour toute question ou problème :
* **Issues GitHub :** [Créer une issue](https://github.com/killianrms/bdeinfo-site/issues)
* **Email :** bdeinfomontpellier@gmail.com
* **Discord :** Serveur du BDE Info

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
