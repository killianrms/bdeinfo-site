<?php
// Script pour initialiser la base de données

// Chemin de la base de données
$dbPath = __DIR__ . '/../database/bde.db';
$schemaPath = __DIR__ . '/../database/schema.sql';

// Vérifier si le schéma existe
if (!file_exists($schemaPath)) {
    die("Erreur : Fichier de schéma non trouvé à $schemaPath\n");
}

try {
    // Créer la base de données
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lire et exécuter le schéma SQL
    $schema = file_get_contents($schemaPath);
    $db->exec($schema);
    
    echo "Base de données initialisée avec succès à $dbPath\n";
    
    // Ajouter des données de test
    
    // 1. Ajouter des adhésions
    $memberships = [
        [
            'name' => 'Support',
            'description' => 'Adhésion de soutien au BDE Informatique',
            'price' => 5.00,
            'duration_days' => 365,
            'discount_percentage' => 5.00
        ],
        [
            'name' => 'Premium',
            'description' => 'Adhésion premium avec accès à tous les avantages',
            'price' => 15.00,
            'duration_days' => 365,
            'discount_percentage' => 15.00
        ]
    ];
    
    $membershipStmt = $db->prepare('INSERT INTO memberships (name, description, price, duration_days, discount_percentage) VALUES (:name, :description, :price, :duration_days, :discount_percentage)');
    
    foreach ($memberships as $membership) {
        $membershipStmt->execute($membership);
        echo "Adhésion '{$membership['name']}' ajoutée\n";
    }
    
    // 2. Ajouter des utilisateurs
    $users = [
        [
            'email' => 'admin@bdeinfo.fr',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'first_name' => 'Admin',
            'last_name' => 'BDE',
            'is_admin' => 1,
            'membership_status' => 'premium'
        ],
        [
            'email' => 'user@example.com',
            'password' => password_hash('user123', PASSWORD_DEFAULT),
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'is_admin' => 0,
            'membership_status' => 'support'
        ],
        [
            'email' => 'etudiant@example.com',
            'password' => password_hash('etudiant123', PASSWORD_DEFAULT),
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'is_admin' => 0,
            'membership_status' => 'none'
        ]
    ];
    
    $userStmt = $db->prepare('INSERT INTO users (email, password, first_name, last_name, is_admin, membership_status) VALUES (:email, :password, :first_name, :last_name, :is_admin, :membership_status)');
    
    foreach ($users as $user) {
        $userStmt->execute($user);
        echo "Utilisateur '{$user['email']}' ajouté\n";
    }
    
    // 3. Ajouter des événements
    $events = [
        [
            'title' => 'Soirée d\'intégration',
            'description' => 'Venez rencontrer les nouveaux étudiants et l\'équipe du BDE lors de notre soirée d\'intégration annuelle. Au programme : jeux, musique et networking !',
            'event_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
            'price' => 5.00,
            'location' => 'Salle des fêtes du campus',
            'points_awarded' => 100,
            'status' => 'open'
        ],
        [
            'title' => 'Atelier Développement Web',
            'description' => 'Apprenez les bases du développement web avec HTML, CSS et JavaScript. Cet atelier est ouvert à tous les niveaux, débutants bienvenus !',
            'event_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
            'price' => 0.00,
            'location' => 'Salle informatique B204',
            'points_awarded' => 75,
            'status' => 'open'
        ],
        [
            'title' => 'Tournoi de jeux vidéo',
            'description' => 'Affrontez vos camarades lors de notre tournoi de jeux vidéo ! Plusieurs jeux au programme : League of Legends, Super Smash Bros, et FIFA.',
            'event_date' => date('Y-m-d H:i:s', strtotime('+3 weeks')),
            'price' => 2.00,
            'location' => 'Foyer étudiant',
            'points_awarded' => 150,
            'status' => 'open'
        ],
        [
            'title' => 'Conférence Intelligence Artificielle',
            'description' => 'Découvrez les dernières avancées en matière d\'intelligence artificielle avec notre conférencier invité, expert du domaine.',
            'event_date' => date('Y-m-d H:i:s', strtotime('+2 months')),
            'price' => 0.00,
            'location' => 'Amphithéâtre A',
            'points_awarded' => 50,
            'status' => 'open'
        ],
        [
            'title' => 'Hackathon BDE Info',
            'description' => '48 heures pour développer une application innovante en équipe. Prix à gagner pour les meilleures réalisations !',
            'event_date' => date('Y-m-d H:i:s', strtotime('+3 months')),
            'price' => 10.00,
            'location' => 'Campus entier',
            'points_awarded' => 200,
            'status' => 'open'
        ]
    ];
    
    $eventStmt = $db->prepare('INSERT INTO events (title, description, event_date, price, location, points_awarded, status) VALUES (:title, :description, :event_date, :price, :location, :points_awarded, :status)');
    
    foreach ($events as $event) {
        $eventStmt->execute($event);
        echo "Événement '{$event['title']}' ajouté\n";
    }
    
    echo "Données de test ajoutées avec succès !\n";
    
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage() . "\n");
}