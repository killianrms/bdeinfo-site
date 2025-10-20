<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';


require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/EmailService.php';
require_once __DIR__ . '/../src/QRCodeService.php';
require_once __DIR__ . '/../config/sumup.php';

// Utilisation des déclarations du SDK SumUp
use SumUp\SumUp;
use SumUp\Exceptions\SumUpAuthenticationException;
use SumUp\Exceptions\SumUpResponseException;
use SumUp\Exceptions\SumUpSDKException;
use SumUp\Services\Checkouts; // Spécifiquement pour les paiements

define('TEMPLATE_PATH', __DIR__ . '/../templates/');


// Calculer le chemin relatif au répertoire du script
$fullPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($fullPath === false) {
    // Gérer l'échec de parse_url, peut-être enregistrer une erreur ou utiliser '/' par défaut
    error_log("Échec de l'analyse de REQUEST_URI : " . $_SERVER['REQUEST_URI']);
    $fullPath = '/';
}

$scriptName = $_SERVER['SCRIPT_NAME']; // ex: /subdir/index.php ou /index.php
$basePath = dirname($scriptName); // ex: /subdir ou /

// Normaliser basePath pour le répertoire racine et les éventuels backslashes sous Windows
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

// Calculer le chemin relatif au chemin de base
$routePath = $fullPath;
// S'assurer que basePath n'est pas vide et que fullPath commence réellement par celui-ci
if ($basePath !== '' && strpos($fullPath, $basePath) === 0) {
    $routePath = substr($fullPath, strlen($basePath));
}

// S'assurer que le chemin de la route commence par un '/' et gérer le chemin vide pour la racine
// Si $routePath est vide après avoir supprimé le chemin de base, cela signifie que nous sommes à la racine
if (empty($routePath)) {
    $routePath = '/';
} elseif ($routePath[0] !== '/') {
    // S'assurer qu'il commence par un slash s'il n'est pas vide
    $routePath = '/' . $routePath;
}
// Utiliser $routePath pour la logique de routage ci-dessous
$method = $_SERVER['REQUEST_METHOD'];

$page_content = '';
$layout_vars = [];


if (strpos($routePath, '/admin/') === 0) { // Utiliser le chemin de route calculé

    if (!isset($_SESSION['user_id'])) {
        // Rediriger en utilisant l'URI de requête complet original pour préserver les paramètres de requête, etc.
        header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }


    try {
        $db = Database::getInstance();
        $user = $db->getUserById($_SESSION['user_id']);


        if (!$user || !isset($user['is_admin']) || $user['is_admin'] != 1) {


            $_SESSION['message'] = "Accès non autorisé.";
            $_SESSION['message_type'] = 'error';
            header('Location: /home');
            exit;
        }

        $layout_vars['is_admin_page'] = true;

    } catch (Exception $e) {
        error_log("Erreur de vérification d'accès admin pour l'utilisateur ID {$_SESSION['user_id']}: " . $e->getMessage());

        http_response_code(500);

        echo "Une erreur interne est survenue. Impossible de vérifier les permissions administrateur.";
        exit;
    }
}

// --- Correspondance des Routes ---
$matched = false;

// --- Routes Paramétrées (Vérifiées en Premier) ---

// GET /events/{id} - Détail Public de l'Événement
if (!$matched && $method === 'GET' && preg_match('#^/events/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    try {
        $db = Database::getInstance();
        $event = $db->getEventById($eventId); // Déjà mis à jour pour récupérer le lieu
        $discounted_price = null;
        $discount_percentage = 0;

        if ($event && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $user = $db->getUserById($userId); // Récupérer les données utilisateur y compris membership_status
            $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
            $original_price = (float)$event['price'];

            if ($isAdmin) {
                // Admin : Trouver le pourcentage de réduction le plus élevé disponible parmi toutes les adhésions
                $allMemberships = $db->getAllMemberships(); // Récupérer toutes les adhésions
                $maxDiscount = 0;
                foreach ($allMemberships as $membership) {
                    if (isset($membership['discount_percentage']) && (float)$membership['discount_percentage'] > $maxDiscount) {
                        $maxDiscount = (float)$membership['discount_percentage'];
                    }
                }
                if ($maxDiscount > 0 && $original_price > 0) {
                    $discount_percentage = $maxDiscount;
                    $discounted_price = $original_price * (1 - ($discount_percentage / 100));
                }
            } elseif ($user && $user['membership_status'] === 'premium') {
                // Utilisateur Premium : Appliquer 10% de réduction
                if ($original_price > 0) {
                    $discount_percentage = 10;
                    $discounted_price = $original_price * (1 - ($discount_percentage / 100));
                }
            } else {
                // Non Admin & Non Premium : Utiliser la logique existante basée sur leur adhésion active
                $activeMemberships = $db->getUserActiveMemberships($userId);
                if (!empty($activeMemberships)) {
                    $activeMembership = $activeMemberships[0]; // Utiliser la première active
                    if (isset($activeMembership['discount_percentage']) && $activeMembership['discount_percentage'] > 0) {
                        $member_discount = (float)$activeMembership['discount_percentage'];
                        if ($original_price > 0) {
                            $discount_percentage = $member_discount; // Définir discount_percentage pour l'affichage
                            $discounted_price = $original_price * (1 - ($member_discount / 100));
                        }
                    }
                }
            }
        }

        $layout_vars['event'] = $event;
        $layout_vars['discounted_price'] = $discounted_price;
        $layout_vars['discount_percentage'] = $discount_percentage;
        
        // Vérifier si l'utilisateur est déjà inscrit à cet événement
        $user_registration = null;
        if ($event && isset($_SESSION['user_id'])) {
            $user_registration = $db->getUserRegistrationForEvent($_SESSION['user_id'], $eventId);
        }
        $layout_vars['user_registration'] = $user_registration;

        if (!$event || (isset($event['status']) && $event['status'] !== 'open')) {
             http_response_code(404); // Événement non trouvé
             $page_content = TEMPLATE_PATH . '404.php';
             $layout_vars['title'] = "Événement non trouvé";
        } else {
            $layout_vars['page_title'] = $event['title']; // Définir le titre de la page pour les détails de l'événement
            $page_content = TEMPLATE_PATH . 'event_details.php';
        }

    } catch (Exception $e) {
        error_log("Erreur lors du chargement de la page de détail de l'événement pour l'ID {$eventId}: " . $e->getMessage());
        http_response_code(500);
        $page_content = TEMPLATE_PATH . '500.php'; // Optionnel : Créer un template 500
        $layout_vars['error_message'] = "Une erreur est survenue lors du chargement de l'événement.";
    }
}

// POST /events/{id}/register - Gérer l'Inscription à un Événement Gratuit
elseif (!$matched && $method === 'POST' && preg_match('#^/events/(\d+)/register$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];

    // 1. Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = ['type' => 'warning', 'text' => "Veuillez vous connecter pour vous inscrire."];
        // Rediriger vers la page de l'événement, la connexion gérera le reste
        header('Location: /events/' . $eventId);
        exit;
    }
    $userId = $_SESSION['user_id'];

    try {
        $db = Database::getInstance();

        // 2. Récupérer les détails de l'événement
        $event = $db->getEventById($eventId);

        // 3. Vérifier que l'événement existe et est gratuit
        if (!$event) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "L'événement demandé n'existe pas."];
            header('Location: /events'); // Rediriger vers la liste des événements si l'événement n'est pas trouvé
            exit;
        }

        $eventPrice = isset($event['price']) ? (float)$event['price'] : 0.0;
        if ($eventPrice > 0) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => "Cet événement n'est pas gratuit. Le paiement est requis."];
            header('Location: /events/' . $eventId);
            exit;
        }

        // 4. Vérifier si l'utilisateur est déjà inscrit (Nécessite une nouvelle méthode DB)
        $isAlreadyRegistered = $db->isUserRegisteredForEvent($userId, $eventId); // En supposant que cette méthode existe/sera créée
        if ($isAlreadyRegistered) {
            $_SESSION['message'] = ['type' => 'info', 'text' => "Vous êtes déjà inscrit à cet événement."];
            header('Location: /events/' . $eventId);
            exit;
        }

        // 5. Ajouter l'enregistrement d'inscription (Nécessite une nouvelle méthode DB)
        $registrationSuccess = $db->addEventRegistration($eventId, $userId); // En supposant que cette méthode existe/sera créée

        if ($registrationSuccess) {
            // Envoyer email de confirmation
            $user = $db->getUserById($userId);
            if ($user) {
                EmailService::sendEventRegistrationConfirmation(
                    $user['email'],
                    $user['first_name'],
                    $event['title'],
                    $event['event_date'],
                    $event['location'] ?? 'À définir',
                    0,
                    true
                );
            }

            // Utiliser le format demandé
            $_SESSION['message'] = ['type' => 'success', 'text' => "Merci ! Votre inscription à l'événement '" . htmlspecialchars($event['title']) . "' a été enregistrée."];
        } else {
            // Utiliser le format et le message demandés
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Une erreur est survenue lors de l'inscription. Veuillez réessayer."];
        }

    } catch (Exception $e) {
        error_log("Erreur lors de l'inscription à l'événement gratuit pour l'ID d'événement {$eventId}, ID utilisateur {$userId}: " . $e->getMessage());
        // Utiliser le format demandé et un message d'erreur générique
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Une erreur technique est survenue lors de l'inscription. Veuillez réessayer."];
    }

    // 6. Rediriger vers la page de détails de l'événement
    header('Location: /events/' . $eventId);
    exit;
}

// GET /tickets/{registrationId} - Afficher le ticket
elseif (!$matched && $method === 'GET' && preg_match('#^/tickets/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $registrationId = (int)$matches[1];

    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = ['type' => 'warning', 'text' => "Veuillez vous connecter pour voir votre ticket."];
        header('Location: /login');
        exit;
    }

    $userId = $_SESSION['user_id'];

    try {
        $db = Database::getInstance();

        // Récupérer l'inscription avec les détails de l'événement
        $registration = $db->getConnection()->prepare("
            SELECT er.*, e.*, u.first_name, u.last_name, u.email
            FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            JOIN users u ON er.user_id = u.id
            WHERE er.id = :reg_id AND er.user_id = :user_id
        ");
        $registration->execute([':reg_id' => $registrationId, ':user_id' => $userId]);
        $data = $registration->fetch();

        if (!$data) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Ticket introuvable ou accès non autorisé."];
            header('Location: /account');
            exit;
        }

        // Vérifier que le paiement est confirmé
        if ($data['payment_status'] !== 'completed' && $data['price'] > 0) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => "Le paiement de cet événement n'est pas encore confirmé."];
            header('Location: /account');
            exit;
        }

        // Générer le ticket
        $event = [
            'id' => $data['event_id'],
            'title' => $data['title'],
            'event_date' => $data['event_date'],
            'location' => $data['location']
        ];
        $user = [
            'id' => $userId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name']
        ];

        $ticketHTML = QRCodeService::generateTicketHTML($event, $user, $registrationId);
        echo $ticketHTML;
        exit;

    } catch (Exception $e) {
        error_log("Erreur lors de la génération du ticket: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Une erreur est survenue lors de la génération du ticket."];
        header('Location: /account');
        exit;
    }
}

// POST /events/{id}/pay - Gérer l'Inscription Payante à un Événement via SumUp
elseif (!$matched && $method === 'POST' && preg_match('#^/events/(\d+)/pay$#', $routePath, $matches)) {
    error_log("[DEBUG] SumUp Pay Route: Entered POST /events/{$matches[1]}/pay route handler."); // New log
    $matched = true;
    $eventId = (int)$matches[1];

    // 1. Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "Veuillez vous connecter pour payer votre inscription.";
        $_SESSION['message_type'] = 'warning';
        header('Location: /login?redirect=/events/' . $eventId);
        exit;
    }
    $userId = $_SESSION['user_id'];

    try {
        $db = Database::getInstance();

        // 2. Récupérer les détails de l'événement (y compris le prix)
        $event = $db->getEventById($eventId);
        if (!$event) {
            $_SESSION['message'] = "L'événement demandé n'existe pas.";
            $_SESSION['message_type'] = 'error';
            header('Location: /events');
            exit;
        }

        // Calculer le prix que l'utilisateur doit payer (en tenant compte des réductions)
        $original_price = (float)$event['price'];
        $price_to_pay = $original_price; // Par défaut, le prix original
        $discount_percentage = 0;

        if ($original_price <= 0) {
             $_SESSION['message'] = "Cet événement est gratuit, vous pouvez vous inscrire directement.";
             $_SESSION['message_type'] = 'info';
             header('Location: /events/' . $eventId); // Rediriger vers la page de l'événement pour utiliser l'inscription gratuite
             exit;
        }

        // Appliquer les réductions (logique similaire à GET /events/{id})
        $user = $db->getUserById($userId); // Récupérer les détails de l'utilisateur y compris l'email
        if (!$user) {
             $_SESSION['message'] = "Utilisateur non trouvé.";
             $_SESSION['message_type'] = 'error';
             header('Location: /login?redirect=/events/' . $eventId);
             exit;
        }
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

        if ($isAdmin) {
            $allMemberships = $db->getAllMemberships();
            $maxDiscount = 0;
            foreach ($allMemberships as $membership) {
                if (isset($membership['discount_percentage']) && (float)$membership['discount_percentage'] > $maxDiscount) {
                    $maxDiscount = (float)$membership['discount_percentage'];
                }
            }
            if ($maxDiscount > 0) {
                $discount_percentage = $maxDiscount;
                $price_to_pay = $original_price * (1 - ($discount_percentage / 100));
            }
        } elseif ($user['membership_status'] === 'premium') {
             $discount_percentage = 10; // En supposant 10% pour premium
             $price_to_pay = $original_price * (1 - ($discount_percentage / 100));
        } else {
            $activeMemberships = $db->getUserActiveMemberships($userId);
            if (!empty($activeMemberships)) {
                $activeMembership = $activeMemberships[0];
                if (isset($activeMembership['discount_percentage']) && $activeMembership['discount_percentage'] > 0) {
                    $member_discount = (float)$activeMembership['discount_percentage'];
                    $discount_percentage = $member_discount;
                    $price_to_pay = $original_price * (1 - ($member_discount / 100));
                }
            }
        }
        // S'assurer que le prix est correctement formaté (ex: 2 décimales)
        $price_to_pay = round($price_to_pay, 2);

        // 3. Vérifier si l'utilisateur est déjà inscrit et le statut du paiement
        // Suppose que getUserRegistrationForEvent retourne la ligne d'inscription ou null
        $registration = $db->getUserRegistrationForEvent($userId, $eventId);
        if ($registration) {
            if ($registration['payment_status'] === 'completed') {
                $_SESSION['message'] = "Vous êtes déjà inscrit et avez payé pour cet événement.";
                $_SESSION['message_type'] = 'info';
                header('Location: /events/' . $eventId);
                exit;
            } elseif ($registration['payment_status'] === 'pending') {
                 $_SESSION['message'] = "Votre paiement pour cet événement est en attente. Veuillez vérifier vos emails ou contacter le support si le problème persiste.";
                 $_SESSION['message_type'] = 'warning';
                 header('Location: /events/' . $eventId);
                 exit;
            }
            // Autoriser la poursuite si le statut est 'failed', null ou vide
        }

        // 4. Instancier le SDK SumUp
        // S'assurer que les constantes SUMUP sont chargées depuis config/sumup.php
        if (!defined('SUMUP_CLIENT_ID') || !defined('SUMUP_CLIENT_SECRET') || !defined('SUMUP_MERCHANT_CODE') || !defined('BASE_URL')) {
             throw new Exception("Les constantes de configuration SumUp ne sont pas définies.");
        }
        error_log("[DEBUG] SumUp Pay Route: Attempting to instantiate SumUp SDK for event {$eventId}, user {$userId}. CLIENT_ID: " . SUMUP_CLIENT_ID);
        $sumup = new SumUp([
            'app_id'     => SUMUP_CLIENT_ID,
            'app_secret' => SUMUP_CLIENT_SECRET,
            'grant_type' => 'client_credentials'
        ]);
        error_log("[DEBUG] SumUp Pay Route: SumUp SDK instantiated. CLIENT_ID: " . SUMUP_CLIENT_ID);

        // 5. Créer le Checkout SumUp
        $checkoutService = $sumup->getCheckoutService();
        error_log("[DEBUG] SumUp Pay Route: Checkout service obtained. Attempting to create checkout. Event: {$eventId}, User: {$userId}, Price: {$price_to_pay}, Merchant: " . SUMUP_MERCHANT_CODE);
        $checkoutReference = 'EVENT_' . $eventId . '_USER_' . $userId . '_' . time();
        // Définir les URL de succès et d'annulation pour utiliser le callback unifié
        $callbackUrl = rtrim(BASE_URL, '/') . '/payment/callback?ref=' . urlencode($checkoutReference);
        
        $checkoutData = [
            'checkout_reference' => $checkoutReference,
            'amount'             => $price_to_pay,
            'currency'           => 'EUR', // En supposant EUR, rendre configurable si nécessaire
            'pay_to_email'       => $user['email'], // Pré-remplir l'email de l'utilisateur
            'description'        => 'Inscription: ' . htmlspecialchars($event['title']), // Utiliser htmlspecialchars
            'merchant_code'      => SUMUP_MERCHANT_CODE, // Depuis la config
            'return_url'         => $callbackUrl, // URL de retour unique pour succès et annulation
        ];

        $checkoutResponse = $checkoutService->create($checkoutData);
        error_log("[DEBUG] SumUp Pay Route: Checkout creation attempted. Response ID: " . ($checkoutResponse->id ?? 'NULL'));

        // Vérifier si la création du checkout a réussi et si nous avons un ID et une URL
        if (empty($checkoutResponse->id) || empty($checkoutResponse->pay_to_url)) {
            throw new SumUpResponseException("Échec de la création de la session de paiement SumUp. Réponse invalide reçue.");
        }

        // 6. Créer une Inscription en Attente dans la DB
        // Suppose que addPendingEventRegistration gère INSERT/UPDATE et retourne true/false
        // Passer l'ID de checkout SumUp pour référence future potentielle
        $pendingSuccess = $db->addPendingEventRegistration($eventId, $userId, $checkoutReference, $checkoutResponse->id);

        if (!$pendingSuccess) {
             // Enregistrer l'erreur, informer l'utilisateur
             error_log("Échec de la création/mise à jour de l'inscription en attente pour l'événement $eventId, utilisateur $userId, réf checkout $checkoutReference");
             $_SESSION['message'] = "Une erreur interne est survenue avant de procéder au paiement. Veuillez réessayer.";
             $_SESSION['message_type'] = 'error';
             header('Location: /events/' . $eventId);
             exit;
        }

        // 7. Rediriger l'utilisateur vers la page de paiement SumUp
        header('Location: ' . $checkoutResponse->pay_to_url);
        exit;

    } catch (SumUpAuthenticationException $e) {
        error_log("Erreur d'Authentification SumUp : " . $e->getMessage());
        $_SESSION['message'] = "Erreur d'authentification avec le service de paiement. Veuillez contacter le support.";
        $_SESSION['message_type'] = 'error';
        header('Location: /events/' . $eventId);
        exit;
    } catch (SumUpResponseException $e) {
        error_log("Erreur de Réponse API SumUp : " . $e->getMessage() . " | Corps : " . $e->getBody());
        $_SESSION['message'] = "Erreur de communication avec le service de paiement (" . $e->getCode() . "). Veuillez réessayer.";
        $_SESSION['message_type'] = 'error';
        header('Location: /events/' . $eventId);
        exit;
    } catch (SumUpSDKException $e) {
        error_log("Erreur SDK SumUp : " . $e->getMessage());
        $_SESSION['message'] = "Erreur technique avec le service de paiement. Veuillez réessayer.";
        $_SESSION['message_type'] = 'error';
        header('Location: /events/' . $eventId);
        exit;
    } catch (Exception $e) {
        error_log("Erreur Générale lors de l'initiation du paiement de l'événement pour l'ID d'événement {$eventId}, ID utilisateur {$userId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur technique est survenue (" . $e->getCode() . ").";
        $_SESSION['message_type'] = 'error';
        header('Location: /events/' . $eventId);
        exit;
    }
}


