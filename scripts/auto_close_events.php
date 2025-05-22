<?php

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../config/database.php'; // Ensure DB constants are loaded

// Set a default timezone if not already set to avoid warnings with date functions
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC'); // Or your application's default timezone
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection(); // PDO connection for potential direct use if needed, though methods are preferred

    echo "Script d'archivage automatique des événements démarré...\n";

    $eventsToClose = $db->getOpenPastEvents();

    if (empty($eventsToClose)) {
        echo "Aucun événement ouvert passé à archiver.\n";
    } else {
        $closedCount = 0;
        echo "Événements à archiver :\n";
        foreach ($eventsToClose as $event) {
            echo "  - ID: {$event['id']}, Titre: {$event['title']}, Date: {$event['event_date']}\n";
            if ($db->closeEvent($event['id'])) {
                echo "    STATUT: L'événement ID {$event['id']} a été archivé avec succès.\n";
                $closedCount++;
            } else {
                echo "    ERREUR: Échec de l'archivage de l'événement ID {$event['id']}.\n";
            }
        }
        echo "Nombre total d'événements archivés : {$closedCount}\n";
    }

    echo "Script d'archivage automatique des événements terminé.\n";

} catch (PDOException $e) {
    error_log("Erreur de base de données dans le script auto_close_events.php : " . $e->getMessage());
    echo "ERREUR CRITIQUE : Une erreur de base de données est survenue. Vérifiez les logs.\n";
} catch (Exception $e) {
    error_log("Erreur inattendue dans le script auto_close_events.php : " . $e->getMessage());
    echo "ERREUR CRITIQUE : Une erreur inattendue est survenue. Vérifiez les logs.\n";
}

?>