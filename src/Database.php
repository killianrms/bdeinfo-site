<?php


require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {

        $dsn = '';
        if (DB_DRIVER === 'sqlite') {
            $dsn = 'sqlite:' . DB_PATH;
        } elseif (DB_DRIVER === 'pgsql') {
            $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . (defined('DB_CHARSET') ? ';charset=' . DB_CHARSET : '');
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {

            if (DB_DRIVER === 'sqlite') {
                $this->pdo = new PDO($dsn, null, null, $options);

                $this->pdo->exec('PRAGMA foreign_keys = ON;');
// Vérifier et ajouter la colonne is_admin si manquante
                try {
                    $columns = $this->pdo->query("PRAGMA table_info(users);")->fetchAll(PDO::FETCH_COLUMN, 1);
                    if (!in_array('is_admin', $columns)) {
                        $this->pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0;");
                        error_log('Colonne is_admin manquante ajoutée à la table users.');
                    }
                    // Vérifier et ajouter la colonne is_locked si manquante
                    if (!in_array('is_locked', $columns)) {
                        $this->pdo->exec("ALTER TABLE users ADD COLUMN is_locked INTEGER NOT NULL DEFAULT 0;");
                        error_log('Colonne is_locked manquante ajoutée à la table users.');
                    }
                    // Vérifier et ajouter la colonne failed_login_attempts si manquante
                    if (!in_array('failed_login_attempts', $columns)) {
                        $this->pdo->exec("ALTER TABLE users ADD COLUMN failed_login_attempts INTEGER NOT NULL DEFAULT 0;");
                        error_log('Colonne failed_login_attempts manquante ajoutée à la table users.');
                    }
                } catch (\PDOException $e) {
                    error_log('Erreur lors de la vérification/ajout de colonnes à la table users : ' . $e->getMessage());
                    // Décider si cela doit être une erreur fatale ou simplement enregistrée
                }

                $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmt->fetchAll();

                if (count($tables) === 0) {

                    try {
                        $schemaPath = __DIR__ . '/../database/schema.sql';
                        if (file_exists($schemaPath)) {
                            $sql = file_get_contents($schemaPath);
                            $this->pdo->exec($sql);
                            error_log('Schéma de base de données créé avec succès.');
                        } else {
                            error_log('Fichier de schéma non trouvé : ' . $schemaPath);
                        }
                    } catch (\Exception $e) {
                        error_log('Erreur lors de l\'exécution du script de schéma : ' . $e->getMessage());

                        throw new \RuntimeException('Échec de l\'initialisation du schéma de base de données.', 0, $e);
                    }
                }

            } else {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (\PDOException $e) {

            error_log('Erreur de Connexion à la Base de Données : ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    
    public function getConnection(): PDO {
        return $this->pdo;
    }


    public function getUserCount(): int {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération du nombre d\'utilisateurs : ' . $e->getMessage());
            return 0; // Retourner 0 en cas d'erreur
        }
    }

    
    public function getEventCount(): int {
        try {
            // Compte tous les événements, quel que soit leur statut (open, closed)
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM events");
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération du nombre d\'événements : ' . $e->getMessage());
            return 0; // Retourner 0 en cas d'erreur
        }
    }
    
    public function getAllMemberships(): array {
        try {
            $stmt = $this->pdo->query("SELECT id, name, description, price, duration_days, discount_percentage, discord_role_id FROM memberships ORDER BY price ASC");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des adhésions : ' . $e->getMessage());
            return [];
        }
    }

    
    public function getUserActiveMemberships(int $userId): array {
        try {
            $currentDate = date('Y-m-d');
            $sql = "SELECT
                        um.id AS user_membership_id, um.start_date, um.end_date, um.purchase_date,
                        m.id AS membership_id, m.name AS membership_name, m.description AS membership_description,
                        m.price AS membership_price, m.duration_days, m.discount_percentage, m.discord_role_id
                    FROM user_memberships um
                    JOIN memberships m ON um.membership_id = m.id
                    WHERE um.user_id = :user_id AND :current_date BETWEEN um.start_date AND um.end_date
                    ORDER BY um.end_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':current_date', $currentDate, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des adhésions actives de l\'utilisateur ID ' . $userId . ': ' . $e->getMessage());
            return [];
        }
    }

    
    public function getMembershipById(int $id) {
        try {
            $sql = "SELECT id, name, description, price, duration_days, discount_percentage, discord_role_id FROM memberships WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération de l\'adhésion avec l\'ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function createUserMembership(int $userId, int $membershipId, string $startDate, string $endDate, string $purchaseDate, string $transactionId): bool {
        try {
            $sql = "INSERT INTO user_memberships (user_id, membership_id, start_date, end_date, purchase_date, transaction_id)
                    VALUES (:user_id, :membership_id, :start_date, :end_date, :purchase_date, :transaction_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':membership_id', $membershipId, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $stmt->bindParam(':purchase_date', $purchaseDate, PDO::PARAM_STR);
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_STR); // Gérer l'ID de transaction null
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la création de l'adhésion utilisateur pour l'utilisateur ID {$userId}, adhésion ID {$membershipId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function getMembershipByName(string $name) {
        try {
            $sql = "SELECT id, name, description, price, duration_days, discount_percentage, discord_role_id FROM memberships WHERE name = :name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération de l\'adhésion avec le nom ' . $name . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function deactivateUserMembership(int $userMembershipId): bool {
        try {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $sql = "UPDATE user_memberships SET end_date = :end_date WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':end_date', $yesterday, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userMembershipId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la désactivation de l\'adhésion utilisateur avec l\'ID ' . $userMembershipId . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function getAllUpcomingEvents(): array {
        try {
            $now = date('Y-m-d H:i:s');
            // Ajout de location à SELECT
            $sql = "SELECT id, title, description, image_path, event_date, location, price, status FROM events WHERE event_date >= :now AND status = 'open' ORDER BY event_date ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':now', $now, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des événements à venir : ' . $e->getMessage());
            return [];
        }
    }

    
    public function getEventById(int $id) {
        try {

            // Ajout de location à SELECT
            $sql = "SELECT id, title, description, image_path, event_date, location, price, points_awarded, status, created_at, updated_at FROM events WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération de l\'événement avec l\'ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function getAllEventsAdmin(): array {
        try {
            // Ajout de location à SELECT
            $sql = "SELECT id, title, description, image_path, event_date, location, price, points_awarded, created_at, updated_at
                    FROM events ORDER BY event_date DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération de tous les événements pour l\'admin : ' . $e->getMessage());
            return [];
        }
    }

    
    public function getUserById(int $id) {
        try {
            $sql = "SELECT id, email, first_name, last_name, is_admin, created_at, membership_status FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération de l\'utilisateur avec l\'ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function getUserByEmail(string $email) {
        try {
            // S'assurer que tous les champs nécessaires à la connexion sont sélectionnés
            $sql = "SELECT id, email, first_name, last_name, password, is_admin, created_at FROM users WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(); // Retourne false si aucun utilisateur n'est trouvé
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération de l\'utilisateur avec l\'email ' . $email . ': ' . $e->getMessage());
            return false; // Retourne false en cas d'erreur de base de données
        }
    }

    
    public function getUserPasswordHashById(int $userId) {
        try {

            $sql = "SELECT password FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération du hash de mot de passe pour l\'utilisateur ID ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function updateUserPassword(int $userId, string $newPasswordHash): bool {
        try {

            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':password', $newPasswordHash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la mise à jour du mot de passe pour l\'utilisateur ID ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }

    
    public function createEventRegistration(int $userId, int $eventId, string $paymentStatus, ?string $transactionId): int|false {
        try {
            $sql = "INSERT INTO event_registrations (user_id, event_id, registration_date, payment_status, transaction_id)
                    VALUES (:user_id, :event_id, CURRENT_TIMESTAMP, :payment_status, :transaction_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':payment_status', $paymentStatus, PDO::PARAM_STR);
            $stmt->bindParam(':transaction_id', $transactionId, $transactionId === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // Gérer l'ID de transaction null
            $success = $stmt->execute();
            return $success ? (int)$this->pdo->lastInsertId() : false; // Retourner lastInsertId en cas de succès
        } catch (\PDOException $e) {
            error_log("Erreur lors de la création de l'inscription à l'événement pour l'utilisateur ID {$userId}, événement ID {$eventId}: " . $e->getMessage());
            return false; // Retourner false en cas d'erreur
        }
    }

    
    public function getEventAttendanceLeaderboard(int $limit = 20): array {
        try {
            $sql = "SELECT
                        u.id AS user_id,
                        u.first_name || ' ' || u.last_name AS username,
                        SUM(e.points_awarded) AS total_points
                    FROM event_registrations er
                    JOIN users u ON er.user_id = u.id
                    JOIN events e ON er.event_id = e.id
                    WHERE er.payment_status = 'completed'
                    GROUP BY u.id, u.first_name, u.last_name
                    ORDER BY total_points DESC
                    LIMIT :limit";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération du classement basé sur les points : ' . $e->getMessage());
            return [];
        }
    }

    
    // Ajout du paramètre $location
    public function insertEvent(string $title, string $description, string $eventDate, ?string $location, float $price, int $pointsAwarded, ?string $imagePath): int|false {
        try {
            // Ajout de location à INSERT
            $sql = "INSERT INTO events (title, description, event_date, location, price, points_awarded, image_path, created_at, updated_at)
                    VALUES (:title, :description, :event_date, :location, :price, :points_awarded, :image_path, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':event_date', $eventDate, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, $location === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // Lier location, gérer null
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':points_awarded', $pointsAwarded, PDO::PARAM_INT);
            $stmt->bindParam(':image_path', $imagePath, $imagePath === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // Gérer le chemin d'image null
            $success = $stmt->execute();
            return $success ? (int)$this->pdo->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'insertion de l'événement '{$title}': " . $e->getMessage());
            return false;
        }
    }

    
    // Ajout du paramètre $location
    public function updateEvent(int $id, string $title, string $description, string $eventDate, ?string $location, float $price, int $pointsAwarded, ?string $imagePath, bool $imageChanged): bool {
        try {
            // Ajout de location à UPDATE
            $sql = "UPDATE events SET
                        title = :title, description = :description, event_date = :event_date, location = :location,
                        price = :price, points_awarded = :points_awarded, updated_at = CURRENT_TIMESTAMP";
            if ($imageChanged) {
                $sql .= ", image_path = :image_path";
            }
            $sql .= " WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':event_date', $eventDate, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, $location === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // Lier location, gérer null
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':points_awarded', $pointsAwarded, PDO::PARAM_INT);
            if ($imageChanged) {
                $stmt->bindParam(':image_path', $imagePath, $imagePath === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // Gérer le chemin d'image null
            }
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'événement ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    
    public function deleteEvent(int $id): bool {
        try {
            $sql = "DELETE FROM events WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la suppression de l'événement ID {$id}: " . $e->getMessage());
            return false;
        }
    }
    
    public function closeEvent(int $id): bool {
        try {
            $sql = "UPDATE events SET status = 'closed' WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la clôture de l'événement ID {$id}: " . $e->getMessage());
            return false;
        }
    } // <-- Correct closing brace added here

    
    public function getOpenEventsAdmin(): array {
        try {
            $sql = "SELECT id, title, description, image_path, event_date, location, price, points_awarded, status, created_at, updated_at
                    FROM events
                    WHERE status = 'open'
                    ORDER BY event_date DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des événements ouverts pour l\'admin : ' . $e->getMessage());
            return [];
        } // Closing brace for catch
    } // Closing brace for getOpenEventsAdmin

    public function getClosedEventsAdmin(): array {
        try {
            $sql = "SELECT id, title, description, image_path, event_date, location, price, points_awarded, status, created_at, updated_at
                    FROM events
                    WHERE status = 'closed'
                    ORDER BY event_date DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des événements clôturés pour l\'admin : ' . $e->getMessage());
            return [];
        }
    }

    public function reopenEvent(int $id): bool {
        try {
            // Vérifier si l'événement est bien 'closed' avant de réouvrir (sécurité optionnelle)
            $event = $this->getEventById($id);
            if (!$event || $event['status'] !== 'closed') {
                 error_log("Tentative de réouverture d'un événement non clôturé ou inexistant ID {$id}.");
                 return false; // Ou lancer une exception
            }

            $sql = "UPDATE events SET status = 'open' WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la réouverture de l'événement ID {$id}: " . $e->getMessage());
            return false;
        }
    }
    
        /**
         * Récupère tous les événements ouverts dont la date est passée de plus d'un jour.
         * @return array Liste des événements ou false en cas d'erreur.
         */
        public function getOpenPastEvents(): array {
            try {
                // Calcule la date et l'heure d'il y a 24 heures
                // SQLite format: YYYY-MM-DD HH:MM:SS
                $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));
    
                $sql = "SELECT id, title, event_date, status
                        FROM events
                        WHERE status = 'open' AND event_date < :one_day_ago";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':one_day_ago', $oneDayAgo, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (\PDOException $e) {
                error_log('Erreur lors de la récupération des événements ouverts passés : ' . $e->getMessage());
                return [];
            }
        }
    
        
        public function updateEventRegistrationStatus(int $registrationId, string $status, ?string $transactionId): bool {
            // Valider le statut
            if (!in_array($status, ['pending', 'completed', 'cancelled'])) {
            error_log("Statut invalide '{$status}' fourni pour l'inscription à l'événement ID {$registrationId}.");
            return false;
        }

        try {
            $sql = "UPDATE event_registrations
                    SET payment_status = :status, transaction_id = :transaction_id
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':transaction_id', $transactionId, $transactionId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':id', $registrationId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour du statut de l'inscription à l'événement pour l'ID {$registrationId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function addPendingEventRegistration(int $eventId, int $userId, string $checkoutRef, string $sumupCheckoutId): bool {
        try {
            // Syntaxe SQLite pour INSERT OR UPDATE
            $sql = "INSERT INTO event_registrations (event_id, user_id, checkout_reference, sumup_checkout_id, payment_status, registration_date)
                    VALUES (:event_id, :user_id, :checkout_reference, :sumup_checkout_id, 'pending', CURRENT_TIMESTAMP)
                    ON CONFLICT(user_id, event_id) DO UPDATE SET
                        checkout_reference = excluded.checkout_reference,
                        sumup_checkout_id = excluded.sumup_checkout_id,
                        payment_status = 'pending',
                        registration_date = CURRENT_TIMESTAMP,
                        transaction_id = NULL"; // Réinitialiser transaction_id lors d'une nouvelle tentative

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':checkout_reference', $checkoutRef, PDO::PARAM_STR);
            $stmt->bindParam(':sumup_checkout_id', $sumupCheckoutId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout/mise à jour de l'inscription d'événement en attente pour l'utilisateur ID {$userId}, événement ID {$eventId}, réf {$checkoutRef}: " . $e->getMessage());
            return false;
        }
    }

    
    public function getRegistrationByCheckoutRef(string $checkoutRef) {
        try {
            $sql = "SELECT id, user_id, event_id, registration_date, payment_status, transaction_id, checkout_reference, sumup_checkout_id
                    FROM event_registrations
                    WHERE checkout_reference = :checkout_reference";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':checkout_reference', $checkoutRef, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération de l'inscription par référence de checkout {$checkoutRef}: " . $e->getMessage());
            return false;
        }
    }

     
    public function updateRegistrationPaymentStatus(string $checkoutRef, string $status, ?string $transactionId): bool {
        // Valider le statut
        if (!in_array($status, ['pending', 'completed', 'failed', 'cancelled'])) { // Ajout de 'failed'
            error_log("Statut invalide '{$status}' fourni pour l'inscription à l'événement avec la réf checkout {$checkoutRef}.");
            return false;
        }

        try {
            $sql = "UPDATE event_registrations
                    SET payment_status = :status, transaction_id = :transaction_id
                    WHERE checkout_reference = :checkout_reference";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':transaction_id', $transactionId, $transactionId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':checkout_reference', $checkoutRef, PDO::PARAM_STR);
            // S'assurer que nous mettons à jour uniquement si le statut change (optionnel, évite les écritures inutiles)
            // $sql .= " AND payment_status != :status";
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour du statut de l'inscription à l'événement pour la réf checkout {$checkoutRef}: " . $e->getMessage());
            return false;
        }
    }

    
    public function getUserRegistrationForEvent(int $userId, int $eventId) {
         try {
            $sql = "SELECT id, user_id, event_id, registration_date, payment_status, transaction_id, checkout_reference, sumup_checkout_id
                    FROM event_registrations
                    WHERE user_id = :user_id AND event_id = :event_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(); // Retourne la ligne ou false
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des détails de l'inscription pour l'utilisateur ID {$userId}, événement ID {$eventId}: " . $e->getMessage());
            return false;
        }
    }



    public function isUserRegisteredForEvent(int $userId, int $eventId): bool {
        try {
            $sql = "SELECT 1 FROM event_registrations WHERE user_id = :user_id AND event_id = :event_id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() !== false; // Retourne true si une ligne est trouvée
        } catch (\PDOException $e) {
            error_log("Erreur lors de la vérification de l'inscription à l'événement pour l'utilisateur ID {$userId}, événement ID {$eventId}: " . $e->getMessage());
            return false; // Supposer non inscrit en cas d'erreur, ou relancer selon le comportement souhaité
        }
    }

    
    public function addEventRegistration(int $eventId, int $userId): bool {
        try {
            // Utilisation de la structure de table existante, définition du statut à completed pour les événements gratuits
            $sql = "INSERT INTO event_registrations (user_id, event_id, registration_date, payment_status, transaction_id)
                    VALUES (:user_id, :event_id, CURRENT_TIMESTAMP, 'completed', NULL)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Gérer gracieusement la violation potentielle de contrainte unique (utilisateur déjà inscrit) si nécessaire,
            // bien que la vérification dans index.php devrait l'empêcher.
            error_log("Erreur lors de l'ajout de l'inscription à l'événement gratuit pour l'utilisateur ID {$userId}, événement ID {$eventId}: " . $e->getMessage());
            return false;
        }
    }

    public function getRegistrationsForEvent(int $eventId): array {
        try {
            $sql = "SELECT
                        er.id AS registration_id,
                        er.registration_date,
                        er.payment_status,
                        er.transaction_id,
                        u.id AS user_id,
                        u.first_name,
                        u.last_name,
                        u.email
                    FROM event_registrations er
                    JOIN users u ON er.user_id = u.id
                    WHERE er.event_id = :event_id
                    ORDER BY er.registration_date ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des inscriptions pour l'événement ID {$eventId}: " . $e->getMessage());
            return []; // Retourner un tableau vide en cas d'erreur
        }
    }
    private function __clone() {}

    public function resetLoginAttempts(int $userId): bool {
        try {
            $sql = "UPDATE users SET failed_login_attempts = 0 WHERE id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error resetting login attempts for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function incrementLoginAttempts(int $userId): int|false {
        $this->pdo->beginTransaction();
        try {
            // Increment the counter
            $sqlInc = "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :user_id";
            $stmtInc = $this->pdo->prepare($sqlInc);
            $stmtInc->bindParam(':user_id', $userId, PDO::PARAM_INT);
            if (!$stmtInc->execute()) {
                $this->pdo->rollBack();
                return false;
            }

            // Get the new count
            $sqlSelect = "SELECT failed_login_attempts FROM users WHERE id = :user_id";
            $stmtSelect = $this->pdo->prepare($sqlSelect);
            $stmtSelect->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtSelect->execute();
            $newCount = $stmtSelect->fetchColumn();

            $this->pdo->commit();
            return ($newCount !== false) ? (int)$newCount : false;

        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error incrementing login attempts for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function lockAccount(int $userId): bool {
        try {
            $sql = "UPDATE users SET is_locked = 1 WHERE id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error locking account for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function unlockAccount(int $userId): bool {
        try {
            $sql = "UPDATE users SET is_locked = 0, failed_login_attempts = 0 WHERE id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error unlocking account for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function setUserLockStatus(int $userId, int $lockStatus): bool {
        if ($lockStatus !== 0 && $lockStatus !== 1) {
             error_log("Invalid lock status provided for user ID {$userId}: {$lockStatus}");
             return false; // Invalid status
        }

        try {
            $sql = "UPDATE users SET is_locked = :lock_status";
            if ($lockStatus === 0) {
                // Also reset attempts when unlocking
                $sql .= ", failed_login_attempts = 0";
            }
            $sql .= " WHERE id = :user_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':lock_status', $lockStatus, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error setting lock status ({$lockStatus}) for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    
    
    public function getAllUsersWithLockStatus(string $searchTerm = ''): array {
        try {
            // Select necessary columns including is_admin
            $sql = "SELECT id, email, first_name, last_name, is_admin, is_locked, failed_login_attempts, created_at
                    FROM users";

            $params = [];
            if (!empty($searchTerm)) {
                // Add WHERE clause for searching
                $sql .= " WHERE email LIKE :search OR first_name LIKE :search OR last_name LIKE :search";
                // Use PDO wildcard placeholder style
                $params[':search'] = '%' . trim($searchTerm) . '%';
            }

            $sql .= " ORDER BY last_name ASC, first_name ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params); // Execute with parameters if search term exists
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des utilisateurs avec statut de verrouillage/admin : ' . $e->getMessage());
             // Check if the error is due to missing columns (optional, based on previous code)
            if (str_contains($e->getMessage(), 'no such column: is_locked') || str_contains($e->getMessage(), 'no such column: failed_login_attempts')) {
                 error_log('Missing is_locked or failed_login_attempts columns in users table. Please update schema.');
            }
            return [];
        }
    }


    public function addUser(string $firstName, string $lastName, string $email, string $hashedPassword, int $isAdmin): bool {
        try {
            $sql = "INSERT INTO users (first_name, last_name, email, password, is_admin)
                    VALUES (:first_name, :last_name, :email, :password, :is_admin)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Log the error, check for duplicate email specifically
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE constraint failed: users.email')) {
                 error_log("Erreur lors de l'ajout de l'utilisateur : L'email '{$email}' existe déjà.");
            } else {
                error_log("Erreur lors de l'ajout de l'utilisateur '{$email}': " . $e->getMessage());
            }
            return false;
        }
    }
    
    public function updateUser(int $userId, array $data): bool {
        $allowedFields = ['email', 'first_name', 'last_name'];
        $fieldsToUpdate = [];
        $params = [':id' => $userId];

        // Build the SET part of the query dynamically
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fieldsToUpdate[] = "{$field} = :{$field}";
                $params[":{$field}"] = trim($data[$field]); // Trim whitespace
            }
        }

        if (empty($fieldsToUpdate)) {
            error_log("Aucun champ valide fourni pour la mise à jour de l'utilisateur ID {$userId}.");
            return false; // No valid fields to update
        }

        // Prevent updating admin status through this method
        if (isset($data['is_admin'])) {
             error_log("Tentative de modification du statut admin via updateUser pour l'utilisateur ID {$userId} bloquée.");
             // Optionally return an error or just ignore the field
        }

        try {
            $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            // Check for unique constraint violation (e.g., email already exists)
            if ($e->getCode() == 23000 || str_contains(strtolower($e->getMessage()), 'unique constraint')) {
                 error_log("Erreur de contrainte d'unicité lors de la mise à jour de l'utilisateur ID {$userId}: " . $e->getMessage());
                 // Consider returning a specific error code or throwing a custom exception
                 return false;
            }
            error_log("Erreur lors de la mise à jour de l'utilisateur ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    
    public function deleteUser(int $userId): bool {
        // First, check if the user is an admin
        $user = $this->getUserById($userId); // Reuse existing method
        if (!$user) {
            error_log("Tentative de suppression d'un utilisateur inexistant ID {$userId}.");
            return false; // User not found
        }
        if ($user['is_admin'] == 1) {
            error_log("Tentative de suppression de l'administrateur ID {$userId} bloquée.");
            return false; // Do not delete admins
        }

        try {
            // Added is_admin = 0 check for extra safety
            $sql = "DELETE FROM users WHERE id = :id AND is_admin = 0";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $success = $stmt->execute();
            // Check if any row was actually deleted
            return $success && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la suppression de l'utilisateur ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    public function permanentlyDeleteEvent(int $eventId): bool {
        try {
            // 1. Get event details to find the image path
            $event = $this->getEventById($eventId);
            if (!$event) {
                error_log("Attempted to delete non-existent event ID {$eventId}.");
                return false; // Event not found
            }

            // 2. Delete the event record from the database
            // Associated registrations will be deleted by ON DELETE CASCADE
            $sql = "DELETE FROM events WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
            $dbDeleteSuccess = $stmt->execute();

            if (!$dbDeleteSuccess) {
                error_log("Failed to delete event ID {$eventId} from database.");
                return false;
            }

            // 3. Delete the associated image file, if it exists
            if (!empty($event['image_path'])) {
                // Construct the full path to the image.
                // image_path is stored as 'uploads/events/filename.ext'
                // __DIR__ is /home/depinfo/Bureau/bde/bdeinfo-site/src
                $imageFullPath = __DIR__ . '/../public/' . $event['image_path'];

                if (file_exists($imageFullPath)) {
                    if (!unlink($imageFullPath)) {
                        error_log("Failed to delete image file: {$imageFullPath} for event ID {$eventId}.");
                        // Continue, as DB deletion was successful, but log the error.
                    }
                } else {
                    // Optional: Log if image file was not found
                    // error_log("Image file not found for event ID {$eventId} at path: {$imageFullPath}");
                }
            }
            return true;
        } catch (\PDOException $e) {
            error_log("Error permanently deleting event ID {$eventId}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) { // Catch other potential errors (e.g., filesystem)
            error_log("General error permanently deleting event ID {$eventId}: " . $e->getMessage());
            return false;
        }
    }

    // Prevent unserialization (Singleton pattern) - __clone might exist elsewhere
    public function __wakeup() {}
}