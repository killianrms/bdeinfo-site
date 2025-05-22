<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php'; // Inclure la classe Database si nécessaire pour la configuration de la connexion, bien que SQLite3 direct soit suffisant ici.

$dbPath = DB_PATH;
$sqlStatements = [
    // Ajouter la colonne manquante 'location' à la table 'events'
    "ALTER TABLE events ADD COLUMN location TEXT NULL;",

    // Ajouter la colonne manquante 'points_awarded' à la table 'events'
    "ALTER TABLE events ADD COLUMN points_awarded INTEGER NOT NULL DEFAULT 50;",

    // Ajouter la colonne 'status' à la table 'events'
    "ALTER TABLE events ADD COLUMN status TEXT NOT NULL DEFAULT 'open' CHECK(status IN ('open', 'closed', 'archived'));",

    // Schéma SQL pour les transactions SumUp en attente
    "CREATE TABLE IF NOT EXISTS pending_sumup_transactions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      checkout_reference TEXT NOT NULL UNIQUE,
      user_id INTEGER NOT NULL,
      membership_id INTEGER NOT NULL,
      status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed')),
      created_at TEXT DEFAULT CURRENT_TIMESTAMP,
      updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE RESTRICT
    );",

    // Déclencheur pour mettre à jour le timestamp 'updated_at' pour 'pending_sumup_transactions'
    "CREATE TRIGGER IF NOT EXISTS update_pending_sumup_transactions_updated_at
    AFTER UPDATE ON pending_sumup_transactions
    FOR EACH ROW
    BEGIN
        UPDATE pending_sumup_transactions SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
    END;"
];

$db = null;
$allSuccessful = true;

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true); // Activer les exceptions pour les erreurs

    echo "Attempting to apply schema changes to $dbPath...\n";

    foreach ($sqlStatements as $sql) {
        try {
            echo "Exécution : " . substr(trim($sql), 0, 80) . "...\n"; // Afficher les 80 premiers caractères
            $db->exec($sql);
            echo " -> Success.\n";
        } catch (Exception $e) {
            // Vérifier si l'erreur concerne une colonne dupliquée ou une table/déclencheur existant
            if (str_contains($e->getMessage(), 'duplicate column name') || str_contains($e->getMessage(), 'already exists')) {
                echo " -> Skipped (already exists): " . $e->getMessage() . "\n";
            } else {
                echo " -> Failed: " . $e->getMessage() . "\n";
                $allSuccessful = false; // Marquer comme échoué si c'est une erreur inattendue
            }
        }
    }

    if ($allSuccessful) {
        echo "Schema changes applied (or already present).\n";
    } else {
        echo "Some schema changes failed unexpectedly.\n";
    }

} catch (Exception $e) {
    echo "Error connecting to or configuring the database: " . $e->getMessage() . "\n";
    $allSuccessful = false; // Marquer comme échoué en cas d'erreur de connexion
    exit(1); // Indiquer l'échec
} finally {
    if ($db) {
        $db->close();
    }
}

exit($allSuccessful ? 0 : 1); // Indiquer le succès (0) ou l'échec (1)

?>