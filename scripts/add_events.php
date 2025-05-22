<?php

// Script pour ajouter des événements à venir
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Connexion à la base de données réussie.\n";
    
    // Ajouter des événements à venir
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
        
        echo "Événement ajouté : {$event['title']}\n";
    }
    
    echo "Ajout des événements terminé avec succès.\n";
    
} catch (Exception $e) {
    echo "Erreur lors de l'ajout des événements : " . $e->getMessage() . "\n";
    exit(1);
}