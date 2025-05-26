<?php




if (empty($event)) {
    $title = "Événement non trouvé";
?>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p>Désolé, l'événement que vous recherchez n'a pas pu être trouvé.</p>
    <p><a href="/events" class="button">Retour à l'agenda</a></p>
<?php
    return;
}

$title = "Détails : " . htmlspecialchars($event['title']);
$isUserLoggedIn = isset($_SESSION['user_id']);


$display_price = (float)$event['price'];
$price_to_pay = $display_price;
$is_discounted = ($discounted_price !== null && $discounted_price < $display_price);

if ($is_discounted) {
    $price_to_pay = (float)$discounted_price;
}

?>

<h1><?= htmlspecialchars($event['title']) ?></h1>

<div class="event-details-container">
    <div class="event-image-column">
<?php

    $imageUrl = '/images/events/placeholder-default-large.jpg';
    if (!empty($event['image_path'])) {


        $imageFilePath = '/uploads/events/' . basename($event['image_path']);


         $imageUrl = htmlspecialchars($imageFilePath);

    } else {

         $imageUrl = '/images/events/placeholder-default-large.jpg';
    }
?>
<img src="<?= $imageUrl ?>" alt="Image pour <?= htmlspecialchars($event['title']) ?>" class="event-image-large">
    </div>

    <div class="event-info-column">

<?php

    try {
        $date = new DateTime($event['event_date']);
        if (class_exists('IntlDateFormatter')) {


            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::SHORT, 'Europe/Paris', IntlDateFormatter::GREGORIAN);
             if (!$formatter) {
                 throw new Exception("IntlDateFormatter could not be created for fr_FR.");
             }
            $formattedDate = $formatter->format($date);
        } else {

            $dayOfWeek = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $month = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            $formattedDate = $dayOfWeek[$date->format('w')] . ' ' . $date->format('j') . ' ' . $month[$date->format('n')-1] . ' ' . $date->format('Y \à H:i');
        }
    } catch (Exception $e) {
        error_log("Error formatting date for event ID {$event['id']}: " . $e->getMessage());
        $formattedDate = date('d/m/Y H:i', strtotime($event['event_date'])) . ' (Formatage avancé échoué)';
    }
?>
<p><strong>Date et Heure :</strong> <?= htmlspecialchars($formattedDate) ?></p>

