<?php

// Script pour initialiser la base de données avec des données de test
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Connexion à la base de données réussie.\n";
    
    // Vérifier si la base de données est vide
    $userCount = $db->getUserCount();
    $eventCount = $db->getEventCount();
    
    echo "Nombre d'utilisateurs existants : $userCount\n";
    echo "Nombre d'événements existants : $eventCount\n";
    
    // Ajouter un utilisateur administrateur si aucun n'existe
    if ($userCount === 0) {
        echo "Ajout d'un utilisateur administrateur...\n";
        
        $adminEmail = 'admin@bdeinfo.fr';
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $adminFirstName = 'Admin';
        $adminLastName = 'BDE';
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_admin, created_at) 
                              VALUES (:email, :password, :first_name, :last_name, 1, CURRENT_TIMESTAMP)");
        $stmt->bindParam(':email', $adminEmail);
        $stmt->bindParam(':password', $adminPassword);
        $stmt->bindParam(':first_name', $adminFirstName);
        $stmt->bindParam(':last_name', $adminLastName);
        $stmt->execute();
        
        echo "Utilisateur administrateur créé avec succès.\n";
        echo "Email: $adminEmail\n";
        echo "Mot de passe: admin123\n";
    }
    
    // Ajouter des adhésions si aucune n'existe
    $stmt = $pdo->query("SELECT COUNT(*) FROM memberships");
    $membershipCount = (int)$stmt->fetchColumn();
    
    if ($membershipCount === 0) {
        echo "Ajout des types d'adhésions...\n";
        
        $memberships = [
            [
                'name' => 'Adhésion Standard',
                'description' => 'Adhésion de base au BDE Informatique. Accès aux événements et réductions sur les activités.',
                'price' => 10.00,
                'duration_days' => 365,
                'discount_percentage' => 5.00
            ],
            [
                'name' => 'Adhésion Premium',
                'description' => 'Adhésion premium avec accès prioritaire aux événements et réductions importantes.',
                'price' => 20.00,
                'duration_days' => 365,
                'discount_percentage' => 15.00
            ],
            [
                'name' => 'Adhésion Semestrielle',
                'description' => 'Adhésion pour un semestre seulement. Idéal pour les étudiants en échange.',
                'price' => 5.00,
                'duration_days' => 182,
                'discount_percentage' => 5.00
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO memberships (name, description, price, duration_days, discount_percentage, created_at) 
                              VALUES (:name, :description, :price, :duration_days, :discount_percentage, CURRENT_TIMESTAMP)");
        
        foreach ($memberships as $membership) {
            $stmt->bindParam(':name', $membership['name']);
            $stmt->bindParam(':description', $membership['description']);
            $stmt->bindParam(':price', $membership['price']);
            $stmt->bindParam(':duration_days', $membership['duration_days']);
            $stmt->bindParam(':discount_percentage', $membership['discount_percentage']);
            $stmt->execute();
        }
        
        echo "Types d'adhésions ajoutés avec succès.\n";
    }
    
    // Ajouter des événements si aucun n'existe
    if ($eventCount === 0) {
        echo "Ajout d'événements de test...\n";
        
        $events = [
            [
                'title' => 'Soirée d\'intégration',
                'description' => 'Venez rencontrer les nouveaux étudiants et l\'équipe du BDE lors de notre soirée d\'intégration annuelle. Au programme : jeux, musique et convivialité !',
                'event_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
                'location' => 'Salle des fêtes du campus',
                'price' => 5.00,
                'points_awarded' => 100
            ],
            [
                'title' => 'Atelier Cybersécurité',
                'description' => 'Un atelier pratique sur les bases de la cybersécurité animé par des professionnels du secteur. Apportez votre ordinateur portable !',
                'event_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
                'location' => 'Salle B204',
                'price' => 0.00,
                'points_awarded' => 75
            ],
            [
                'title' => 'LAN Party',
                'description' => 'Une nuit entière dédiée aux jeux vidéo en réseau. Plusieurs tournois seront organisés avec des lots à gagner !',
                'event_date' => date('Y-m-d H:i:s', strtotime('+3 weeks')),
                'location' => 'Gymnase du campus',
                'price' => 10.00,
                'points_awarded' => 150
            ],
            [
                'title' => 'Visite d\'entreprise - Google',
                'description' => 'Visite exclusive des bureaux de Google. Une opportunité unique de découvrir l\'environnement de travail d\'une des plus grandes entreprises tech.',
                'event_date' => date('Y-m-d H:i:s', strtotime('+2 months')),
                'location' => 'Google France, Paris',
                'price' => 15.00,
                'points_awarded' => 200
            ],
            [
                'title' => 'Hackathon Développement Durable',
                'description' => 'Un week-end pour développer des solutions innovantes aux problématiques environnementales. Ouvert à tous les niveaux !',
                'event_date' => date('Y-m-d H:i:s', strtotime('+6 weeks')),
                'location' => 'Incubateur du campus',
                'price' => 0.00,
                'points_awarded' => 250
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, price, points_awarded, status, created_at, updated_at) 
                              VALUES (:title, :description, :event_date, :location, :price, :points_awarded, 'open', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        
        foreach ($events as $event) {
            $stmt->bindParam(':title', $event['title']);
            $stmt->bindParam(':description', $event['description']);
            $stmt->bindParam(':event_date', $event['event_date']);
            $stmt->bindParam(':location', $event['location']);
            $stmt->bindParam(':price', $event['price']);
            $stmt->bindParam(':points_awarded', $event['points_awarded']);
            $stmt->execute();
        }
        
        echo "Événements de test ajoutés avec succès.\n";
    }
    
    echo "Initialisation des données de test terminée avec succès.\n";
    
} catch (Exception $e) {
    echo "Erreur lors de l'initialisation des données de test : " . $e->getMessage() . "\n";
    exit(1);
}