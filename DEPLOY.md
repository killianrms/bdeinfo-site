# Déploiement du Site BDE Info

## Prérequis

- Docker et Docker Compose installés sur votre serveur
- Port 80 disponible (ou modifier le port dans docker-compose.yml)

## Installation Rapide

### 1. Cloner le projet sur votre serveur

```bash
git clone https://github.com/killianrms/bdeinfo-site.git
cd bdeinfo-site
```

### 2. Lancer avec Docker

```bash
docker compose up -d
```

Le site sera accessible sur `http://votre-serveur` (port 80 par défaut).

### 3. Initialiser les données (première installation uniquement)

```bash
docker exec bdeinfo-web php /var/www/html/scripts/init_test_data.php
```

## Compte Administrateur par Défaut

- **Email :** killian.ramus@gmail.com
- **Mot de passe :** BdeSite2025?2806!


## Configuration Personnalisée

### Modifier le port

Dans `docker-compose.yml`, changez la ligne :
```yaml
ports:
  - "80:80"  # Changez 80 par le port souhaité
```

### Modifier les identifiants MySQL

Dans `docker-compose.yml`, modifiez les variables d'environnement :
```yaml
environment:
  MYSQL_PASSWORD: votre_nouveau_mot_de_passe
  DB_PASS: votre_nouveau_mot_de_passe
```

## Commandes Utiles

```bash
# Voir les logs
docker compose logs -f

# Arrêter les conteneurs
docker compose down

# Redémarrer
docker compose restart

# Sauvegarder la base de données
docker exec bdeinfo-mysql mysqldump -u bdeinfo -pbdeinfo123 bdeinfo_site > backup.sql

# Restaurer une sauvegarde
docker exec -i bdeinfo-mysql mysql -u bdeinfo -pbdeinfo123 bdeinfo_site < backup.sql
```

## Mise à jour

```bash
git pull
docker compose down
docker compose up -d --build
```

## Support

Pour toute question : bdeinfomontpellier@gmail.com