// GET /admin/events/registrations?id={id} - Admin Voir les Inscriptions à l'Événement
elseif (!$matched && $method === 'GET' && $routePath === '/admin/events/registrations') {
    $matched = true;
    // Vérification admin déjà effectuée en haut

    // Valider et obtenir l'ID de l'événement depuis le paramètre de requête
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
        $_SESSION['message'] = "ID d'événement manquant ou invalide.";
        $_SESSION['message_type'] = 'error';
        header('Location: /admin/events');
        exit;
    }
    $eventId = (int)$_GET['id'];

    try {
        $db = Database::getInstance();
        $event = $db->getEventById($eventId); // Suppose que cette méthode existe

        if (!$event) {
            $_SESSION['message'] = "Événement non trouvé pour l'ID " . $eventId . ".";
            $_SESSION['message_type'] = 'error';
            header('Location: /admin/events');
            exit;
        }

        // Récupérer les inscriptions en utilisant la méthode existante qui joint déjà avec la table users
        $registrations = $db->getRegistrationsForEvent($eventId);

        $layout_vars['page_title'] = 'Inscriptions pour : ' . htmlspecialchars($event['title']);
        $layout_vars['event'] = $event;
        $layout_vars['registrations'] = $registrations; // Passer les inscriptions au template
        $page_content = TEMPLATE_PATH . 'admin/event_registrations.php'; // Pointer vers le template

    } catch (Exception $e) {
        error_log("Erreur lors du chargement de la page des inscriptions à l'événement pour l'ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur est survenue lors du chargement des inscriptions.";
        $_SESSION['message_type'] = 'error';
        header('Location: /admin/events'); // Rediriger vers la liste des événements en cas d'erreur
        exit;
    }
}


