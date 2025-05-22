<?php
// Script pour mettre à jour les événements avec des images

// Chemin de la base de données
$dbPath = __DIR__ . '/../database/bde.db';

// Vérifier si la base de données existe
if (!file_exists($dbPath)) {
    die("Erreur : Base de données non trouvée à $dbPath\n");
}

try {
    // Connexion à la base de données
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer tous les événements
    $stmt = $db->query('SELECT id, title FROM events');
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Images disponibles
    $images = [
        'event-integration.jpg',
        'event-dev-web.jpg',
        'event-gaming.jpg',
        'event-ai.jpg',
        'event-hackathon.jpg'
    ];
    
    // Mettre à jour chaque événement avec une image
    foreach ($events as $index => $event) {
        // Sélectionner une image (en boucle si plus d'événements que d'images)
        $imageIndex = $index % count($images);
        $imagePath = $images[$imageIndex];
        
        // Mettre à jour l'événement
        $updateStmt = $db->prepare('UPDATE events SET image_path = :image_path WHERE id = :id');
        $updateStmt->execute([
            ':image_path' => $imagePath,
            ':id' => $event['id']
        ]);
        
        echo "Événement #{$event['id']} ({$event['title']}) mis à jour avec l'image : $imagePath\n";
    }
    
    echo "Tous les événements ont été mis à jour avec succès !\n";
    
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage() . "\n");
}