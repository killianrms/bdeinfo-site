-- Schéma SQL pour la table users (compatible SQLite)

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, -- Changed from TEXT to VARCHAR(255) for password hashing
  first_name TEXT NOT NULL,
  last_name TEXT NOT NULL,
  is_admin INTEGER NOT NULL DEFAULT 0, -- 0 = false, 1 = true
  failed_login_attempts INTEGER NOT NULL DEFAULT 0, -- Added for login attempt tracking
  is_locked INTEGER NOT NULL DEFAULT 0, -- Added for account locking (0 = false, 1 = true)
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  membership_status TEXT NOT NULL DEFAULT 'none' CHECK(membership_status IN ('none', 'support', 'premium'))
);

-- Schéma SQL pour la table memberships (compatible SQLite)
CREATE TABLE IF NOT EXISTS memberships (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  price REAL NOT NULL, -- Modifié depuis DECIMAL
  duration_days INTEGER NOT NULL, -- Modifié depuis INT
  discount_percentage REAL DEFAULT 0.00, -- Modifié depuis DECIMAL
  discord_role_id TEXT NULL, -- Modifié depuis VARCHAR
  created_at TEXT DEFAULT CURRENT_TIMESTAMP -- Modifié depuis TIMESTAMP
);

-- Schéma SQL pour la table user_memberships (compatible SQLite)
CREATE TABLE IF NOT EXISTS user_memberships (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL, -- Modifié depuis INT
  membership_id INTEGER NOT NULL, -- Modifié depuis INT
  start_date TEXT NOT NULL, -- Modifié depuis DATE
  end_date TEXT NOT NULL, -- Modifié depuis DATE
  purchase_date TEXT DEFAULT CURRENT_TIMESTAMP, -- Modifié depuis TIMESTAMP
  transaction_id TEXT NULL, -- Modifié depuis VARCHAR
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE RESTRICT
);

-- Schéma SQL pour la table events (compatible SQLite)
CREATE TABLE IF NOT EXISTS events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL, -- Modifié depuis VARCHAR
  description TEXT,
  image_path TEXT NULL, -- Modifié depuis VARCHAR
  event_date TEXT NOT NULL, -- Modifié depuis TIMESTAMP
  price REAL DEFAULT 0.00, -- Modifié depuis DECIMAL
  location TEXT NULL,       -- AJOUTÉ : Lieu de l'événement
  points_awarded INTEGER NOT NULL DEFAULT 50, -- CONSERVÉ : Points pour le classement
  status TEXT NOT NULL DEFAULT 'open' CHECK(status IN ('open', 'closed')), -- MODIFIÉ : Statut de l'événement (open/closed only)
  created_at TEXT DEFAULT CURRENT_TIMESTAMP, -- Modifié depuis TIMESTAMP
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP -- Modifié depuis TIMESTAMP
);

-- Déclencheur pour mettre à jour le timestamp updated_at (Optionnel mais recommandé)
CREATE TRIGGER IF NOT EXISTS update_events_updated_at
AFTER UPDATE ON events
FOR EACH ROW
BEGIN
    UPDATE events SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Schéma SQL pour la table event_registrations (compatible SQLite)
CREATE TABLE IF NOT EXISTS event_registrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL, -- Modifié depuis INT
  event_id INTEGER NOT NULL, -- Modifié depuis INT
  registration_date TEXT DEFAULT CURRENT_TIMESTAMP, -- Modifié depuis TIMESTAMP
  payment_status TEXT DEFAULT 'pending' CHECK (payment_status IN ('pending', 'completed', 'failed', 'cancelled')), -- Ajout du statut 'failed'
  transaction_id TEXT NULL, -- Modifié depuis VARCHAR, stocke l'ID de transaction SumUp
  checkout_reference TEXT UNIQUE, -- Ajouté : Référence unique pour cette tentative de paiement
  sumup_checkout_id TEXT NULL, -- Ajouté : ID de paiement interne de SumUp
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  UNIQUE(user_id, event_id) -- Ajouté : Empêche les inscriptions en double par utilisateur/événement
);
-- Schéma SQL pour les transactions SumUp en attente
CREATE TABLE IF NOT EXISTS pending_sumup_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  checkout_reference TEXT NOT NULL UNIQUE,
  user_id INTEGER NOT NULL,
  membership_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed')),
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE RESTRICT
);

-- Déclencheur pour mettre à jour le timestamp updated_at pour pending_sumup_transactions
CREATE TRIGGER IF NOT EXISTS update_pending_sumup_transactions_updated_at
AFTER UPDATE ON pending_sumup_transactions
FOR EACH ROW
BEGIN
    UPDATE pending_sumup_transactions SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;