// GET /admin/events/{id}/export-csv - Export CSV des participants
elseif (!$matched && $method === 'GET' && preg_match('#^/admin/events/(\d+)/export-csv$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    // Vérification admin déjà effectuée en haut

    try {
        $db = Database::getInstance();
        $event = $db->getEventById($eventId);

        if (!$event) {
            $_SESSION['message'] = "Événement non trouvé.";
            $_SESSION['message_type'] = 'error';
            header('Location: /admin/events');
            exit;
        }

        $registrations = $db->getRegistrationsForEvent($eventId);

        // Générer le nom du fichier
        $filename = 'inscriptions_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($event['title'])) . '_' . date('Y-m-d') . '.csv';

        // Headers pour le téléchargement
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Ouvrir le flux de sortie
        $output = fopen('php://output', 'w');

        // Ajouter le BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes du CSV
        fputcsv($output, [
            'ID',
            'Prénom',
            'Nom',
            'Email',
            'Date d\'inscription',
            'Statut paiement',
            'ID Transaction'
        ], ';');

        // Données
        foreach ($registrations as $reg) {
            fputcsv($output, [
                $reg['registration_id'],
                $reg['first_name'],
                $reg['last_name'],
                $reg['email'],
                date('d/m/Y H:i', strtotime($reg['registration_date'])),
                ucfirst($reg['payment_status']),
                $reg['transaction_id'] ?? 'N/A'
            ], ';');
        }

        fclose($output);
        exit;

    } catch (Exception $e) {
        error_log("Erreur lors de l'export CSV pour l'événement ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur est survenue lors de l'export.";
        $_SESSION['message_type'] = 'error';
        header('Location: /admin/events');
        exit;
    }
}


// GET /admin/events/{id}/registrations - Admin Voir les Inscriptions à l'Événement
elseif (!$matched && $method === 'GET' && preg_match('#^/admin/events/(\d+)/registrations$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    // Vérification admin déjà effectuée en haut

    try {
        $db = Database::getInstance();
        $event = $db->getEventById($eventId);

        if (!$event) {
            $_SESSION['message'] = "Événement non trouvé.";
            $_SESSION['message_type'] = 'error';
            header('Location: /admin/events');
            exit;
        }

        // Récupérer les inscriptions en utilisant la nouvelle méthode
        $registrations = $db->getRegistrationsForEvent($eventId);

        $layout_vars['page_title'] = 'Inscriptions : ' . htmlspecialchars($event['title']);
        $layout_vars['event'] = $event;
        $layout_vars['registrations'] = $registrations;
        $page_content = TEMPLATE_PATH . 'admin/event_registrations.php'; // Chemin vers le nouveau template

    } catch (Exception $e) {
        error_log("Erreur lors du chargement de la page des inscriptions à l'événement pour l'ID d'événement {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Erreur lors du chargement des inscriptions pour l'événement.";
        $_SESSION['message_type'] = 'error';
        header('Location: /admin/events');
        exit;
    }
}
// POST /admin/events/close/{id} - Logique de Clôture d'Événement Admin
elseif (!$matched && $method === 'POST' && preg_match('#^/admin/events/close/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];

    try {
        $db = Database::getInstance();

        // Mettre à jour le statut de l'événement à 'closed'
        $closeSuccess = $db->closeEvent($eventId); // Nouvelle méthode à implémenter dans Database.php

        if ($closeSuccess) {
            $_SESSION['message'] = "Événement clôturé avec succès et déplacé vers l'historique.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Échec de la clôture de l'événement.";
            $_SESSION['message_type'] = 'error';
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la clôture de l'événement ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur est survenue lors de la clôture de l'événement.";
        $_SESSION['message_type'] = 'error';
    }

    // Rediriger vers la liste des événements admin après la tentative de clôture
    header('Location: /admin/events');
    exit;
}

// POST /admin/events/reopen/{id} - Logique de Réouverture d'Événement Admin
elseif (!$matched && $method === 'POST' && preg_match('#^/admin/events/reopen/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    // La vérification Admin est déjà faite en haut du routeur pour les routes /admin/*

    try {
        $db = Database::getInstance();
        $eventBeforeReopen = $db->getEventById($eventId);

        if (!$eventBeforeReopen) {
            $_SESSION['message'] = "L'événement ID {$eventId} n'existe pas.";
            $_SESSION['message_type'] = 'error';
            header('Location: /admin/events/history');
            exit;
        }

        if ($eventBeforeReopen['status'] !== 'closed') {
            $_SESSION['message'] = "L'événement '" . htmlspecialchars($eventBeforeReopen['title']) . "' n'est pas actuellement clôturé. Aucune action n'a été effectuée.";
            $_SESSION['message_type'] = 'warning';
            header('Location: /admin/events/history');
            exit;
        }

        $reopenSuccess = $db->reopenEvent($eventId);

        if ($reopenSuccess) {
            $_SESSION['message'] = "L'événement '" . htmlspecialchars($eventBeforeReopen['title']) . "' a été rouvert avec succès et déplacé vers la liste des événements actifs.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Erreur lors de la tentative de réouverture de l'événement '" . htmlspecialchars($eventBeforeReopen['title']) . "'.";
            $_SESSION['message_type'] = 'error';
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la réouverture de l'événement ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur technique est survenue lors de la réouverture de l'événement.";
        $_SESSION['message_type'] = 'error';
    }

    // Rediriger vers l'historique des événements ou la liste des événements actifs
    header('Location: /admin/events/history'); // Ou /admin/events pour voir l'événement rouvert
    exit;
}

// POST /admin/events/delete/{id} - Logique de Suppression Permanente d'Événement Admin
elseif (!$matched && $method === 'POST' && preg_match('#^/admin/events/delete/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    // La vérification Admin est déjà faite en haut du routeur pour les routes /admin/*

    try {
        $db = Database::getInstance();
        $eventToDelete = $db->getEventById($eventId); // Get event details for messages and image path

        if (!$eventToDelete) {
            $_SESSION['message'] = "L'événement ID {$eventId} n'existe pas ou a déjà été supprimé.";
            $_SESSION['message_type'] = 'error';
        } else {
            // At this point, the JS confirm() on the button should have already happened.
            // We proceed directly with deletion.
            $deleteSuccess = $db->permanentlyDeleteEvent($eventId);

            if ($deleteSuccess) {
                $_SESSION['message'] = "L'événement '" . htmlspecialchars($eventToDelete['title']) . "' et toutes ses données associées (inscriptions, image) ont été supprimés définitivement.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Erreur lors de la tentative de suppression permanente de l'événement '" . htmlspecialchars($eventToDelete['title']) . "'.";
                $_SESSION['message_type'] = 'error';
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression permanente de l'événement ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur technique est survenue lors de la suppression permanente de l'événement.";
        $_SESSION['message_type'] = 'error';
    }

    // Toujours rediriger vers l'historique des événements après la tentative de suppression
    header('Location: /admin/events/history');
    exit;
}

// POST /admin/events/archive/{id} - Archiver (supprimer définitivement) un événement clôturé
elseif (!$matched && $method === 'POST' && preg_match('#^/admin/events/archive/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    try {
        $db = Database::getInstance();
        $event = $db->getEventById($eventId); // Récupérer les détails pour l'image et vérifier le statut

        if (!$event) {
            $_SESSION['message'] = "Événement non trouvé.";
            $_SESSION['message_type'] = 'error';
        } elseif ($event['status'] !== 'closed') {
            $_SESSION['message'] = "Seuls les événements clôturés peuvent être archivés.";
            $_SESSION['message_type'] = 'warning';
        } else {
            $imagePathToDelete = null;
            if (!empty($event['image_path'])) {
                $imagePathToDelete = $event['image_path'];
            }

            // Supprimer l'enregistrement de l'événement de la base de données
            $deleteSuccess = $db->deleteEvent($eventId); // Utiliser la suppression existante

            if ($deleteSuccess) {
                // Supprimer le fichier image associé, s'il existe
                if ($imagePathToDelete) {
                    $upload_dir = __DIR__ . '/uploads/events/';
                    $fullImagePath = $upload_dir . $imagePathToDelete;
                    if (file_exists($fullImagePath)) {
                        if (!unlink($fullImagePath)) {
                            error_log("Impossible de supprimer le fichier image de l'événement archivé : " . $fullImagePath);
                            $_SESSION['message'] = "Événement archivé (supprimé de la DB), mais l'image associée n'a pas pu être supprimée.";
                            $_SESSION['message_type'] = 'warning';
                        } else {
                            $_SESSION['message'] = "Événement et image associée archivés (supprimés) avec succès.";
                            $_SESSION['message_type'] = 'success';
                        }
                    } else {
                        $_SESSION['message'] = "Événement archivé (supprimé) avec succès (image associée non trouvée).";
                        $_SESSION['message_type'] = 'success';
                    }
                } else {
                    $_SESSION['message'] = "Événement archivé (supprimé) avec succès (aucune image associée).";
                    $_SESSION['message_type'] = 'success';
                }
            } else {
                $_SESSION['message'] = "Échec de l'archivage (suppression) de l'événement.";
                $_SESSION['message_type'] = 'error';
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'archivage de l'événement ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur technique est survenue lors de l'archivage.";
        $_SESSION['message_type'] = 'error';
    }
    // Rediriger vers l'historique après la tentative d'archivage
    header('Location: /admin/events/history');
    exit;
}
// POST /admin/events/reopen/{id} - Réouvrir un événement clôturé
elseif (!$matched && $method === 'POST' && preg_match('#^/admin/events/reopen/(\d+)$#', $routePath, $matches)) {
    $matched = true;
    $eventId = (int)$matches[1];
    try {
        $db = Database::getInstance();
        $event = $db->getEventById($eventId); // Vérifier le statut actuel

        if (!$event) {
            $_SESSION['message'] = "Événement non trouvé.";
            $_SESSION['message_type'] = 'error';
        } elseif ($event['status'] !== 'closed') {
            $_SESSION['message'] = "Seuls les événements clôturés peuvent être réouverts.";
            $_SESSION['message_type'] = 'warning';
        } else {
            // Mettre à jour le statut de l'événement à 'open'
            $reopenSuccess = $db->reopenEvent($eventId); // Nouvelle méthode à implémenter

            if ($reopenSuccess) {
                $_SESSION['message'] = "Événement réouvert avec succès.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Échec de la réouverture de l'événement.";
                $_SESSION['message_type'] = 'error';
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la réouverture de l'événement ID {$eventId}: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur technique est survenue lors de la réouverture.";
        $_SESSION['message_type'] = 'error';
    }
    // Rediriger vers l'historique après la tentative de réouverture
    header('Location: /admin/events/history');
    exit;
}
// --- Routes Statiques (Vérifiées Après les Routes Paramétrées) ---
if (!$matched) {
    switch ($routePath) {
        case '/':
        case '/home':
            $matched = true;
            try {
                $db = Database::getInstance();
                // Récupérer les événements à venir au lieu du classement
                $upcomingEvents = $db->getAllUpcomingEvents();
                $layout_vars['upcoming_events'] = $upcomingEvents;
            } catch (Exception $e) {
                error_log("Erreur lors de la récupération des événements à venir pour la page d'accueil : " . $e->getMessage());
                $layout_vars['upcoming_events'] = [];
                $layout_vars['error_message'] = "Impossible de charger les événements à venir.";
            }
            $layout_vars['page_title'] = "Accueil"; // Définir le titre de la page d'accueil
            $page_content = TEMPLATE_PATH . 'home.php';
            break;

        // GET /payment/callback - Gestionnaire de Callback de Paiement SumUp
        case '/payment/callback':
            $matched = true;
            if ($method === 'GET') {
                $checkoutReference = $_GET['ref'] ?? null;
                $sumupCheckoutId = $_GET['id'] ?? null; // SumUp pourrait aussi envoyer son propre ID

                if (empty($checkoutReference)) {
                    error_log("Erreur Callback SumUp : Paramètre 'ref' (checkout_reference) manquant.");
                    $_SESSION['message'] = "Erreur lors du retour du paiement (Référence manquante).";
                    $_SESSION['message_type'] = 'error';
                    header('Location: /home'); // Rediriger vers une page sûre
                    exit;
                }

                try {
                    $db = Database::getInstance();

                    // 1. Trouver l'inscription en attente en utilisant la référence de checkout
                    $registration = $db->getRegistrationByCheckoutRef($checkoutReference); // Nécessite une nouvelle méthode DB

                    if (!$registration) {
                        error_log("Erreur Callback SumUp : Aucune inscription trouvée pour checkout_reference : " . $checkoutReference);
                        $_SESSION['message'] = "Impossible de trouver l'inscription associée à ce paiement.";
                        $_SESSION['message_type'] = 'error';
                        header('Location: /home');
                        exit;
                    }

                    // Empêcher le traitement si déjà complété ou échoué définitivement
                    if (in_array($registration['payment_status'], ['completed', 'failed'])) {
                         error_log("Info Callback SumUp : Inscription déjà traitée pour checkout_reference : " . $checkoutReference . " avec statut : " . $registration['payment_status']);
                         // Rediriger vers la page appropriée en fonction du statut existant
                         if ($registration['payment_status'] === 'completed') {
                             header('Location: /payment/success');
                         } else {
                             header('Location: /payment/cancel');
                         }
                         exit;
                    }

                    // 2. Instancier le SDK SumUp
                    if (!defined('SUMUP_CLIENT_ID') || !defined('SUMUP_CLIENT_SECRET')) {
                         throw new Exception("Les constantes de configuration SumUp ne sont pas définies pour le callback.");
                    }
                    $sumup = new SumUp([
                        'app_id'     => SUMUP_CLIENT_ID,
                        'app_secret' => SUMUP_CLIENT_SECRET,
                        'grant_type' => 'client_credentials'
                    ]);
                    $checkoutService = $sumup->getCheckoutService();

                    // 3. Récupérer le Statut du Checkout depuis SumUp
                    // Utiliser l'ID de checkout SumUp stocké lors de l'initiation si disponible
                    $sumupCheckoutIdToLookup = $registration['sumup_checkout_id'] ?? $sumupCheckoutId; // Préférer l'ID stocké en DB

                    if (empty($sumupCheckoutIdToLookup)) {
                         throw new Exception("Impossible de récupérer le statut SumUp : ID de Checkout SumUp manquant pour la référence " . $checkoutReference);
                    }

                    $checkoutDetails = $checkoutService->getById($sumupCheckoutIdToLookup);

                    if (empty($checkoutDetails->status) || empty($checkoutDetails->transaction_id)) {
                         // Vérifier si transaction_code existe, car transaction_id peut être null pour les checkouts échoués initialement
                         $transactionId = $checkoutDetails->transaction_id ?? ($checkoutDetails->transaction_code ?? 'N/A'); // Utiliser transaction_code comme ID de secours si transaction_id est null
                         if (empty($checkoutDetails->status)) {
                            throw new SumUpResponseException("Détails de checkout invalides reçus de SumUp (statut manquant) pour l'ID : " . $sumupCheckoutIdToLookup);
                         }
                         // Autoriser la poursuite si le statut est présent mais transaction_id peut être null (ex: pour FAILED)
                         error_log("Avertissement Callback SumUp : transaction_id est null pour l'ID de checkout {$sumupCheckoutIdToLookup}, statut : {$checkoutDetails->status}. Utilisation de transaction_code : {$transactionId}");

                    } else {
                         $transactionId = $checkoutDetails->transaction_id; // Obtenir l'ID de transaction réel
                    }


                    // 4. Déterminer le nouveau statut et mettre à jour la DB
                    $newStatus = 'failed'; // Par défaut à échoué

                    if ($checkoutDetails->status === 'PAID') {
                        $newStatus = 'completed';
                    } elseif ($checkoutDetails->status === 'FAILED') {
                        $newStatus = 'failed';
                    } else {
                        // Gérer d'autres statuts comme PENDING, EXPIRED etc. si nécessaire
                        // Pour l'instant, traiter les non-PAID comme échoué/annulé pour simplifier
                        $newStatus = 'failed'; // Ou peut-être 'cancelled' selon les codes de statut SumUp
                        error_log("Callback SumUp : Statut non géré '{$checkoutDetails->status}' pour l'ID de checkout {$sumupCheckoutIdToLookup}. Traité comme échoué.");
                    }

                    // 5. Mettre à Jour le Statut de l'Inscription dans la DB
                    // Nécessite une nouvelle méthode DB : updateRegistrationPaymentStatus(checkoutRef, status, transactionId)
                    $updateSuccess = $db->updateRegistrationPaymentStatus($checkoutReference, $newStatus, $transactionId);

                    if (!$updateSuccess) {
                        // Enregistrer l'erreur, mais continuer à rediriger en fonction du statut récupéré
                        error_log("Erreur Callback SumUp : Échec de la mise à jour du statut de l'inscription en DB pour checkout_reference : " . $checkoutReference . " au statut : " . $newStatus);
                        // Peut-être définir un message d'erreur générique ?
                        $_SESSION['message'] = "Votre paiement a été traité, mais une erreur est survenue lors de la mise à jour de votre inscription. Veuillez contacter le support.";
                        $_SESSION['message_type'] = 'warning'; // Utiliser warning car le paiement pourrait être ok
                    }

                    // 6. Rediriger l'Utilisateur
                    if ($newStatus === 'completed') {
                        $_SESSION['payment_event_id'] = $registration['event_id']; // Stocker l'ID de l'événement pour la page de succès
                        header('Location: /payment/success');
                        exit;
                    } else {
                        $_SESSION['payment_event_id'] = $registration['event_id']; // Stocker l'ID de l'événement pour la page d'annulation
                        header('Location: /payment/cancel');
                        exit;
                    }

                } catch (SumUpAuthenticationException $e) {
                    error_log("Erreur d'Authentification Callback SumUp : " . $e->getMessage());
                    $_SESSION['message'] = "Erreur d'authentification avec le service de paiement lors de la vérification.";
                    $_SESSION['message_type'] = 'error';
                    header('Location: /home'); exit;
                } catch (SumUpResponseException $e) {
                    error_log("Erreur de Réponse API Callback SumUp : " . $e->getMessage() . " | Corps : " . $e->getBody());
                    $_SESSION['message'] = "Erreur de communication avec le service de paiement lors de la vérification (" . $e->getCode() . ").";
                    $_SESSION['message_type'] = 'error';
                    header('Location: /home'); exit;
                } catch (SumUpSDKException $e) {
                    error_log("Erreur SDK Callback SumUp : " . $e->getMessage());
                    $_SESSION['message'] = "Erreur technique avec le service de paiement lors de la vérification.";
                    $_SESSION['message_type'] = 'error';
                    header('Location: /home'); exit;
                } catch (Exception $e) {
                    error_log("Erreur Générale lors du traitement du callback SumUp pour réf {$checkoutReference}: " . $e->getMessage());
                    $_SESSION['message'] = "Une erreur technique est survenue lors de la confirmation du paiement (" . $e->getCode() . ").";
                    $_SESSION['message_type'] = 'error';
                    header('Location: /home'); exit;
                }
            } else {
                 http_response_code(405); // Méthode Non Autorisée pour non-GET
                 echo "Method Not Allowed";
                 exit;
            }
            break;

        // Page de Succès de Paiement - Gère la Vérification du Callback SumUp
        case '/payment/success':
            $matched = true;
            $layout_vars['page_title'] = "Vérification du Paiement"; // Titre pendant la vérification
            $layout_vars['error_message'] = null;
            $layout_vars['success_message'] = null;
            $layout_vars['event_details'] = null; // Pour passer au template

            // --- Logique de Vérification SumUp ---
            try {
                // 1. Obtenir l'Instance de la Base de Données (config/sumup.php devrait déjà être inclus en haut)
                $db = Database::getInstance();
                $pdo = $db->getConnection();

                // 2. Extraire l'ID de Checkout SumUp
                $sumupCheckoutId = $_GET['id'] ?? null;
                if (empty($sumupCheckoutId)) {
                    throw new Exception("ID de transaction SumUp manquant dans l'URL de retour.");
                }

                // 3. Interroger l'API SumUp pour le Statut
                if (!defined('SUMUP_API_BASE_URL') || !defined('SUMUP_TRANSACTION_ENDPOINT_PATTERN') || !defined('SUMUP_API_KEY')) {
                    throw new Exception("Configuration SumUp API manquante (URL ou Clé).");
                }
                $apiUrl = SUMUP_API_BASE_URL . str_replace('{id}', urlencode($sumupCheckoutId), SUMUP_TRANSACTION_ENDPOINT_PATTERN);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . SUMUP_API_KEY,
                    'Accept: application/json'
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    throw new Exception("Erreur cURL lors de la requête API SumUp: " . $curlError);
                }
                if ($httpCode >= 400) {
                     error_log("Erreur API SumUp (HTTP {$httpCode}) pour l'ID de checkout {$sumupCheckoutId}: " . $response);
                     // Essayer de décoder la réponse pour plus de détails si possible
                     $errorData = json_decode($response, true);
                     $errorMessage = $errorData['error_message'] ?? ('Erreur ' . $httpCode);
                     throw new Exception("Erreur de l'API SumUp lors de la récupération du statut: " . $errorMessage);
                }

                $sumupData = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($sumupData)) {
                    error_log("Erreur de Décodage JSON de la Réponse API SumUp pour l'ID de checkout {$sumupCheckoutId}: " . json_last_error_msg() . " | Réponse : " . $response);
                    throw new Exception("Réponse invalide reçue de l'API SumUp.");
                }

                // 4. Trouver l'Inscription par sumup_checkout_id
                $stmt = $pdo->prepare("SELECT er.*, e.title as event_title, e.event_date, e.location FROM event_registrations er JOIN events e ON er.event_id = e.id WHERE er.sumup_checkout_id = :sumup_id");
                $stmt->bindValue(':sumup_id', $sumupCheckoutId, PDO::PARAM_STR);
                $stmt->execute();
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$registration) {
                    error_log("Avertissement Callback SumUp : Aucune inscription trouvée pour sumup_checkout_id : " . $sumupCheckoutId);
                    throw new Exception("Impossible de trouver l'inscription associée à ce paiement.");
                }

                // Empêcher le re-traitement si déjà complété/échoué définitivement
                 if (in_array($registration['payment_status'], ['completed', 'failed', 'cancelled'])) {
                     error_log("Info Callback SumUp : Inscription ID {$registration['id']} déjà traitée avec statut : " . $registration['payment_status']);
                     // Charger le template approprié en fonction du statut existant
                     if ($registration['payment_status'] === 'completed') {
                         $layout_vars['page_title'] = "Paiement Réussi";
                         $layout_vars['success_message'] = "Votre inscription pour '" . htmlspecialchars($registration['event_title']) . "' est déjà confirmée.";
                         $layout_vars['event_details'] = $registration; // Passer les détails au template
                         $page_content = TEMPLATE_PATH . 'payment_success.php';
                     } else {
                         $layout_vars['page_title'] = "Paiement Annulé ou Échoué";
                         $layout_vars['error_message'] = "Le statut de votre paiement pour '" . htmlspecialchars($registration['event_title']) . "' est déjà marqué comme '" . $registration['payment_status'] . "'.";
                         $layout_vars['event_details'] = $registration; // Passer les détails au template
                         $page_content = TEMPLATE_PATH . 'payment_cancel.php';
                     }
                     break; // Sortir du bloc try principal et du cas switch
                 }


                // 5. Mettre à Jour la Base de Données Basé sur le Statut API
                $sumupStatus = $sumupData['status'] ?? 'UNKNOWN';
                $sumupTransactionId = $sumupData['transaction_id'] ?? ($sumupData['transaction_code'] ?? null); // Utiliser transaction_code comme secours

                $newDbStatus = null;
                if ($sumupStatus === 'PAID') {
                    $newDbStatus = 'completed';
                } elseif ($sumupStatus === 'FAILED') {
                    $newDbStatus = 'failed';
                } elseif ($sumupStatus === 'CANCELLED') { // En supposant que SumUp utilise CANCELLED
                    $newDbStatus = 'cancelled';
                } else {
                    // Traiter les autres statuts (PENDING, etc.) ou UNKNOWN comme potentiellement échoué/annulé dans ce contexte
                    $newDbStatus = 'failed'; // Par défaut à échoué si le statut est inattendu
                    error_log("Callback SumUp : Statut non géré '{$sumupStatus}' pour l'ID de checkout {$sumupCheckoutId}. Traité comme 'failed'.");
                }

                $updateStmt = $pdo->prepare("UPDATE event_registrations SET payment_status = :status, transaction_id = :tx_id, updated_at = CURRENT_TIMESTAMP WHERE sumup_checkout_id = :sumup_id");
                $updateStmt->bindValue(':status', $newDbStatus, PDO::PARAM_STR);
                $updateStmt->bindValue(':tx_id', $sumupTransactionId, PDO::PARAM_STR); // Stocker l'ID de transaction SumUp
                $updateStmt->bindValue(':sumup_id', $sumupCheckoutId, PDO::PARAM_STR);
                $updateSuccess = $updateStmt->execute();

                if (!$updateSuccess) {
                    error_log("Erreur DB Callback SumUp : Échec de la mise à jour du statut de l'inscription pour sumup_checkout_id : " . $sumupCheckoutId . " au statut : " . $newDbStatus);
                    // Même si la mise à jour DB échoue, afficher la page basée sur le statut SumUp, mais enregistrer l'erreur
                    $_SESSION['message'] = "Le paiement a été vérifié (" . $sumupStatus . "), mais une erreur est survenue lors de la mise à jour interne. Veuillez contacter le support.";
                    $_SESSION['message_type'] = 'warning';
                } else {
                     error_log("Succès Callback SumUp : Inscription ID {$registration['id']} mise à jour à '{$newDbStatus}' basé sur le statut SumUp '{$sumupStatus}' pour l'ID de checkout {$sumupCheckoutId}. ID Tx SumUp : {$sumupTransactionId}");
                     // Optionnellement déclencher l'email de confirmation ici si le statut est 'completed'
                }

                // 6. Charger le Template Final basé sur le résultat
                if ($newDbStatus === 'completed') {
                    $layout_vars['page_title'] = "Paiement Réussi";
                    $layout_vars['success_message'] = "Paiement confirmé ! Votre inscription pour '" . htmlspecialchars($registration['event_title']) . "' est validée.";
                    $layout_vars['event_details'] = $registration; // Passer les détails au template
                    $page_content = TEMPLATE_PATH . 'payment_success.php';
                } else {
                    // Aller à la page d'annulation/échec si le statut n'était pas PAID
                    $layout_vars['page_title'] = "Paiement Annulé ou Échoué";
                    $layout_vars['error_message'] = "Le statut de votre paiement SumUp est '" . $sumupStatus . "'. Votre inscription n'a pas pu être validée.";
                     $layout_vars['event_details'] = $registration; // Passer les détails au template
                    $page_content = TEMPLATE_PATH . 'payment_cancel.php';
                }

            } catch (Exception $e) {
                error_log("Erreur lors du traitement du Callback de Succès SumUp : " . $e->getMessage());
                $layout_vars['error_message'] = "Une erreur est survenue lors de la vérification de votre paiement : " . $e->getMessage();
                // Afficher une page d'erreur/annulation générique en cas d'échec
                $layout_vars['page_title'] = "Erreur de Paiement";
                $page_content = TEMPLATE_PATH . 'payment_cancel.php'; // Par défaut à la page d'annulation en cas d'erreur
            }
            // --- Fin de la Logique de Vérification SumUp ---
            break;

        // Page d'Annulation/Échec de Paiement - Gère la Vérification du Callback SumUp
        case '/payment/cancel':
             $matched = true;
            $layout_vars['page_title'] = "Vérification du Paiement"; // Titre pendant la vérification
            $layout_vars['error_message'] = null;
            $layout_vars['success_message'] = null;
            $layout_vars['event_details'] = null; // Pour passer au template

            // --- Logique de Vérification SumUp (Similaire au Succès) ---
            try {
                // 1. Obtenir l'Instance de la Base de Données
                $db = Database::getInstance();
                $pdo = $db->getConnection();

                // 2. Extraire l'ID de Checkout SumUp
                $sumupCheckoutId = $_GET['id'] ?? null;
                if (empty($sumupCheckoutId)) {
                    // Si l'ID est manquant lors de l'annulation, peut-être juste afficher la page d'annulation générique ?
                    // Ou tenter une recherche via checkout_reference si disponible ?
                    // Pour l'instant, traiter l'ID manquant comme une erreur nécessitant la page d'annulation.
                    error_log("Callback d'Annulation SumUp : Paramètre 'id' manquant.");
                    $layout_vars['page_title'] = "Paiement Annulé ou Échoué";
                    $layout_vars['error_message'] = "Le paiement a été annulé ou l'identifiant de transaction est manquant.";
                    $page_content = TEMPLATE_PATH . 'payment_cancel.php';
                    break; // Sortir du cas
                }

                // 3. Interroger l'API SumUp pour le Statut (Optionnel mais bonne pratique)
                // Il est possible que l'utilisateur ait annulé *avant* que SumUp ne traite quoi que ce soit,
                // ou le statut pourrait déjà être FAILED/CANCELLED. L'interrogation confirme.
                 if (!defined('SUMUP_API_BASE_URL') || !defined('SUMUP_TRANSACTION_ENDPOINT_PATTERN') || !defined('SUMUP_API_KEY')) {
                    throw new Exception("Configuration SumUp API manquante (URL ou Clé).");
                }
                $apiUrl = SUMUP_API_BASE_URL . str_replace('{id}', urlencode($sumupCheckoutId), SUMUP_TRANSACTION_ENDPOINT_PATTERN);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . SUMUP_API_KEY,
                    'Accept: application/json'
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                $sumupData = null;
                $sumupStatus = 'UNKNOWN'; // Par défaut si l'API échoue ou l'inscription n'est pas trouvée
                $sumupTransactionId = null;

                if ($curlError) {
                    error_log("Erreur cURL Callback d'Annulation SumUp pour l'ID de checkout {$sumupCheckoutId}: " . $curlError);
                    // Continuer sans statut API, supposer annulé/échoué
                } elseif ($httpCode >= 400) {
                     error_log("Erreur API Callback d'Annulation SumUp (HTTP {$httpCode}) pour l'ID de checkout {$sumupCheckoutId}: " . $response);
                     // Continuer sans statut API, supposer annulé/échoué
                } else {
                    $sumupData = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($sumupData)) {
                        $sumupStatus = $sumupData['status'] ?? 'UNKNOWN';
                        $sumupTransactionId = $sumupData['transaction_id'] ?? ($sumupData['transaction_code'] ?? null);
                    } else {
                         error_log("Erreur de Décodage JSON Réponse API Callback d'Annulation SumUp pour l'ID de checkout {$sumupCheckoutId}: " . json_last_error_msg());
                         // Continuer sans statut API
                    }
                }

                // 4. Trouver l'Inscription par sumup_checkout_id
                $stmt = $pdo->prepare("SELECT er.*, e.title as event_title FROM event_registrations er JOIN events e ON er.event_id = e.id WHERE er.sumup_checkout_id = :sumup_id");
                $stmt->bindValue(':sumup_id', $sumupCheckoutId, PDO::PARAM_STR);
                $stmt->execute();
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($registration) {
                     $layout_vars['event_details'] = $registration; // Passer les détails au template

                     // 5. Mettre à Jour la Base de Données si le statut est toujours en attente
                     if ($registration['payment_status'] === 'pending') {
                         $newDbStatus = null;
                         // Déterminer le statut basé sur la réponse API ou supposer 'cancelled' si l'API a échoué
                         if ($sumupStatus === 'FAILED') {
                             $newDbStatus = 'failed';
                         } elseif ($sumupStatus === 'CANCELLED') {
                             $newDbStatus = 'cancelled';
                         } else {
                             // Si l'utilisateur a atteint l'URL d'annulation mais que le statut SumUp est PAID (improbable) ou PENDING/UNKNOWN,
                             // marquer comme 'cancelled' dans notre système car l'utilisateur a explicitement annulé le flux.
                             $newDbStatus = 'cancelled';
                             error_log("Callback d'Annulation SumUp : Marquage de l'inscription ID {$registration['id']} comme 'cancelled'. Le statut API SumUp était '{$sumupStatus}'. ID Checkout : {$sumupCheckoutId}");
                         }

                         $updateStmt = $pdo->prepare("UPDATE event_registrations SET payment_status = :status, transaction_id = :tx_id, updated_at = CURRENT_TIMESTAMP WHERE sumup_checkout_id = :sumup_id");
                         $updateStmt->bindValue(':status', $newDbStatus, PDO::PARAM_STR);
                         $updateStmt->bindValue(':tx_id', $sumupTransactionId, PDO::PARAM_STR);
                         $updateStmt->bindValue(':sumup_id', $sumupCheckoutId, PDO::PARAM_STR);
                         $updateSuccess = $updateStmt->execute();

                         if (!$updateSuccess) {
                             error_log("Erreur DB Callback d'Annulation SumUp : Échec de la mise à jour du statut de l'inscription pour sumup_checkout_id : " . $sumupCheckoutId . " au statut : " . $newDbStatus);
                             $_SESSION['message'] = "Une erreur est survenue lors de la mise à jour interne suite à l'annulation. Veuillez contacter le support si nécessaire.";
                             $_SESSION['message_type'] = 'warning';
                         } else {
                              error_log("Succès Callback d'Annulation SumUp : Inscription ID {$registration['id']} mise à jour à '{$newDbStatus}' pour l'ID de checkout {$sumupCheckoutId}. Statut API SumUp : '{$sumupStatus}'. ID Tx SumUp : {$sumupTransactionId}");
                         }
                     } else {
                          error_log("Info Callback d'Annulation SumUp : Inscription ID {$registration['id']} déjà traitée avec statut : " . $registration['payment_status'] . ". Aucune mise à jour nécessaire.");
                     }
                     // Définir le message basé sur le statut final déterminé (ou le statut existant)
                     $finalStatus = $registration['payment_status'] === 'pending' ? $newDbStatus : $registration['payment_status'];
                     $layout_vars['error_message'] = "Le paiement pour '" . htmlspecialchars($registration['event_title']) . "' a été annulé ou a échoué (Statut final: " . $finalStatus . ").";

                } else {
                    // Inscription non trouvée pour cet ID de checkout
                    error_log("Avertissement Callback d'Annulation SumUp : Aucune inscription trouvée pour sumup_checkout_id : " . $sumupCheckoutId);
                     $layout_vars['error_message'] = "Paiement annulé. Impossible de trouver l'inscription associée.";
                }

                // 6. Charger le Template Final
                $layout_vars['page_title'] = "Paiement Annulé ou Échoué";
                $page_content = TEMPLATE_PATH . 'payment_cancel.php';


            } catch (Exception $e) {
                error_log("Erreur lors du traitement du Callback d'Annulation SumUp : " . $e->getMessage());
                $layout_vars['error_message'] = "Une erreur est survenue lors du traitement de l'annulation : " . $e->getMessage();
                // Afficher la page d'annulation générique en cas d'erreur
                $layout_vars['page_title'] = "Erreur de Paiement";
                $page_content = TEMPLATE_PATH . 'payment_cancel.php';
            }
            // --- Fin de la Logique de Vérification SumUp ---
            break;


        case '/events': // Route pour la liste publique des événements
            $matched = true;
            if ($method === 'GET') {
                try {
                    $db = Database::getInstance();

                    // Récupérer les filtres
                    $filters = [
                        'search' => $_GET['search'] ?? '',
                        'price' => $_GET['price'] ?? '',
                        'date' => $_GET['date'] ?? ''
                    ];

                    // Utiliser la méthode filtrée si des filtres sont présents
                    $hasFilters = !empty($filters['search']) || !empty($filters['price']) || !empty($filters['date']);
                    $events = $hasFilters
                        ? $db->getFilteredUpcomingEvents($filters)
                        : $db->getAllUpcomingEvents();

                    $layout_vars['events'] = $events;
                    $layout_vars['page_title'] = "Agenda des Événements";
                    $page_content = TEMPLATE_PATH . 'events.php';
                } catch (Exception $e) {
                    error_log("Erreur lors de la récupération des événements : " . $e->getMessage());
                    http_response_code(500);
                    $page_content = TEMPLATE_PATH . '500.php';
                    $layout_vars['error_message'] = "Une erreur est survenue lors du chargement des événements.";
                }
            } else {
                http_response_code(405); // Méthode Non Autorisée
                $page_content = TEMPLATE_PATH . '404.php';
                $layout_vars['error_message'] = "Méthode non autorisée";
            }
            break;
            
        case '/pay': // Route pour gérer les redirections de paiement
            $matched = true;
            if ($method === 'GET') {
                // Récupérer l'ID de l'événement depuis l'URL
                $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
                
                if ($eventId <= 0) {
                    $_SESSION['message'] = ['type' => 'danger', 'text' => "Événement non spécifié."];
                    header('Location: /events');
                    exit;
                }
                
                // Vérifier si l'utilisateur est connecté
                if (!isset($_SESSION['user_id'])) {
                    $_SESSION['message'] = ['type' => 'warning', 'text' => "Veuillez vous connecter pour effectuer un paiement."];
                    header('Location: /login?redirect=/events/' . $eventId);
                    exit;
                }
                
                try {
                    $db = Database::getInstance();
                    $event = $db->getEventById($eventId);
                    
                    if (!$event) {
                        $_SESSION['message'] = ['type' => 'danger', 'text' => "L'événement demandé n'existe pas."];
                        header('Location: /events');
                        exit;
                    }
                    
                    // Rediriger vers la page de paiement SumUp
                    $userId = $_SESSION['user_id'];
                    $user = $db->getUserById($userId);
                    
                    // Calculer le prix avec réduction si applicable
                    $price = (float)$event['price'];
                    $discountPercentage = 0;
                    
                    // Appliquer les réductions selon le statut de l'utilisateur
                    if ($user['membership_status'] === 'premium') {
                        $discountPercentage = 10;
                    } else {
                        $activeMemberships = $db->getUserActiveMemberships($userId);
                        if (!empty($activeMemberships)) {
                            $activeMembership = $activeMemberships[0];
                            if (isset($activeMembership['discount_percentage'])) {
                                $discountPercentage = (float)$activeMembership['discount_percentage'];
                            }
                        }
                    }
                    
                    $finalPrice = $price;
                    if ($discountPercentage > 0) {
                        $finalPrice = $price * (1 - ($discountPercentage / 100));
                    }
                    $finalPrice = round($finalPrice, 2);
                    
                    // Simuler un paiement réussi pour le développement
                    // Dans un environnement de production, nous utiliserions SumUp ici
                    
                    $checkoutReference = 'EVENT_' . $eventId . '_USER_' . $userId . '_' . time();
                    
                    // Enregistrer la tentative de paiement dans la base de données
                    $db->recordPaymentAttempt($userId, $eventId, $checkoutReference, $finalPrice, 'pending');
                    
                    // Simuler un paiement réussi
                    try {
                        // Enregistrer l'utilisateur à l'événement
                        $db->registerUserToEvent($userId, $eventId);
                        
                        // Mettre à jour le statut du paiement
                        $db->updatePaymentStatus($checkoutReference, 'completed');
                        
                        $_SESSION['message'] = ['type' => 'success', 'text' => "Paiement simulé réussi ! Vous êtes inscrit à l'événement."];
                        header('Location: /events/' . $eventId);
                    } catch (Exception $e) {
                        error_log("Erreur lors de l'inscription à l'événement: " . $e->getMessage());
                        $_SESSION['message'] = ['type' => 'danger', 'text' => "Une erreur est survenue lors de l'inscription. Veuillez réessayer."];
                        header('Location: /events/' . $eventId);
                    }
                    exit;
                    
                } catch (Exception $e) {
                    error_log("Erreur lors de la création du paiement SumUp: " . $e->getMessage());
                    $_SESSION['message'] = ['type' => 'danger', 'text' => "Une erreur est survenue lors de la préparation du paiement. Veuillez réessayer."];
                    header('Location: /events/' . $eventId);
                    exit;
                }
            } else {
                http_response_code(405);
                $_SESSION['message'] = ['type' => 'danger', 'text' => "Méthode non autorisée."];
                header('Location: /events');
                exit;
            }
            break;

        case '/register':
            $matched = true;
        if ($method === 'GET') {
            $page_content = TEMPLATE_PATH . 'register.php';
        } elseif ($method === 'POST') {

            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? ''); // Supprimer les espaces du mot de passe

            $errors = [];
            if (empty($first_name)) $errors[] = "Le prénom est requis.";
            if (empty($last_name)) $errors[] = "Le nom est requis.";
            if (empty($email)) {
                $errors[] = "L'email est requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Le format de l'email est invalide.";
            }
            if (empty($password)) {
                $errors[] = "Le mot de passe est requis.";
            } elseif (strlen($password) < 8) {
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule.";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
            } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*()_+-=[]{};':\"\\|,.<>/?~).";
            }

            if (empty($errors)) {
                try {
                    $db = Database::getInstance()->getConnection();


                    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();

                    if ($stmt->fetch()) {
                        $errors[] = "Cette adresse email est déjà utilisée.";
                    } else {

                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);


                        $insertStmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)");
                        $insertStmt->bindParam(':first_name', $first_name);
                        $insertStmt->bindParam(':last_name', $last_name);
                        $insertStmt->bindParam(':email', $email);
                        $insertStmt->bindParam(':password', $hashed_password);

                        if ($insertStmt->execute()) {

                            header('Location: /login?registered=1');
                            exit;
                        } else {
                            $errors[] = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Erreur d'Inscription : " . $e->getMessage());
                    $errors[] = "Erreur de base de données lors de l'inscription.";
                }
            }


            if (!empty($errors)) {
                $layout_vars['error_message'] = implode('<br>', $errors);
            }

            $page_content = TEMPLATE_PATH . 'register.php';

        }
        break;

case '/login':
        $matched = true;
        if ($method === 'GET') {
            // Afficher le formulaire de connexion
            $page_content = TEMPLATE_PATH . 'login.php';
            if (isset($_GET['registered']) && $_GET['registered'] == 1) {
                $layout_vars['success_message'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            }
            if (isset($_GET['redirect'])) {
                // Stocker l'URL de redirection dans un champ caché du formulaire ou la passer via layout_vars
                 $layout_vars['redirect_url'] = htmlspecialchars($_GET['redirect']);
            }
             if (isset($_GET['error']) && $_GET['error'] === 'not_logged_in') {
                $layout_vars['error_message'] = "Vous devez être connecté pour accéder à cette page.";
            }

        } elseif ($method === 'POST') {
            // Traiter le formulaire de connexion
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? ''); // Supprimer les espaces du mot de passe
            // Récupérer l'URL de redirection depuis un champ caché du formulaire, par défaut /home
            $redirect_url = $_POST['redirect_url'] ?? '/home';

            $errors = [];
            if (empty($email)) {
                $errors[] = "L'email est requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Le format de l'email est invalide.";
            }
            if (empty($password)) {
                $errors[] = "Le mot de passe est requis.";
            }

            if (empty($errors)) {
                try {
                    $db = Database::getInstance();
                    // Assurez-vous que getUserByEmail récupère id, password, failed_login_attempts, is_locked, first_name, is_admin
                    $user = $db->getUserByEmail($email);

                    if ($user) {
                        // 1. Vérifier si le compte est verrouillé
                        if ($user['is_locked'] == 1) {
                            $errors[] = "Compte verrouillé en raison de trop nombreuses tentatives de connexion échouées.";
                        } else {
                            // 2. Vérifier le mot de passe
                            if (password_verify($password, $user['password'])) {
                                // Connexion réussie
                                // 3. Réinitialiser les tentatives échouées si nécessaire
                                if ($user['failed_login_attempts'] > 0) {
                                    $db->resetLoginAttempts($user['id']); // Nouvelle méthode DB nécessaire
                                }

                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['user_name'] = $user['first_name'];
                                $_SESSION['is_admin'] = ($user['is_admin'] == 1);

                                session_regenerate_id(true);

                                header('Location: ' . $redirect_url);
                                exit;
                            } else {
                                // Échec de la connexion - mot de passe incorrect
                                // 4. Incrémenter les tentatives échouées
                                $newAttemptCount = $db->incrementLoginAttempts($user['id']); // Nouvelle méthode DB nécessaire (retourne le nouveau compte)

                                // 5. Verrouiller le compte si le seuil est atteint
                                if ($newAttemptCount >= 5) {
                                    $db->lockAccount($user['id']); // Nouvelle méthode DB nécessaire
                                    $errors[] = "Compte verrouillé en raison de trop nombreuses tentatives de connexion échouées."; // Message spécifique si verrouillé maintenant
                                } else {
                                    $errors[] = "Email ou mot de passe incorrect."; // Message générique sinon
                                }
                            }
                        }
                    } else {
                        // Échec de la connexion - utilisateur non trouvé
                        $errors[] = "Email ou mot de passe incorrect."; // Message générique pour ne pas révéler si l'email existe
                    }
                } catch (Exception $e) {
                    error_log("Erreur de Connexion : " . $e->getMessage());
                    // Utiliser un message générique pour les erreurs internes aussi
                    $errors[] = "Email ou mot de passe incorrect.";
                }
            }

            // Si des erreurs se sont produites ou si la connexion a échoué, réafficher le formulaire de connexion avec les erreurs
            $layout_vars['error_message'] = implode('<br>', $errors);
            $layout_vars['email'] = $email; // Remplir à nouveau le champ email
            $layout_vars['redirect_url'] = $redirect_url; // Conserver l'URL de redirection pour le formulaire
            $page_content = TEMPLATE_PATH . 'login.php';
        }
        break;
case '/logout':
            $matched = true;
            // S'assurer que la session est active (déjà démarrée en haut)
            session_unset();   // Désactiver toutes les variables de session
            session_destroy(); // Détruire la session
            header('Location: /'); // Rediriger vers la page d'accueil
            exit; // Arrêter l'exécution du script


    case '/leaderboard':
        $matched = true;
        if ($method === 'GET') {
            $leaderboard_users = []; // Initialiser un tableau vide
            try {
                $db = Database::getInstance();
                // Récupérer les données du classement : Sommer les points des inscriptions aux événements complétées
                $sql = "
                    SELECT
                        u.first_name, -- Sélectionner le prénom
                        u.last_name,  -- Sélectionner le nom de famille
                        SUM(e.points_awarded) AS total_points -- Sommer les points de la table events
                    FROM
                        users u
                    JOIN
                        event_registrations er ON u.id = er.user_id
                    JOIN
                        events e ON er.event_id = e.id
                    WHERE
                        er.payment_status = 'completed' -- Compter uniquement les inscriptions complétées
                        AND e.points_awarded > 0 -- Compter uniquement les événements qui attribuent des points
                    GROUP BY
                        u.id, u.first_name, u.last_name -- Grouper par utilisateur y compris le nom de famille
                    ORDER BY
                        total_points DESC -- Trier par le total de points calculé
                    LIMIT 20; -- Limiter aux 20 meilleurs utilisateurs
                ";
                $stmt = $db->getConnection()->query($sql); // Obtenir d'abord la connexion PDO
                $leaderboard_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (Exception $e) {
                // Gérer les erreurs potentielles, ex: les enregistrer ou afficher un message générique
                error_log("Échec de la requête du classement : " . $e->getMessage());
                $layout_vars['error_message'] = "Impossible de charger le classement.";
            }
            // Passer les données récupérées (ou le tableau vide en cas d'erreur) au layout
            $layout_vars['leaderboard_users'] = $leaderboard_users;
            $layout_vars['page_title'] = "Classement BDE"; // Définir le titre de la page
            $page_content = TEMPLATE_PATH . 'leaderboard.php';
        } else {
            http_response_code(405);
            echo "Méthode non autorisée pour cette URL.";
            exit;
        }
        break;

case '/mentions-legales':
        $matched = true;
        if ($method === 'GET') {
            $page_content = TEMPLATE_PATH . 'legal_notice.php';
        } else {
            http_response_code(405);
            echo "Méthode non autorisée pour cette URL.";
            exit;
        }
        break;

case '/memberships':
            $matched = true;
            if ($method === 'GET') {
                $userId = $_SESSION['user_id'] ?? null;
                $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
                $allMemberships = [];
                $userActiveSoutien = null;
                $userActivePremium = null;
                $dbError = null; // Initialiser dbError

                try {
                    $db = Database::getInstance();
                    $allMemberships = $db->getAllMemberships();

                    if ($userId) {
                        if ($isAdmin) {
                            // Utilisateur admin : Accorder le statut Premium virtuel
                            $userActivePremium = [
                                'membership_name' => 'Premium (Admin)',
                                'end_date' => 'N/A', // Les admins n'expirent pas
                                'membership_description' => 'Accès Premium accordé aux administrateurs.'
                            ];
                            // Les admins n'ont pas de statut 'Soutien' sauf s'il est explicitement attribué,
                            // mais pour la cohérence de l'affichage, nous vérifions aussi leurs adhésions réelles.
                            $actualMemberships = $db->getUserActiveMemberships($userId);
                             foreach ($actualMemberships as $membership) {
                                if (stripos($membership['membership_name'], 'Soutien') !== false) {
                                    $userActiveSoutien = $membership;
                                    break; // Soutien trouvé, pas besoin de vérifier plus loin
                                }
                            }

                        } else {
                            // Utilisateur régulier : Vérifier les adhésions réelles
                            $activeMemberships = $db->getUserActiveMemberships($userId);
                            foreach ($activeMemberships as $membership) {
                                if (stripos($membership['membership_name'], 'Premium') !== false) {
                                    $userActivePremium = $membership;
                                } elseif (stripos($membership['membership_name'], 'Soutien') !== false) {
                                    $userActiveSoutien = $membership;
                                }
                                // Si les deux sont trouvés, arrêter la vérification (ou ajuster si plusieurs actives sont possibles)
                                if ($userActivePremium && $userActiveSoutien) break;
                            }
                        }
                    }

                    // Préparer les variables pour le template
                    $layout_vars['allMemberships'] = $allMemberships;
                    $layout_vars['userActiveSoutien'] = $userActiveSoutien;
                    $layout_vars['userActivePremium'] = $userActivePremium;
                    $layout_vars['isUserLoggedIn'] = ($userId !== null);
                    // Note : La logique du prix de mise à niveau pourrait nécessiter un ajustement si elle n'est pas déjà gérée

                } catch (Exception $e) {
                    error_log("Erreur lors de la récupération des données des adhésions : " . $e->getMessage());
                    $dbError = "Impossible de charger les informations sur les adhésions.";
                    $layout_vars['dbError'] = $dbError; // Passer l'erreur au template
                    $layout_vars['allMemberships'] = [];
                    $layout_vars['userActiveSoutien'] = null;
                    $layout_vars['userActivePremium'] = null;
                    $layout_vars['isUserLoggedIn'] = ($userId !== null);
                }

                $layout_vars['page_title'] = "Adhésions";
                $page_content = TEMPLATE_PATH . 'memberships.php';
            } else {
                http_response_code(405);
                echo "Méthode non autorisée pour cette URL.";
                exit;
            }
            break;






case '/account':
        $matched = true;
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?redirect=/account');
            exit;
        }
        if ($method === 'GET') {
            try {
                $db = Database::getInstance();
                $userId = $_SESSION['user_id'];
                $user = $db->getUserById($userId);
                $activeMemberships = $db->getUserActiveMemberships($userId);
                // Utiliser la première adhésion active si plusieurs existent (la logique pourrait nécessiter un affinement basé sur les règles métier)
                $activeMembership = !empty($activeMemberships) ? $activeMemberships[0] : null;

                if (!$user) {
                    // ID utilisateur en session mais non trouvé en DB ? Déconnecter.
                    error_log("ID utilisateur {$_SESSION['user_id']} en session mais non trouvé dans la base de données.");
                    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['is_admin']);
                    session_destroy();
                    header('Location: /login?error=user_not_found');
                    exit;
                }

                // Passer les données au layout/template
                $layout_vars['user'] = $user;
                $layout_vars['activeMembership'] = $activeMembership;
                $layout_vars['title'] = "Mon Compte"; // Définir le titre de la page

                $page_content = TEMPLATE_PATH . 'account.php';

            } catch (Exception $e) {
                error_log("Erreur lors du chargement de la page de compte pour l'ID utilisateur {$userId}: " . $e->getMessage());
                http_response_code(500);
                // Afficher un message d'erreur générique ou rediriger
                 $_SESSION['message'] = "Une erreur est survenue lors du chargement de votre compte.";
                 $_SESSION['message_type'] = 'error';
                 // Rediriger vers l'accueil ou afficher un template d'erreur
                 header('Location: /home');
                 exit;
            }
        } else {
            // Gérer d'autres méthodes comme POST si nécessaire pour les mises à jour de compte plus tard
            http_response_code(405);
            echo "Méthode non autorisée pour cette URL.";
            exit;
        }
        break;
    case '/create-sumup-checkout':
        if ($method === 'POST') {

            require_once __DIR__ . '/../config/sumup.php';


            if (!isset($_SESSION['user_id'])) {
                header('Location: /login?error=not_logged_in');
                exit;
            }
            $userId = $_SESSION['user_id'];


            $itemType = filter_input(INPUT_POST, 'item_type', FILTER_SANITIZE_STRING);
            $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
            $isUpgradeRequest = filter_input(INPUT_POST, 'is_upgrade', FILTER_VALIDATE_INT) === 1;

            if (!$itemType || !$itemId) {
                $_SESSION['message'] = "Informations de paiement invalides.";
                $_SESSION['message_type'] = 'error';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/home'));
                exit;
            }


            $amount = 0.0;
            $description = 'Achat BDE';
            // La référence de checkout et les métadonnées seront définies spécifiquement pour l'événement/l'adhésion
            $checkout_reference = null;
            $metadata = [];
            $registrationId = null; // Pour les événements

            try {
                $db = Database::getInstance();
                $user = $db->getUserById($userId);
                if (!$user) throw new Exception("Utilisateur non trouvé.");

                if ($itemType === 'membership') {
                    // --- Logique de Checkout d'Adhésion (largement inchangée) ---
                    $checkout_reference = uniqid('bde_membership_', true); // Conserver le préfixe unique pour les adhésions
                    $metadata = [
                        'user_id' => $userId,
                        'item_type' => $itemType,
                        'item_id' => $itemId,
                        'checkout_ref' => $checkout_reference
                    ];
                    $membership = $db->getMembershipById($itemId);
                    if (!$membership) throw new Exception("Adhésion non trouvée.");

                    $finalPrice = $membership['price'];
                    $productName = 'Adhésion: ' . $membership['name'];
                    $metadata['duration_days'] = $membership['duration_days'];


                    if ($isUpgradeRequest && $membership['name'] === 'Premium') {
                        $activeMemberships = $db->getUserActiveMemberships($userId);
                        $userActiveSoutien = null;
                        foreach ($activeMemberships as $activeMembership) {
                            if ($activeMembership['membership_name'] === 'Soutien') {
                                $userActiveSoutien = $activeMembership; break;
                            }
                        }
                        if ($userActiveSoutien) {
                            $soutienDetails = $db->getMembershipByName('Soutien');
                            if ($soutienDetails) {
                                $sPrice = filter_var($soutienDetails['price'], FILTER_VALIDATE_FLOAT);
                                $pPrice = filter_var($membership['price'], FILTER_VALIDATE_FLOAT);
                                if ($sPrice !== false && $pPrice !== false && $pPrice > $sPrice) {
                                    $upgradePrice = $pPrice - $sPrice;
                                    if ($upgradePrice > 0) {
                                        $finalPrice = $upgradePrice;
                                        $productName = 'Mise à niveau vers Adhésion: Premium';
                                        $metadata['is_upgrade'] = '1';
                                        $metadata['old_membership_id'] = $userActiveSoutien['user_membership_id'];
                                    }
                                }
                            }
                        }
                    }
                    $amount = $finalPrice;
                    $description = $productName;

                } elseif ($itemType === 'event') {
                    $event = $db->getEventById($itemId);
                    if (!$event) throw new Exception("Événement non trouvé.");
                    if ($event['price'] <= 0) throw new Exception("Cet événement est gratuit.");

                    $price_to_pay = (float)$event['price'];

                    $user = $db->getUserById($userId); // Récupérer les données utilisateur
                    $discount_percentage = 0; // Initialiser le pourcentage de réduction

                    if ($user && $user['membership_status'] === 'premium') {
                        // Utilisateur Premium : Appliquer 10% de réduction
                        $discount_percentage = 10;
                        $discounted_price = $price_to_pay * (1 - ($discount_percentage / 100));
                        $price_to_pay = max(0.01, $discounted_price);
                    } else {
                        // Non Premium : Vérifier la réduction de l'adhésion active
                        $activeMemberships = $db->getUserActiveMemberships($userId);
                        if (!empty($activeMemberships)) {
                            $activeMembership = $activeMemberships[0];
                            if (isset($activeMembership['discount_percentage']) && $activeMembership['discount_percentage'] > 0) {
                                $discount_percentage = (float)$activeMembership['discount_percentage'];
                                $discounted_price = $price_to_pay * (1 - ($discount_percentage / 100));
                                $price_to_pay = max(0.01, $discounted_price);
                            }
                        }
                    }
                    $amount = $price_to_pay;
                    $description = 'Inscription: ' . $event['title'];

                    // --- Créer une Inscription d'Événement en Attente AVANT SumUp ---
                    $registrationId = $db->createEventRegistration($userId, $itemId, 'pending', null);
                    if (!$registrationId) {
                        throw new Exception("Impossible de créer l'enregistrement initial pour l'événement.");
                    }

                    // --- Définir la Référence de Checkout et les Métadonnées Spécifiques à l'Événement ---
                    $checkout_reference = 'EVENT-REG-' . $registrationId; // Utiliser l'ID d'inscription dans la référence
                    $metadata = [
                        'user_id' => $userId,
                        'item_type' => $itemType,
                        'item_id' => $itemId,
                        'registration_id' => $registrationId, // Stocker l'ID d'inscription
                        'checkout_ref' => $checkout_reference
                    ];

                } else {
                    throw new Exception("Type d'article non supporté.");
                }

                // --- Logique de Checkout Commune ---
                if ($amount < 0.01) {
                     throw new Exception("Le montant final est trop bas pour le paiement.");
                }
                if ($checkout_reference === null) {
                    throw new Exception("Référence de checkout non définie.");
                }


                $guzzleClient = new \GuzzleHttp\Client();
                $tokenResponse = $guzzleClient->post(SUMUP_AUTH_URL, [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => SUMUP_CLIENT_ID,
                        'client_secret' => SUMUP_CLIENT_SECRET

                    ]
                ]);

                if ($tokenResponse->getStatusCode() !== 200) {
                    throw new Exception("Impossible d'obtenir le token d'accès SumUp.");
                }
                $tokenData = json_decode($tokenResponse->getBody(), true);
                $accessToken = $tokenData['access_token'] ?? null;
                if (!$accessToken) {
                     throw new Exception("Token d'accès SumUp manquant dans la réponse.");
                }


                $redirectBase = rtrim(BASE_URL, '/');
                $checkoutPayload = [
                    'checkout_reference' => $checkout_reference,
                    'amount' => round($amount, 2),
                    'currency' => 'EUR',
                    'pay_to_email' => $user['email'] ?? '',
                    'description' => $description,
                    'merchant_code' => null, // Utiliser votre code marchand si disponible
                    // --- Définir les URL de Redirection Spécifiques basées sur itemType ---
                    'return_url' => $redirectBase . ($itemType === 'event' ? '/payment/event/success' : '/payment-success') . '?checkout_reference=' . urlencode($checkout_reference),
                    'redirect_url' => $redirectBase . ($itemType === 'event' ? '/payment/event/success' : '/payment-success') . '?checkout_reference=' . urlencode($checkout_reference), // Souvent identique à return_url
                    'cancel_url' => $redirectBase . ($itemType === 'event' ? '/payment/event/cancel' : '/payment-cancel') . '?checkout_reference=' . urlencode($checkout_reference), // Ajouter l'URL d'annulation


                ];


                    // --- DÉBUT : Insertion dans pending_sumup_transactions ---
                    try {
                        // S'assurer que l'instance DB est disponible (elle devrait l'être depuis plus tôt dans le bloc try)
                        if (!isset($db)) { $db = Database::getInstance(); } // Obtenir l'instance si elle a été perdue d'une manière ou d'une autre
                        $stmt = $db->prepare("INSERT INTO pending_sumup_transactions (checkout_reference, user_id, membership_id, status) VALUES (:checkout_ref, :user_id, :membership_id, 'pending')");
                        $stmt->bindValue(':checkout_ref', $checkout_reference, SQLITE3_TEXT);
                        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
                        $stmt->bindValue(':membership_id', $itemId, SQLITE3_INTEGER); // itemId est membership_id dans ce contexte
                        $stmt->execute();
                        error_log("Transaction SumUp en attente insérée pour checkout_reference : " . $checkout_reference);
                    } catch (Exception $dbException) {
                        // Si l'insertion de la transaction en attente échoue, nous ne devrions pas procéder à SumUp
                        error_log("Erreur Base de Données : Échec de l'insertion de la transaction SumUp en attente pour checkout_reference {$checkout_reference}: " . $dbException->getMessage());
                        // Relancer pour être attrapé par le bloc catch externe et afficher une erreur à l'utilisateur
                        throw new Exception("Impossible d'enregistrer la transaction en attente avant le paiement.");
                    }
                    // --- FIN : Insertion dans pending_sumup_transactions ---



                $checkoutResponse = $guzzleClient->post(SUMUP_CHECKOUT_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $checkoutPayload
                ]);

                if ($checkoutResponse->getStatusCode() !== 200 && $checkoutResponse->getStatusCode() !== 201) {
                     $errorBody = $checkoutResponse->getBody()->getContents();
                     error_log("Erreur Création Checkout SumUp : Statut {$checkoutResponse->getStatusCode()}, Corps : {$errorBody}");
                     throw new Exception("Erreur lors de la création du checkout SumUp.");
                }

                $checkoutData = json_decode($checkoutResponse->getBody(), true);
                $payToUrl = $checkoutData['pay_to_url'] ?? null;
                $checkoutId = $checkoutData['id'] ?? null;

                if (!$payToUrl || !$checkoutId) {
                    error_log("Erreur Création Checkout SumUp : pay_to_url ou id manquant dans la réponse. " . json_encode($checkoutData));
                    throw new Exception("Réponse invalide de l'API SumUp lors de la création du checkout.");
                }


                 // Le stockage en session est supprimé car la vérification repose sur les webhooks et la table pending_sumup_transactions.
                 // L'$checkoutId de SumUp pourrait être utile à stocker dans pending_sumup_transactions si nécessaire plus tard pour les recherches API.


                header('Location: ' . $payToUrl);
                exit;

            } catch (Exception $e) {
                error_log("Erreur Checkout SumUp : " . $e->getMessage());
                $_SESSION['message'] = "Erreur lors de l'initialisation du paiement SumUp : " . $e->getMessage();
                $_SESSION['message_type'] = 'error';

                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/home'));
                exit;
            }

        } else {
            http_response_code(405);
            echo "Méthode non autorisée.";
            exit;
        }
        break;

    // --- Succès de Paiement Générique (Conserver pour les Adhésions pour l'instant) ---
    case '/payment-success':
        $matched = true;
        if ($method === 'GET') {
            // Cette page est atteinte après le retour de SumUp pour les articles non-événementiels.
            // La confirmation et le traitement réels du paiement se font via le /sumup-webhook.
            // Nous affichons un message générique ici.
            $sumup_checkout_ref = $_GET['checkout_reference'] ?? null;
            $sumup_status = $_GET['status'] ?? null; // SumUp pourrait ajouter ceci

            $layout_vars['page_title'] = "Paiement Initié";
            $layout_vars['success_message'] = "Votre paiement a été initié avec succès. Vous recevrez une confirmation par email (pour les événements) ou votre statut sera mis à jour sur le site (pour les adhésions) une fois le paiement validé par SumUp.";
            $layout_vars['error_message'] = null;

            // Enregistrer le retour pour le débogage
            error_log("Retour à /payment-success (Générique) depuis SumUp. Réf : " . ($sumup_checkout_ref ?? 'N/A'));

            $page_content = TEMPLATE_PATH . 'payment_success.php'; // Utiliser le template de succès générique
        } else {
            http_response_code(405);
            echo "Méthode non autorisée.";
            exit;
        }
        break;

    // --- Succès de Paiement Spécifique à l'Événement ---
    case '/payment/event/success':
        $matched = true;
        if ($method === 'GET') {
            $checkout_reference = $_GET['checkout_reference'] ?? null;

            // Cette page indique seulement que l'utilisateur est revenu. La vérification se fait via webhook.
            $layout_vars['page_title'] = "Paiement Initié";
            $layout_vars['success_message'] = "Votre paiement pour l'événement a été initié. Vous recevrez une confirmation par email une fois le paiement validé par SumUp.";
            $layout_vars['error_message'] = null;

            // Enregistrer le retour pour le débogage
            error_log("Retour à /payment/event/success depuis SumUp. Réf : " . ($checkout_reference ?? 'N/A'));

            $page_content = TEMPLATE_PATH . 'payment_success.php'; // Utiliser le template de succès générique
        } else {
            http_response_code(405);
            echo "Méthode non autorisée.";
            exit;
        }
        break;

    // --- Annulation de Paiement Générique (Conserver pour les Adhésions pour l'instant) ---
    case '/payment-cancel':
        $matched = true;
        if ($method === 'GET') {
            $checkout_reference = $_GET['checkout_reference'] ?? null;

            // Enregistrer l'annulation
            error_log("Retour à /payment-cancel (Générique) depuis SumUp. Réf : " . ($checkout_reference ?? 'N/A'));
            // Le webhook gérera la mise à jour réelle du statut si le checkout a été annulé côté SumUp.

            $layout_vars['page_title'] = "Paiement Annulé";
            $layout_vars['error_message'] = "Votre paiement a été annulé ou a échoué. Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le BDE.";
            $page_content = TEMPLATE_PATH . 'payment_cancel.php'; // Utiliser le template d'annulation générique
        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break;

    // --- Annulation de Paiement Spécifique à l'Événement ---
    case '/payment/event/cancel':
        $matched = true;
        if ($method === 'GET') {
            $checkout_reference = $_GET['checkout_reference'] ?? null;

            // Enregistrer l'annulation
            error_log("Retour à /payment/event/cancel depuis SumUp. Réf : " . ($checkout_reference ?? 'N/A'));
            // Le webhook gérera la mise à jour réelle du statut.

            $layout_vars['page_title'] = "Paiement Annulé";
            $layout_vars['error_message'] = "Votre inscription à l'événement ou le paiement associé a été annulé(e).";
            $page_content = TEMPLATE_PATH . 'payment_cancel.php'; // Utiliser le template d'annulation générique
        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break;

    // --- Webhook SumUp (Corrigé avec stockage persistant) ---
    case '/sumup-webhook':
        $matched = true;
        if ($method === 'POST') {
            require_once __DIR__ . '/../config/sumup.php';
            // Charger la config pour obtenir le secret du webhook
            require_once __DIR__ . '/../config/sumup.php';
            // IMPORTANT : Définir SUMUP_WEBHOOK_SECRET dans config/sumup.php ou les variables d'environnement
            if (!defined('SUMUP_WEBHOOK_SECRET')) {
                 error_log("Erreur Webhook SumUp : SUMUP_WEBHOOK_SECRET n'est pas défini dans la config.");
                 http_response_code(500); // Erreur Interne du Serveur
                 exit;
            }

            $payload = file_get_contents('php://input');
            $signature = $_SERVER['HTTP_X_SUMUP_SIGNATURE'] ?? '';

            // --- Vérification de la Signature du Webhook ---
            $calculatedSignature = hash_hmac('sha256', $payload, SUMUP_WEBHOOK_SECRET);
            if (!hash_equals($signature, $calculatedSignature)) {
                error_log("Erreur Webhook SumUp : Signature invalide. Reçue : '{$signature}'");
                http_response_code(401); // Non Autorisé
                exit;
            }
            // --- Fin de la Vérification de la Signature ---

            error_log("Payload Webhook SumUp Reçu (Signature OK) : " . $payload);

            $data = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                error_log("Erreur Webhook SumUp : JSON invalide reçu.");
                http_response_code(400); // Mauvaise Requête
                exit;
            }

            $eventType = $data['event_type'] ?? null;
            $checkoutReference = $data['checkout_reference'] ?? null;
            $transactionId = $data['transaction_id'] ?? null; // L'ID de transaction réel de SumUp

            if (!$checkoutReference) {
                error_log("Erreur Webhook SumUp : checkout_reference manquant dans le payload. Payload : " . $payload);
                http_response_code(200); // Accuser réception même si les données sont incomplètes
                exit;
            }

            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection(); // Obtenir l'instance PDO pour les transactions si nécessaire

                // Trouver la transaction en attente
                $stmt = $pdo->prepare("SELECT id, user_id, membership_id, status FROM pending_sumup_transactions WHERE checkout_reference = :checkout_ref");
                $stmt->bindValue(':checkout_ref', $checkoutReference, PDO::PARAM_STR);
                $stmt->execute();
                $pendingTx = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$pendingTx) {
                    error_log("Avertissement Webhook SumUp : Aucune transaction en attente trouvée pour checkout_reference : " . $checkoutReference);
                    http_response_code(200); // Accuser réception, mais rien à traiter
                    exit;
                }

                $pendingTxId = $pendingTx['id'];
                $currentStatus = $pendingTx['status'];

                // Gérer en fonction du type d'événement
                if ($eventType === 'PAYMENT_SUCCESSFUL') {
                    // Vérifier si déjà traité ou non en attente
                    if ($currentStatus !== 'pending') {
                        error_log("Info Webhook SumUp : Transaction {$checkoutReference} déjà traitée ou non en état d'attente (actuel : {$currentStatus}). Ignoré.");
                        http_response_code(200);
                        exit;
                    }

                    if (!$transactionId) {
                         error_log("Erreur Webhook SumUp : transaction_id manquant dans le payload PAYMENT_SUCCESSFUL pour checkout_reference : " . $checkoutReference);
                         // Essayer quand même de traiter mais enregistrer l'ID manquant
                         $transactionId = 'SUMUP_ID_MISSING_' . $checkoutReference; // ID de secours
                    }

                    $userId = $pendingTx['user_id'];
                    $membershipId = $pendingTx['membership_id'];

                    $userId = $pendingTx['user_id'];
                    $itemId = $pendingTx['membership_id']; // En supposant que cela stocke aussi event_id pour les événements, basé sur la logique INSERT

                    // Déterminer le Type d'Article basé sur le préfixe de checkout_reference
                    $itemType = null;
                    if (strpos($checkoutReference, 'EVENT-REG-') === 0) {
                        $itemType = 'event';
                    } elseif (strpos($checkoutReference, 'bde_membership_') === 0) {
                        $itemType = 'membership';
                    }

                    if ($itemType === 'membership') {
                        $membershipId = $itemId;
                        // Récupérer la durée de l'adhésion
                        $membershipDetails = $db->getMembershipById($membershipId);
                        if (!$membershipDetails || !isset($membershipDetails['duration_days'])) {
                            throw new Exception("Impossible de récupérer les détails de l'adhésion ou la durée pour l'ID d'adhésion : {$membershipId}");
                        }
                        $durationDays = (int)$membershipDetails['duration_days'];

                        // Calculer les dates
                        $startDate = date('Y-m-d');
                        $endDate = date('Y-m-d', strtotime("+{$durationDays} days"));
                        $purchaseDate = date('Y-m-d H:i:s');

                        // --- Créer l'Adhésion Utilisateur ---
                        $membershipCreated = $db->createUserMembership(
                            (int)$userId, (int)$membershipId, $startDate, $endDate, $purchaseDate, $transactionId
                        );

                        if ($membershipCreated) {
                            // Mettre à jour le statut de la transaction en attente à 'completed'
                            $updateStmt = $pdo->prepare("UPDATE pending_sumup_transactions SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                            $updateStmt->bindValue(':id', $pendingTxId, PDO::PARAM_INT);
                            $updateStmt->execute();
                            error_log("Succès Webhook SumUp : Adhésion créée pour l'utilisateur {$userId}, adhésion {$membershipId}. Transaction en attente {$pendingTxId} marquée comme complétée. ID Tx SumUp : {$transactionId}");
                        } else {
                            error_log("Erreur CRITIQUE Webhook SumUp : Échec de la création de l'adhésion en DB pour l'utilisateur {$userId}, adhésion {$membershipId} après paiement réussi ! ID Tx SumUp : {$transactionId}");
                            // Envisager de définir le statut en attente à 'error'
                        }

                    } elseif ($itemType === 'event') {
                        $registrationId = (int) str_replace('EVENT-REG-', '', $checkoutReference);
                        if ($registrationId <= 0) {
                             throw new Exception("Impossible d'extraire un ID d'inscription valide de la référence de checkout : {$checkoutReference}");
                        }

                        // --- Mettre à Jour le Statut de l'Inscription à l'Événement ---
                        $updateSuccess = $db->updateEventRegistrationStatus($registrationId, 'completed', $transactionId);

                        if ($updateSuccess) {
                            // Mettre à jour le statut de la transaction en attente à 'completed'
                            $updateStmt = $pdo->prepare("UPDATE pending_sumup_transactions SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                            $updateStmt->bindValue(':id', $pendingTxId, PDO::PARAM_INT);
                            $updateStmt->execute();
                            error_log("Succès Webhook SumUp : Inscription à l'événement {$registrationId} marquée comme complétée pour l'utilisateur {$userId}. Transaction en attente {$pendingTxId} marquée comme complétée. ID Tx SumUp : {$transactionId}");

                            // --- Déclencher l'Email de Confirmation ---
                            try {
                                $user = $db->getUserById($userId);
                                $event = $db->getEventById($itemId); // $itemId est l'event_id ici

                                if ($user && $event) {
                                    $to = $user['email'];
                                    $eventName = $event['title'];
                                    // Formater la date pour la lisibilité
                                    $eventDateFormatted = 'Date non spécifiée';
                                    if (!empty($event['event_date'])) {
                                        try {
                                            $dateObj = new DateTime($event['event_date']);
                                            $eventDateFormatted = $dateObj->format('d/m/Y à H:i');
                                        } catch (Exception $e) {
                                             error_log("Erreur de formatage de la date de l'événement '{$event['event_date']}' pour l'email : " . $e->getMessage());
                                        }
                                    }
                                    $eventLocation = !empty($event['location']) ? $event['location'] : 'Lieu non spécifié';

                                    $subject = "Confirmation d'inscription - Événement : " . $eventName;

                                    $body = "Bonjour " . htmlspecialchars($user['first_name']) . ",\n\n";
                                    $body .= "Votre inscription à l'événement '" . htmlspecialchars($eventName) . "' a été confirmée avec succès !\n\n";
                                    $body .= "Détails de l'événement :\n";
                                    $body .= "- Date : " . $eventDateFormatted . "\n";
                                    $body .= "- Lieu : " . htmlspecialchars($eventLocation) . "\n\n";
                                    $body .= "Merci de votre participation.\n\n";
                                    $body .= "Cordialement,\n";
                                    $body .= "Le BDE Info";

                                    // En-têtes basiques - Remplacer 'From' et 'Reply-To' par des adresses réelles si possible
                                    $headers = 'From: BDE Info <no-reply@bdeinfo.example.com>' . "\r\n" .
                                               'Reply-To: contact@bdeinfo.example.com' . "\r\n" .
                                               'X-Mailer: PHP/' . phpversion() . "\r\n" .
                                               'Content-Type: text/plain; charset=UTF-8'; // Assurer UTF-8

                                    // Utiliser mb_encode_mimeheader pour l'encodage du sujet si nécessaire, mais rester simple pour l'instant
                                    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

                                    if (mail($to, $encodedSubject, $body, $headers)) {
                                        error_log("Email de confirmation d'événement envoyé avec succès à {$to} pour l'ID d'inscription {$registrationId}");
                                    } else {
                                        error_log("Erreur CRITIQUE : Échec de l'envoi de l'email de confirmation d'événement via mail() à {$to} pour l'ID d'inscription {$registrationId}");
                                        // Note : mail() retourne false mais ne donne pas facilement de détails d'erreur spécifiques. Vérifier les logs du serveur mail.
                                    }
                                } else {
                                    error_log("Erreur lors de l'envoi de l'email de confirmation d'événement : Impossible de récupérer les détails de l'utilisateur ({$userId}) ou de l'événement ({$itemId}) pour l'ID d'inscription {$registrationId}");
                                }
                            } catch (Exception $emailEx) {
                                error_log("Exception attrapée lors de la préparation/l'envoi de l'email de confirmation d'événement pour l'ID d'inscription {$registrationId}: " . $emailEx->getMessage());
                            }
                            // --- Fin de l'Envoi d'Email ---

                        } else {
                             error_log("Erreur CRITIQUE Webhook SumUp : Échec de la mise à jour du statut de l'inscription à l'événement à complété pour l'ID reg {$registrationId} après paiement réussi ! ID Tx SumUp : {$transactionId}");
                             // Envisager de définir le statut en attente à 'error'
                        }

                    } else {
                         error_log("Avertissement Webhook SumUp : Impossible de déterminer le type d'article depuis checkout_reference : {$checkoutReference}. Impossible de traiter le succès.");
                         // Conserver le statut en attente comme 'pending' pour examen manuel
                    }

                } elseif ($eventType === 'PAYMENT_FAILED' || $eventType === 'CHECKOUT_CANCELLED') {
                    if ($currentStatus === 'pending') {
                        // Mettre à jour le statut de la transaction en attente à 'failed' ou 'cancelled'
                        $newStatus = ($eventType === 'CHECKOUT_CANCELLED') ? 'cancelled' : 'failed';
                        $updateStmt = $pdo->prepare("UPDATE pending_sumup_transactions SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                        $updateStmt->bindValue(':status', $newStatus, PDO::PARAM_STR);
                        $updateStmt->bindValue(':id', $pendingTxId, PDO::PARAM_INT);
                        $updateStmt->execute();
                        error_log("Info Webhook SumUp : Transaction en attente {$pendingTxId} pour checkout_reference {$checkoutReference} marquée comme '{$newStatus}' en raison du type d'événement : {$eventType}.");

                        // S'il s'agissait d'une inscription à un événement, mettre à jour son statut également
                        if (strpos($checkoutReference, 'EVENT-REG-') === 0) {
                            $registrationId = (int) str_replace('EVENT-REG-', '', $checkoutReference);
                            if ($registrationId > 0) {
                                try {
                                    $db->updateEventRegistrationStatus($registrationId, 'cancelled', null); // Utiliser 'cancelled' pour les deux types d'échec ici
                                    error_log("Info Webhook SumUp : Statut de l'inscription à l'événement {$registrationId} mis à jour à 'cancelled' en raison de {$eventType}.");
                                } catch (Exception $eventUpdateError) {
                                     error_log("Erreur Webhook SumUp : Échec de la mise à jour du statut de l'inscription à l'événement {$registrationId} à cancelled après {$eventType}: " . $eventUpdateError->getMessage());
                                }
                            }
                        }
                    } else {
                         error_log("Info Webhook SumUp : Reçu {$eventType} pour la transaction déjà traitée {$checkoutReference} (actuel : {$currentStatus}). Ignoré la mise à jour du statut.");
                    }
                } else {
                    error_log("Webhook SumUp : Type d'événement non géré reçu : " . ($eventType ?? 'NULL') . " pour checkout_reference : " . $checkoutReference);
                }

            } catch (Exception $e) {
                error_log("Erreur CRITIQUE Traitement Webhook SumUp pour checkout_reference {$checkoutReference}: " . $e->getMessage() . "\nTrace : " . $e->getTraceAsString());
                // Retourner 200 à SumUp pour éviter les nouvelles tentatives sur des erreurs potentiellement persistantes, mais enregistrer de manière approfondie.
                http_response_code(200);
                exit;
            }

            // Accuser réception à SumUp
            http_response_code(200);
            echo "Webhook processed."; // Optionnel : Envoyer un corps simple
            exit;

        } else {
            http_response_code(405); // Méthode Non Autorisée
            echo "Method Not Allowed.";
            exit;
        }
        break;


    case '/admin/dashboard': // Admin Dashboard Route
        $matched = true;
        if ($method === 'GET') { // GET /admin/dashboard
            // Admin check already performed at the top
            // User fetching logic removed, fetch dashboard specific data here.
            try {
                $db = Database::getInstance();
                $userCount = $db->getUserCount();
                $eventCount = $db->getEventCount(); // Counts all events (open and closed)
            } catch (Exception $e) {
                error_log("Error fetching dashboard counts: " . $e->getMessage());
                $userCount = 0; // Default to 0 on error
                $eventCount = 0;
                $layout_vars['error_message'] = "Erreur lors du chargement des statistiques du tableau de bord.";
            }

            $layout_vars['page_title'] = 'Tableau de Bord Admin'; // Title already in French
            $layout_vars['userCount'] = $userCount;
            $layout_vars['eventCount'] = $eventCount;
            $page_content = TEMPLATE_PATH . 'admin/dashboard.php';

        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break; // End of /admin/dashboard case

    // --- User Management Routes ---

case '/admin/users/add': // Add User Route (GET for form, POST for submission)
        $matched = true;
        // Admin check already performed

        if ($method === 'GET') { // Display the add user form
            $layout_vars['page_title'] = 'Ajouter un Utilisateur';
            $layout_vars['form_action'] = '/admin/users/add'; // POST to the same URL
            $layout_vars['user'] = null; // No existing user data for creation
            $layout_vars['isEditing'] = false; // Indicate we are creating

            // Retrieve potential errors and old input from a failed POST attempt
            $layout_vars['errors'] = $_SESSION['form_errors'] ?? [];
            $layout_vars['old_input'] = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_errors'], $_SESSION['form_data']); // Clear after use

            $page_content = TEMPLATE_PATH . 'admin/user_form.php';

        } elseif ($method === 'POST') { // Process the add user form submission
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? ''); // Keep password trim, hash handles spaces ok usually
            $isAdminInput = $_POST['is_admin'] ?? '0'; // Default to '0' (standard user)

            $errors = [];
            $formData = $_POST; // Store for repopulation on error

            // Validation
            if (empty($firstName)) $errors['first_name'] = "Le prénom est requis.";
            if (empty($lastName)) $errors['last_name'] = "Le nom est requis.";
            if (empty($email)) {
                $errors['email'] = "L'email est requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Format d'email invalide.";
            }
            if (empty($password)) {
                $errors['password'] = "Le mot de passe est requis.";
            } // Add more password complexity rules if needed, matching registration?
            if (!in_array($isAdminInput, ['0', '1'])) {
                 $errors['is_admin'] = "Rôle invalide sélectionné.";
                 $isAdmin = 0; // Default to non-admin on invalid input
            } else {
                 $isAdmin = (int)$isAdminInput;
            }

            if (empty($errors)) {
                try {
                    $db = Database::getInstance();

                    // Check if email already exists (addUser handles this, but good practice)
                    if ($db->getUserByEmail($email)) {
                        $errors['email'] = "Cette adresse email est déjà utilisée.";
                    } else {
                        // Hash the password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        // Add user to database
                        $success = $db->addUser($firstName, $lastName, $email, $hashedPassword, $isAdmin);

                        if ($success) {
                            $_SESSION['user_message'] = "Utilisateur '" . htmlspecialchars($email) . "' ajouté avec succès.";
                            $_SESSION['user_message_type'] = 'success';
                            header('Location: /admin/users'); // Redirect to user list on success
                            exit;
                        } else {
                            // General error if addUser failed for other reasons (DB error)
                            $errors['general'] = "Une erreur est survenue lors de l'ajout de l'utilisateur. Veuillez réessayer.";
                             error_log("addUser failed for email: {$email}. Check Database logs.");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error adding user: " . $e->getMessage());
                    $errors['general'] = "Une erreur technique est survenue lors de l'ajout de l'utilisateur.";
                }
            }

            // If errors occurred (validation or DB), store errors and form data in session and redirect back
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                // Don't store the password back into the session/form data
                unset($formData['password']);
                $_SESSION['form_data'] = $formData;
                header('Location: /admin/users/add'); // Redirect back to the GET form
                exit;
            }

        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break; // End of /admin/users/add case
    case '/admin/users': // GET /admin/users - Display user list
        $matched = true;
        if ($method === 'GET') {
            // Admin check already performed
            try {
                $db = Database::getInstance();
                $searchTerm = trim($_GET['search'] ?? ''); // Get search term

                // Fetch users with lock status, potentially filtered by search term
                $users = $db->getAllUsersWithLockStatus($searchTerm);

                $layout_vars['users'] = $users; // Pass users to the template
                $layout_vars['searchTerm'] = $searchTerm; // Pass search term for the input field value

                // Handle success/error messages from user actions
                if (isset($_SESSION['user_message'])) {
                    // Use the specific keys expected by the manage_users template
                    if ($_SESSION['user_message_type'] === 'success') {
                        $layout_vars['user_success'] = $_SESSION['user_message'];
                    } else {
                        $layout_vars['user_error'] = $_SESSION['user_message'];
                    }
                    unset($_SESSION['user_message'], $_SESSION['user_message_type']);
                }

            } catch (Exception $e) {
                error_log("Error fetching users for admin user management: " . $e->getMessage());
                $layout_vars['users'] = []; // Pass empty array on error
                $layout_vars['searchTerm'] = $searchTerm ?? '';
                $layout_vars['user_error'] = "Failed to load user list."; // Inform admin
            }
            $layout_vars['page_title'] = 'Gestion des Utilisateurs';
            $page_content = TEMPLATE_PATH . 'admin/manage_users.php';

        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break; // End of /admin/users case

    case '/admin/users/lock':
        $matched = true;
        if ($method === 'POST') { // POST /admin/users/lock
            // Admin check already performed
            $userIdToLock = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $redirectUrl = '/admin/users'; // Redirect back to user list

            if (!$userIdToLock || $userIdToLock <= 0) {
                $_SESSION['user_message'] = "ID utilisateur invalide fourni pour le verrouillage.";
                $_SESSION['user_message_type'] = 'error';
            } else {
                try {
                    $db = Database::getInstance();
                    $targetUser = $db->getUserById($userIdToLock);
                    if ($targetUser && $targetUser['is_admin'] == 1) {
                        $_SESSION['user_message'] = "Impossible de verrouiller un compte administrateur.";
                        $_SESSION['user_message_type'] = 'error';
                    } elseif ($db->setUserLockStatus($userIdToLock, 1)) { // 1 = locked
                        $_SESSION['user_message'] = "Compte utilisateur (ID : {$userIdToLock}) verrouillé avec succès.";
                        $_SESSION['user_message_type'] = 'success';
                    } else {
                        $_SESSION['user_message'] = "Échec du verrouillage du compte utilisateur (ID : {$userIdToLock}). L'utilisateur n'existe peut-être pas.";
                        $_SESSION['user_message_type'] = 'error';
                    }
                } catch (Exception $e) {
                    error_log("Error locking user account ID {$userIdToLock}: " . $e->getMessage());
                    $_SESSION['user_message'] = "Une erreur est survenue lors du verrouillage du compte.";
                    $_SESSION['user_message_type'] = 'error';
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        } else { http_response_code(405); echo "Méthode non autorisée."; exit; }
        break; // End of /admin/users/lock case

    case '/admin/users/unlock':
        $matched = true;
        if ($method === 'POST') { // POST /admin/users/unlock
            // Admin check already performed
            $userIdToUnlock = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $redirectUrl = '/admin/users'; // Redirect back to user list

            if (!$userIdToUnlock || $userIdToUnlock <= 0) {
                $_SESSION['user_message'] = "ID utilisateur invalide fourni pour le déverrouillage.";
                $_SESSION['user_message_type'] = 'error';
            } else {
                try {
                    $db = Database::getInstance();
                    // Allow unlocking admin, no specific check needed here unless policy changes

                    if ($db->setUserLockStatus($userIdToUnlock, 0)) { // 0 = unlocked, also resets attempts
                        $_SESSION['user_message'] = "Compte utilisateur (ID : {$userIdToUnlock}) déverrouillé avec succès.";
                        $_SESSION['user_message_type'] = 'success';
                    } else {
                        $_SESSION['user_message'] = "Échec du déverrouillage du compte utilisateur (ID : {$userIdToUnlock}). L'utilisateur n'existe peut-être pas.";
                        $_SESSION['user_message_type'] = 'error';
                    }
                } catch (Exception $e) {
                    error_log("Error unlocking user account ID {$userIdToUnlock}: " . $e->getMessage());
                    $_SESSION['user_message'] = "Une erreur est survenue lors du déverrouillage du compte.";
                    $_SESSION['user_message_type'] = 'error';
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        } else { http_response_code(405); echo "Méthode non autorisée."; exit; }
        break; // End of /admin/users/unlock case

    case '/admin/users/delete':
        $matched = true;
        if ($method === 'POST') { // POST /admin/users/delete
            // Admin check already performed
            $userIdToDelete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $redirectUrl = '/admin/users'; // Redirect back to user list

            if (!$userIdToDelete || $userIdToDelete <= 0) {
                 $_SESSION['user_message'] = "ID utilisateur invalide fourni pour la suppression.";
                 $_SESSION['user_message_type'] = 'error';
            } elseif ($userIdToDelete === $_SESSION['user_id']) {
                 $_SESSION['user_message'] = "Vous ne pouvez pas supprimer votre propre compte.";
                 $_SESSION['user_message_type'] = 'error';
            } else {
                try {
                    $db = Database::getInstance();
                    // deleteUser method already contains the admin check
                    if ($db->deleteUser($userIdToDelete)) {
                        $_SESSION['user_message'] = "Compte utilisateur (ID : {$userIdToDelete}) supprimé avec succès.";
                        $_SESSION['user_message_type'] = 'success';
                    } else {
                        // Error message could be due to non-existence or admin protection
                        $_SESSION['user_message'] = "Échec de la suppression du compte utilisateur (ID : {$userIdToDelete}). L'utilisateur est peut-être un administrateur ou n'existe pas.";
                        $_SESSION['user_message_type'] = 'error';
                    }
                } catch (Exception $e) {
                    error_log("Error deleting user account ID {$userIdToDelete}: " . $e->getMessage());
                    $_SESSION['user_message'] = "Une erreur est survenue lors de la suppression du compte.";
                    $_SESSION['user_message_type'] = 'error';
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        } else { http_response_code(405); echo "Méthode non autorisée."; exit; }
        break; // End of /admin/users/delete

    case '/admin/users/modify':
        $matched = true;
        $userIdToModify = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        // Redirect to user list for errors before form load or on success/major POST error
        $redirectUrl = '/admin/users';
        // Redirect back to the form itself on validation errors during POST
        $formUrl = '/admin/users/modify?id=' . $userIdToModify;

        if (!$userIdToModify || $userIdToModify <= 0) {
            $_SESSION['user_message'] = "ID utilisateur invalide fourni pour la modification.";
            $_SESSION['user_message_type'] = 'error';
            header('Location: ' . $redirectUrl); // Redirect to user list
            exit;
        }

        if ($method === 'GET') { // GET /admin/users/modify?id={id} - Show edit form
            // Admin check performed
            try {
                $db = Database::getInstance();
                $user = $db->getUserById($userIdToModify);

                if (!$user) {
                    $_SESSION['user_message'] = "Utilisateur non trouvé pour la modification (ID : {$userIdToModify}).";
                    $_SESSION['user_message_type'] = 'error';
                    header('Location: ' . $redirectUrl); // Redirect to user list
                    exit;
                }

                // Prevent modification of admin accounts via this form
                if ($user['is_admin'] == 1) {
                     $_SESSION['user_message'] = "Les comptes administrateur ne peuvent pas être modifiés via cette interface.";
                     $_SESSION['user_message_type'] = 'error';
                     header('Location: ' . $redirectUrl); // Redirect to user list
                     exit;
                }

                $layout_vars['page_title'] = 'Modify User: ' . htmlspecialchars($user['email']);
                $layout_vars['form_action'] = '/admin/users/modify?id=' . $userIdToModify; // POST to the same URL
                $layout_vars['user'] = $user; // Pass user data to the template

                // Use session data if available (from failed POST), otherwise use DB data
                $layout_vars['form_data'] = $_SESSION['form_data'] ?? $user;
                unset($_SESSION['form_data']); // Clear session data after use

                // Pass session message (likely errors from failed POST)
                if (isset($_SESSION['user_message'])) {
                     $layout_vars['error_message'] = $_SESSION['user_message']; // Assume it's an error if set here
                     unset($_SESSION['user_message'], $_SESSION['user_message_type']);
                }

                $layout_vars['isEditing'] = true;
                $page_content = TEMPLATE_PATH . 'admin/user_form.php';

            } catch (Exception $e) {
                error_log("Error loading user modification form for ID {$userIdToModify}: " . $e->getMessage());
                $_SESSION['user_message'] = "Erreur lors du chargement des données utilisateur pour la modification.";
                $_SESSION['user_message_type'] = 'error';
                header('Location: ' . $redirectUrl);
                exit;
            }

        } elseif ($method === 'POST') { // POST /admin/users/modify?id={id} - Process update
            // Admin check performed
            $email = trim($_POST['email'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');

            $errors = [];
            if (empty($first_name)) $errors[] = "Le prénom est requis.";
            if (empty($last_name)) $errors[] = "Le nom est requis.";
            if (empty($email)) {
                $errors[] = "L'email est requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide.";
            }

            if (empty($errors)) {
                try {
                    $db = Database::getInstance();
                    // Double-check target user isn't admin before attempting update
                    $targetUser = $db->getUserById($userIdToModify);
                    if (!$targetUser) {
                         // Should not happen if GET worked, but check anyway
                         $_SESSION['user_message'] = "Utilisateur à modifier (ID : {$userIdToModify}) non trouvé lors de la mise à jour.";
                         $_SESSION['user_message_type'] = 'error';
                         header('Location: ' . $redirectUrl); // Redirect to user list
                         exit;
                    }
                    if ($targetUser['is_admin'] == 1) {
                        $_SESSION['user_message'] = "Les comptes administrateur ne peuvent pas être modifiés.";
                        $_SESSION['user_message_type'] = 'error';
                        header('Location: ' . $redirectUrl); // Redirect to user list
                        exit;
                    }

                    $updateData = [
                        'email' => $email,
                        'first_name' => $first_name,
                        'last_name' => $last_name
                        // is_admin is intentionally omitted
                    ];

                    if ($db->updateUser($userIdToModify, $updateData)) {
                        $_SESSION['user_message'] = "Utilisateur (ID : {$userIdToModify}) mis à jour avec succès.";
                        $_SESSION['user_message_type'] = 'success';
                        header('Location: ' . $redirectUrl); // Redirect to user list on success
                        exit;
                    } else {
                        // Check if error was due to unique constraint (email exists)
                        // This requires the updateUser method to potentially signal this specific error.
                        // For now, use a general error message.
                        $errors[] = "Échec de la mise à jour de l'utilisateur. L'adresse e-mail est peut-être déjà utilisée par un autre compte.";
                         error_log("Update failed for user ID {$userIdToModify}. Potential email conflict or DB error.");
                    }
                } catch (Exception $e) {
                    error_log("Error updating user ID {$userIdToModify}: " . $e->getMessage());
                    $errors[] = "Une erreur technique est survenue lors de la mise à jour.";
                }
            }

            // If errors occurred (validation or DB update failure), store errors and form data in session and redirect back to GET form
            if (!empty($errors)) {
                $_SESSION['user_message'] = implode('<br>', $errors);
                $_SESSION['user_message_type'] = 'error'; // Mark as error for display
                $_SESSION['form_data'] = $_POST; // Store submitted data for repopulation
                header('Location: ' . $formUrl); // Redirect back to the GET form
                exit;
            }
        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break; // End of /admin/users/modify

    // --- Event Management Routes ---
    case '/admin/events':
        $matched = true;
        if ($method === 'GET') {
            try {
                $db = Database::getInstance();
                $events = $db->getOpenEventsAdmin(); // Fetch only open events
                $layout_vars['events'] = $events;
            } catch (Exception $e) {
                error_log("Erreur lors de la récupération de la liste des événements admin : " . $e->getMessage());
                $layout_vars['events'] = [];
                $layout_vars['error_message'] = "Erreur lors du chargement de la liste des événements.";
            }
            $layout_vars['page_title'] = 'Gestion des Événements';
            $page_content = TEMPLATE_PATH . 'admin/events_list.php';
        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break; // Prevent fall-through to history case
// GET /admin/events/history - Afficher les événements clôturés (historique)
        case '/admin/events/history':
            $matched = true;
            if ($method === 'GET') {
                try {
                    $db = Database::getInstance();
                    $closedEvents = $db->getClosedEventsAdmin(); // Nouvelle méthode pour récupérer les événements clôturés
                    $layout_vars['events'] = $closedEvents;
                    $layout_vars['is_history_view'] = true; // Indicateur pour le template
                } catch (Exception $e) {
                    error_log("Erreur lors de la récupération de l'historique des événements admin : " . $e->getMessage());
                    $layout_vars['events'] = [];
                    $layout_vars['error_message'] = "Erreur lors du chargement de l'historique des événements.";
                }
                $layout_vars['page_title'] = 'Historique des Événements';
                $page_content = TEMPLATE_PATH . 'admin/events_list.php'; // Réutiliser le template de liste
            } else {
                 http_response_code(405); // Method Not Allowed
                 $page_content = TEMPLATE_PATH . '405.php'; // Optionnel: template pour 405
            }
            break;
        break;
    // GET /admin/events/edit?id={id} - Afficher le Formulaire de Modification d'Événement
    case '/admin/events/edit':
        $matched = true;
        if ($method === 'GET') {
            // Vérification admin déjà effectuée en haut

            // Valider et obtenir l'ID de l'événement depuis le paramètre de requête
            if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
                $_SESSION['message'] = "ID d'événement manquant ou invalide pour la modification.";
                $_SESSION['message_type'] = 'error';
                header('Location: /admin/events');
                exit;
            }
            $eventId = (int)$_GET['id'];

            try {
                $db = Database::getInstance();
                $event = $db->getEventById($eventId);

                if (!$event) {
                    $_SESSION['message'] = "Événement non trouvé pour la modification (ID: {$eventId}).";
                    $_SESSION['message_type'] = 'error';
                    header('Location: /admin/events');
                    exit;
                }

                $layout_vars['page_title'] = 'Modifier l\'Événement : ' . htmlspecialchars($event['title']);
                $layout_vars['form_action'] = '/admin/events/edit?id=' . $eventId; // Pointer POST vers cette même route
                $layout_vars['event'] = $event; // Passer les données de l'événement au formulaire
                $layout_vars['form_data'] = $event; // Pré-remplir les données du formulaire
                $layout_vars['isEditing'] = true; // Indicateur pour le template
                $page_content = TEMPLATE_PATH . 'admin/event_form.php';

            } catch (Exception $e) {
                error_log("Erreur lors du chargement du formulaire de modification d'événement pour l'ID {$eventId}: " . $e->getMessage());
                $_SESSION['message'] = "Erreur lors du chargement du formulaire de modification.";
                $_SESSION['message_type'] = 'error';
                header('Location: /admin/events');
                exit;
            }
        } elseif ($method === 'POST') {
            // POST /admin/events/edit?id={id} - Gérer la Logique de Mise à Jour d'Événement
            // Vérification admin déjà effectuée

            // Valider et obtenir l'ID de l'événement depuis le paramètre de requête
            if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
                $_SESSION['message'] = "ID d'événement manquant ou invalide pour la mise à jour.";
                $_SESSION['message_type'] = 'error';
                header('Location: /admin/events');
                exit;
            }
            $eventId = (int)$_GET['id'];

            // --- Traitement des Données du Formulaire ---
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $event_date_input = trim($_POST['event_date'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $price_input = $_POST['price'] ?? '0';
            $points_input = $_POST['points_awarded'] ?? '0';
            $image_file = $_FILES['image'] ?? null;

            $errors = [];
            $form_data = $_POST; // Pour remplir à nouveau le formulaire en cas d'erreur

            // --- Validation ---
            if (empty($title)) $errors[] = "Le titre est requis.";
            if (empty($event_date_input)) {
                $errors[] = "La date et l'heure de l'événement sont requises.";
                $event_date_db = null;
            } else {
                $event_date_obj = DateTime::createFromFormat('Y-m-d\TH:i', $event_date_input);
                if (!$event_date_obj) {
                    $errors[] = "Format de date invalide. Utilisez le sélecteur.";
                    $event_date_db = null;
                } else {
                    $event_date_db = $event_date_obj->format('Y-m-d H:i:s');
                }
            }
            if (!is_numeric($price_input) || floatval($price_input) < 0) {
                $errors[] = "Le prix doit être un nombre positif.";
                $price = 0.0;
            } else {
                $price = floatval($price_input);
            }
             if (!is_numeric($points_input) || intval($points_input) < 0) {
                $errors[] = "Les points attribués doivent être un nombre entier positif.";
                $points = 0;
            } else {
                $points = intval($points_input);
            }

            // --- Gestion de l'Image ---
            $image_path_db = null; // Contiendra le nouveau nom de fichier si téléchargé
            $imageChanged = false;
            $upload_dir = __DIR__ . '/../public/uploads/events/'; // Chemin corrigé relatif à index.php
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2Mo

            if ($image_file && $image_file['error'] === UPLOAD_ERR_OK) {
                // Nouvelle image téléchargée, valider et traiter
                if (!in_array($image_file['type'], $allowed_types)) {
                    $errors[] = "Type de fichier image non autorisé (JPEG, PNG, GIF uniquement).";
                } elseif ($image_file['size'] > $max_size) {
                    $errors[] = "L'image est trop volumineuse (max 2MB).";
                } else {
                    $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid('event_', true) . '.' . strtolower($file_extension);
                    $target_path = $upload_dir . $unique_filename;

                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0775, true)) {
                             $errors[] = "Impossible de créer le dossier de destination pour les images.";
                             error_log("Échec de la création du répertoire de téléchargement : " . $upload_dir);
                        }
                    }

                    if (is_dir($upload_dir) && empty($errors)) {
                        if (move_uploaded_file($image_file['tmp_name'], $target_path)) {
                            $image_path_db = $unique_filename; // Définir le nouveau chemin d'image pour la mise à jour DB
                            $imageChanged = true;
                        } else {
                            $errors[] = "Erreur lors du téléchargement de la nouvelle image.";
                            error_log("Échec du déplacement du fichier téléchargé vers : " . $target_path);
                        }
                    }
                }
            } elseif ($image_file && $image_file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Erreur lors du téléchargement de l'image (code: {$image_file['error']}).";
                 error_log("Erreur de téléchargement de fichier : Code " . $image_file['error']);
            }

            // --- Mise à Jour de la Base de Données ---
            if (empty($errors)) {
                try {
                    $db = Database::getInstance();
                    // Récupérer les anciennes données de l'événement pour obtenir l'ancien chemin d'image si nécessaire pour la suppression
                    $oldEvent = $db->getEventById($eventId);
                    if (!$oldEvent) {
                         throw new Exception("L'événement à mettre à jour (ID: {$eventId}) n'a pas été trouvé.");
                    }
                    $oldImagePath = ($imageChanged && !empty($oldEvent['image_path'])) ? $oldEvent['image_path'] : null;

                    // Appeler la méthode updateEvent
                    $updateSuccess = $db->updateEvent(
                        $eventId,
                        $title,
                        $description,
                        $event_date_db,
                        $location,
                        $price,
                        $points,
                        $image_path_db, // Passer le nouveau nom de fichier (ou null si pas de nouvelle image)
                        $imageChanged   // Passer l'indicateur indiquant si le chemin de l'image doit être mis à jour
                    );

                    if ($updateSuccess) {
                        // Supprimer l'ancienne image si une nouvelle a été téléchargée avec succès et la DB mise à jour
                        if ($oldImagePath) {
                            $fullOldPath = $upload_dir . $oldImagePath;
                            if (file_exists($fullOldPath)) {
                                if (!unlink($fullOldPath)) {
                                     error_log("Impossible de supprimer l'ancien fichier image de l'événement : " . $fullOldPath);
                                }
                            }
                        }
                        $_SESSION['message'] = "Événement '" . htmlspecialchars($title) . "' mis à jour avec succès.";
                        $_SESSION['message_type'] = 'success';
                        header('Location: /admin/events');
                        exit;
                    } else {
                        $errors[] = "Erreur lors de la mise à jour de l'événement dans la base de données.";
                    }
                } catch (Exception $e) {
                    error_log("Erreur lors de la mise à jour de l'événement ID {$eventId}: " . $e->getMessage());
                    $errors[] = "Une erreur technique est survenue lors de la mise à jour.";
                }
            }

            // --- Gestion des Erreurs : Re-rendre le formulaire ---
            if (!empty($errors)) {
                // Stocker les erreurs dans les messages flash de session pour affichage après redirection
                $_SESSION['message'] = implode('<br>', $errors);
                $_SESSION['message_type'] = 'error';
                // Stocker les données soumises du formulaire en session pour remplir à nouveau le formulaire
                $_SESSION['form_data'] = $form_data;

                // Rediriger vers la page d'édition GET
                header('Location: /admin/events/edit?id=' . $eventId);
                exit;

            }
        } else {
             http_response_code(405); echo "Méthode non autorisée pour /admin/events/edit."; exit;
        }
        break;

    case '/admin/events/create':
        $matched = true;
        if ($method === 'GET') {
            $layout_vars['page_title'] = 'Créer un Événement';
            $layout_vars['form_action'] = '/admin/events/store';
            $layout_vars['event'] = null;
            $page_content = TEMPLATE_PATH . 'admin/event_form.php';
        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break;

    case '/admin/events/store':
        $matched = true;
        if ($method === 'POST') {

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $event_date_input = trim($_POST['event_date'] ?? '');
            $location = trim($_POST['location'] ?? ''); // Obtenir le lieu
            $price_input = $_POST['price'] ?? '0';
            $points_input = $_POST['points_awarded'] ?? '0';
            $image_file = $_FILES['image'] ?? null;

            $errors = [];
            $form_data = $_POST;


// Vérifier un problème potentiel de téléchargement de fichier volumineux (POST vide mais FILES présent)
            if (empty($_POST) && !empty($_FILES)) { // Vérifier si N'IMPORTE QUEL fichier a été tenté lorsque POST est vide
                $_SESSION['flash_error'] = 'Échec de la création de l\'événement. Le fichier téléchargé est peut-être trop volumineux (limite ~8Mo). Veuillez essayer un fichier plus petit ou contacter un administrateur.';
                // Rediriger vers le formulaire
                header('Location: /admin/events/create');
                exit;
            }
            if (empty($title)) $errors[] = "Le titre est requis.";
            if (empty($event_date_input)) {
                $errors[] = "La date et l'heure de l'événement sont requises.";
            } else {

                $event_date_obj = DateTime::createFromFormat('Y-m-d\TH:i', $event_date_input);
                if (!$event_date_obj) {
                    $errors[] = "Format de date invalide. Utilisez le sélecteur.";
                    $event_date_db = null;
                } else {

                    $event_date_db = $event_date_obj->format('Y-m-d H:i:s');
                }
            }

            if (!is_numeric($price_input) || floatval($price_input) < 0) {
                $errors[] = "Le prix doit être un nombre positif.";
                $price = 0.0;
            } else {
                $price = floatval($price_input);
            }
             if (!is_numeric($points_input) || intval($points_input) < 0) {
                $errors[] = "Les points attribués doivent être un nombre entier positif.";
                $points = 0;
            } else {
                $points = intval($points_input);
            }


            $image_path_db = null;
            $upload_dir = __DIR__ . '/uploads/events/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024;

            if ($image_file && $image_file['error'] === UPLOAD_ERR_OK) {

                if (!in_array($image_file['type'], $allowed_types)) {
                    $errors[] = "Type de fichier image non autorisé (JPEG, PNG, GIF uniquement).";
                }

                elseif ($image_file['size'] > $max_size) {
                    $errors[] = "L'image est trop volumineuse (max 2MB).";
                } else {

                    $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid('event_', true) . '.' . strtolower($file_extension);
                    $target_path = $upload_dir . $unique_filename;


                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0775, true)) {
                             $errors[] = "Impossible de créer le dossier de destination pour les images.";
                             error_log("Échec de la création du répertoire de téléchargement : " . $upload_dir);
                        }
                    }


                    if (is_dir($upload_dir) && empty($errors)) {
                        if (move_uploaded_file($image_file['tmp_name'], $target_path)) {
                            $image_path_db = $unique_filename;
                        } else {
                            $errors[] = "Erreur lors du téléchargement de l'image.";
                            error_log("Échec du déplacement du fichier téléchargé vers : " . $target_path);
                        }
                    }
                }
            } elseif ($image_file && $image_file['error'] !== UPLOAD_ERR_NO_FILE) {

                $errors[] = "Erreur lors du téléchargement de l'image (code: {$image_file['error']}).";
                 error_log("Erreur de téléchargement de fichier : Code " . $image_file['error']);
            }


            if (empty($errors)) {
                try {
                    $db = Database::getInstance();
                    // Passer le lieu à insertEvent
                    $newEventId = $db->insertEvent($title, $description, $event_date_db, $location, $price, $points, $image_path_db);

                    if ($newEventId) {

                        $_SESSION['message'] = "Événement '" . htmlspecialchars($title) . "' créé avec succès.";
                        $_SESSION['message_type'] = 'success';
                        header('Location: /admin/events');
                        exit;
                    } else {
                        $errors[] = "Erreur lors de l'enregistrement de l'événement dans la base de données.";
                    }
                } catch (Exception $e) {
                    error_log("Erreur lors du stockage de l'événement : " . $e->getMessage());
                    $errors[] = "Une erreur technique est survenue lors de la création de l'événement.";
                }
            }


            if (!empty($errors)) {
                $layout_vars['error_message'] = implode('<br>', $errors);
                $layout_vars['form_data'] = $form_data;
                $layout_vars['page_title'] = 'Créer un Événement';
                $layout_vars['form_action'] = '/admin/events/store';
                $layout_vars['event'] = null;
                $page_content = TEMPLATE_PATH . 'admin/event_form.php';

            }

        } else {
            http_response_code(405); echo "Méthode non autorisée."; exit;
        }
        break;


    // --- Pages statiques ---
    case '/contact':
        // Redirection vers la FAQ
        header('Location: /faq');
        exit;

    case '/faq':
        $matched = true;
        if ($method === 'GET') {
            $layout_vars['page_title'] = 'Foire Aux Questions';
            $page_content = TEMPLATE_PATH . 'faq.php';
        } else {
            http_response_code(405);
            echo "Méthode non autorisée.";
            exit;
        }
        break;

    case '/mentions-legales':
        $matched = true;
        if ($method === 'GET') {
            $layout_vars['page_title'] = 'Mentions Légales';
            $page_content = TEMPLATE_PATH . 'mentions-legales.php';
        } else {
            http_response_code(405);
            echo "Méthode non autorisée.";
            exit;
        }
        break;

    // --- Logique de Détail et de Suppression d'Événement Déplacée vers des Cas Spécifiques ---

        // --- Par Défaut : 404 Non Trouvé ---
        default:
            // Ce cas n'est maintenant atteint que si aucune route paramétrée ou statique n'a correspondu ci-dessus
            $matched = true; // Marquer comme géré pour éviter de tomber à travers
            http_response_code(404);
            $page_content = TEMPLATE_PATH . '404.php'; // Optionnel : Créer un template 404
            $layout_vars['title'] = "Page non trouvée";
            break;
    }
}

// --- Event cancellation route ---
if (!$matched && preg_match('#^/events/(\d+)/cancel$#', $routePath, $matches)) {
    $matched = true;
    $eventId = $matches[1];
    
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Vous devez être connecté pour annuler votre inscription."];
        header('Location: /login?redirect=/events/' . $eventId);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $db = Database::getInstance();
    
    try {
        // Vérifier si l'événement existe
        $event = $db->getEventById($eventId);
        if (!$event) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "L'événement demandé n'existe pas."];
            header('Location: /events');
            exit;
        }
        
        // Vérifier si l'événement n'est pas déjà passé
        if (strtotime($event['event_date']) < time()) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Vous ne pouvez pas annuler votre inscription à un événement passé."];
            header('Location: /events/' . $eventId);
            exit;
        }

        // Annuler l'inscription
        $db->cancelEventRegistration($userId, $eventId);

        // Envoyer email de confirmation d'annulation
        $user = $db->getUserById($userId);
        if ($user) {
            EmailService::sendCancellationConfirmation(
                $user['email'],
                $user['first_name'],
                $event['title']
            );
        }

        $_SESSION['message'] = ['type' => 'success', 'text' => "Votre inscription a été annulée avec succès."];
        header('Location: /account');
    } catch (Exception $e) {
        error_log("Erreur lors de l'annulation de l'inscription: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Une erreur est survenue lors de l'annulation de votre inscription."];
        header('Location: /events/' . $eventId);
    }
    exit;
}

// --- Vérification Finale : Si aucune route n'a été trouvée du tout ---
if (!$matched) {
    // Ceci ne devrait techniquement pas être atteint si le cas par défaut fonctionne, mais agit comme une sauvegarde
    http_response_code(404);
     $page_content = TEMPLATE_PATH . '404.php';
     $layout_vars['title'] = "Page non trouvée";
}

// Charger le layout principal et passer les variables
// Le layout inclura $page_content
require TEMPLATE_PATH . 'layout.php';

?>