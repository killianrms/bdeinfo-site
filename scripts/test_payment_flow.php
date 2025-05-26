<?php
/**
 * Script de test pour vérifier le flux de paiement des événements
 * 
 * Ce script simule le processus de paiement pour vérifier que tout fonctionne correctement
 */

require_once __DIR__ . '/../src/Database.php';

echo "=== Test du flux de paiement des événements ===\n\n";

try {
    $db = Database::getInstance();
    
    // 1. Vérifier qu'on a des événements payants
    echo "1. Recherche d'événements payants...\n";
    $events = $db->getAllUpcomingEvents();
    $paidEvents = array_filter($events, function($event) {
        return (float)$event['price'] > 0;
    });
    
    if (empty($paidEvents)) {
        echo "   ⚠️ Aucun événement payant trouvé. Création d'un événement de test...\n";
        $eventId = $db->createEvent([
            'title' => 'Événement Test Payant',
            'description' => 'Ceci est un événement de test pour vérifier le système de paiement',
            'event_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'price' => 10.00,
            'location' => 'Salle de test',
            'points_awarded' => 50
        ]);
        echo "   ✅ Événement de test créé (ID: $eventId)\n";
    } else {
        $event = reset($paidEvents);
        $eventId = $event['id'];
        echo "   ✅ Événement payant trouvé: " . $event['title'] . " (" . $event['price'] . "€)\n";
    }
    
    // 2. Vérifier qu'on a un utilisateur de test
    echo "\n2. Recherche d'un utilisateur de test...\n";
    $testEmail = 'test@example.com';
    $user = $db->getUserByEmail($testEmail);
    
    if (!$user) {
        echo "   ⚠️ Utilisateur de test non trouvé. Utilisez l'interface pour créer un compte de test.\n";
    } else {
        $userId = $user['id'];
        echo "   ✅ Utilisateur de test trouvé: " . $user['first_name'] . " " . $user['last_name'] . "\n";
        
        // 3. Vérifier l'état d'inscription actuel
        echo "\n3. Vérification de l'inscription actuelle...\n";
        $registration = $db->getUserRegistrationForEvent($userId, $eventId);
        
        if ($registration) {
            echo "   ℹ️ L'utilisateur est déjà inscrit avec le statut: " . $registration['payment_status'] . "\n";
            if ($registration['payment_status'] === 'completed') {
                echo "   ✅ Le paiement est déjà validé!\n";
            } else {
                echo "   ⏳ Le paiement est en attente ou a échoué\n";
            }
        } else {
            echo "   ℹ️ L'utilisateur n'est pas encore inscrit à cet événement\n";
        }
        
        // 4. Tester l'ajout d'une inscription en attente
        echo "\n4. Test d'ajout d'inscription en attente...\n";
        $checkoutRef = 'TEST_EVENT_' . $eventId . '_USER_' . $userId . '_' . time();
        $sumupCheckoutId = 'TEST_SUMUP_' . uniqid();
        
        $success = $db->addPendingEventRegistration($eventId, $userId, $checkoutRef, $sumupCheckoutId);
        if ($success) {
            echo "   ✅ Inscription en attente créée avec succès\n";
            
            // 5. Simuler la validation du paiement
            echo "\n5. Simulation de la validation du paiement...\n";
            $updateSuccess = $db->updateRegistrationPaymentStatus($checkoutRef, 'completed', 'TEST_TRANSACTION_' . uniqid());
            
            if ($updateSuccess) {
                echo "   ✅ Paiement validé avec succès!\n";
                
                // Vérifier le résultat
                $finalRegistration = $db->getUserRegistrationForEvent($userId, $eventId);
                if ($finalRegistration && $finalRegistration['payment_status'] === 'completed') {
                    echo "   ✅ L'utilisateur est maintenant inscrit et confirmé pour l'événement!\n";
                }
            } else {
                echo "   ❌ Erreur lors de la validation du paiement\n";
            }
        } else {
            echo "   ❌ Erreur lors de la création de l'inscription\n";
        }
    }
    
    // 6. Afficher les statistiques
    echo "\n6. Statistiques des inscriptions...\n";
    if (isset($eventId)) {
        $registrations = $db->getRegistrationsForEvent($eventId);
        $completed = array_filter($registrations, function($r) { return $r['payment_status'] === 'completed'; });
        $pending = array_filter($registrations, function($r) { return $r['payment_status'] === 'pending'; });
        
        echo "   📊 Total des inscriptions: " . count($registrations) . "\n";
        echo "   ✅ Confirmées: " . count($completed) . "\n";
        echo "   ⏳ En attente: " . count($pending) . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== Fin du test ===\n";