<?php if (!empty($event['location'])): ?>
<p><strong>Lieu :</strong> <?= htmlspecialchars($event['location']) ?></p>
<?php endif; ?>
<div class="price-section">
    <?php if ($is_discounted): ?>
        <p><strong>Prix original :</strong> <span style="text-decoration: line-through;"><?= htmlspecialchars(number_format($display_price, 2, ',', ' ')) ?> €</span></p>
        <p><strong>Prix membre (<?= htmlspecialchars(number_format($discount_percentage, 0)) ?>%) :</strong> <span style="font-weight: bold; color: var(--primary-color);"><?= htmlspecialchars(number_format($price_to_pay, 2, ',', ' ')) ?> €</span></p>
    <?php else: ?>
        <p><strong>Prix :</strong> <?= htmlspecialchars(number_format($display_price, 2, ',', ' ')) ?> €</p>
        <?php if ($isUserLoggedIn && $discount_percentage > 0): ?>
             <p><em style="font-size: 0.9em;">(Vous bénéficiez d'une réduction de <?= htmlspecialchars(number_format($discount_percentage, 0)) ?>% sur cet événement !)</em></p>
        <?php elseif ($isUserLoggedIn && $discount_percentage == 0): ?>
             <p><em style="font-size: 0.9em;">(Votre adhésion actuelle n'inclut pas de réduction pour cet événement.)</em></p>
         <?php elseif (!$isUserLoggedIn): ?>
             <p><em style="font-size: 0.9em;">(Les membres Premium bénéficient de réductions sur les événements !)</em></p>
        <?php endif; ?>
    <?php endif; ?>
</div>


<h2>Description</h2>
<div class="event-description">
    <?= nl2br(htmlspecialchars($event['description'])) ?>
</div>

<hr>

<?php
// Afficher les messages de session s'ils existent
if (isset($_SESSION['message']) && is_array($_SESSION['message'])) {
    $message_type = isset($_SESSION['message']['type']) ? htmlspecialchars($_SESSION['message']['type']) : 'info'; // Par défaut à info
    $message_text = isset($_SESSION['message']['text']) ? htmlspecialchars($_SESSION['message']['text']) : '';

    // Mapper les types aux classes d'alerte Bootstrap
    $alert_class = 'alert-info'; // Par défaut
    switch ($message_type) {
        case 'success':
            $alert_class = 'alert-success';
            break;
        case 'warning':
            $alert_class = 'alert-warning';
            break;
        case 'danger':
        case 'error': // Gérer aussi le type 'error'
            $alert_class = 'alert-danger';
            break;
        case 'info':
            $alert_class = 'alert-info';
            break;
    }

    if (!empty($message_text)) {
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
        echo $message_text;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'; // Optionnel : Ajouter un bouton de fermeture si Bootstrap 5 JS est utilisé
        echo '</div>';
    }

    unset($_SESSION['message']); // Effacer le message après l'affichage
} elseif (isset($_SESSION['message']) && is_string($_SESSION['message'])) {
    // Solution de repli pour les anciens messages basés sur des chaînes (optionnel, mais bonne pratique)
    $message_text = htmlspecialchars($_SESSION['message']);
    $message_type = isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : 'info'; // Supposer info si le type est manquant

    $alert_class = 'alert-info'; // Par défaut
    switch ($message_type) {
        case 'success':
            $alert_class = 'alert-success';
            break;
        case 'warning':
            $alert_class = 'alert-warning';
            break;
        case 'danger':
        case 'error':
            $alert_class = 'alert-danger';
            break;
    }
     echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
     echo $message_text;
     echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
     echo '</div>';

    unset($_SESSION['message']);
    if (isset($_SESSION['message_type'])) {
        unset($_SESSION['message_type']);
    }
}
?>
<?php

if ($isUserLoggedIn) {
    // Vérifier si l'utilisateur est déjà inscrit
    if (isset($user_registration) && $user_registration) {
        $payment_status = $user_registration['payment_status'] ?? 'pending';
        
        if ($payment_status === 'completed') {
?>
            <div class="alert alert-success">
                <p><strong>✓ Vous êtes inscrit à cet événement !</strong></p>
                <p>Votre inscription a été confirmée et votre paiement a été traité avec succès.</p>
            </div>
<?php
        } elseif ($payment_status === 'pending') {
?>
            <div class="alert alert-warning">
                <p><strong>⏳ Inscription en attente</strong></p>
                <p>Votre paiement est en cours de traitement. Si cela prend trop de temps, veuillez contacter le support.</p>
            </div>
<?php
        } elseif ($payment_status === 'failed' || $payment_status === 'cancelled') {
?>
            <div class="alert alert-danger">
                <p><strong>❌ Paiement échoué</strong></p>
                <p>Votre précédent paiement a échoué. Vous pouvez réessayer ci-dessous.</p>
            </div>
            <?php if ($display_price > 0) { ?>
            <form action="/events/<?= htmlspecialchars($event['id']) ?>/pay" method="POST" class="event-action-form">
                <button type="submit" class="button payment-button">
                    Réessayer le paiement (<?= htmlspecialchars(number_format($price_to_pay, 2, ',', ' ')) ?> €)
                </button>
            </form>
            <?php } ?>
<?php
        }
    } else {
        // Pas encore inscrit - afficher les boutons d'inscription
        if ($display_price > 0) {
?>
        <form action="/events/<?= htmlspecialchars($event['id']) ?>/pay" method="POST" class="event-action-form">
            <button type="submit" class="button payment-button">
                Payer avec SumUp (<?= htmlspecialchars(number_format($price_to_pay, 2, ',', ' ')) ?> €)
            </button>
        </form>
        <style>
            .payment-button {
                display: inline-block;
                text-align: center;
                padding: 10px 20px;
                margin: 10px 0;
                background-color: var(--primary-color);
                color: white;
                border-radius: 4px;
                text-decoration: none;
                border: none;
                font-weight: bold;
                transition: background-color 0.3s ease;
                cursor: pointer;
                width: auto;
            }
            .payment-button:hover {
                background-color: var(--primary-dark);
                text-decoration: none;
                color: white;
            }
        </style>
<?php
        } else { // Inscription à un événement gratuit
?>
        <form action="/events/<?= htmlspecialchars($event['id']) ?>/register" method="POST" class="event-action-form">
            <button type="submit" class="button">S'inscrire (Gratuit)</button>
        </form>
<?php
        }
    }
} else {
?>
    <div class="alert-info">
        <p>Veuillez vous <a href="/login?redirect=/event/<?= htmlspecialchars($event['id']) ?>">connecter</a> ou <a href="/register">créer un compte</a> pour vous inscrire.</p>
    </div>
<?php
}
?>

        <p style="margin-top: 20px;"><a href="/home" class="button secondary">Retour à l'accueil</a></p> <!-- Lien modifié vers /home -->
    </div>
</div>

<?php

?>