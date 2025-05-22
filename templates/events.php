<?php

$title = "Agenda des Événements";


$events = [
    [
        'id' => 1,
        'title' => 'Soirée d\'Intégration 2025',
        'event_date' => '2025-09-15 19:00:00',
        'image_path' => 'placeholder-event1.jpg',
        'price' => 5.00
    ],
    [
        'id' => 2,
        'title' => 'Tournoi de Jeux Vidéo Smash Bros',
        'event_date' => '2025-10-22 14:00:00',
        'image_path' => 'placeholder-event2.jpg',
        'price' => 2.00
    ],
    [
        'id' => 3,
        'title' => 'Conférence Tech : IA & Avenir',
        'event_date' => '2025-11-10 18:30:00',
        'image_path' => 'placeholder-event3.jpg',
        'price' => 0.00
    ],
     [
        'id' => 4,
        'title' => 'Week-end Ski BDE',
        'event_date' => '2026-01-25 08:00:00',
        'image_path' => 'placeholder-event4.jpg',
        'price' => 150.00
    ],
];


?>

<h1><?= htmlspecialchars($title) ?></h1>

<?php if (empty($events)): ?>
    <p>Aucun événement à venir pour le moment.</p>
<?php else: ?>
    <div class="event-list">
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($event['title']) ?></h3>
                <?php

                    try {
                        $date = new DateTime($event['event_date']);

                        if (class_exists('IntlDateFormatter')) {

                            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::SHORT, 'Europe/Paris');
                            $formattedDate = $formatter->format($date);
                        } else {

                            $formattedDate = $date->format('d/m/Y à H:i');
                        }
                    } catch (Exception $e) {
                        error_log("Error formatting date for event ID {$event['id']}: " . $e->getMessage());
                        $formattedDate = 'Date invalide';
                    }
                ?>
                <p><strong>Date :</strong> <?= htmlspecialchars($formattedDate) ?></p>
                <p><strong>Prix :</strong> <?= htmlspecialchars(number_format($event['price'], 2, ',', ' ')) ?> €</p>
                <?php

                    $imageUrl = '/images/events/placeholder-default.jpg';
                    if (!empty($event['image_path'])) {

                        $imageUrl = '/images/events/' . htmlspecialchars($event['image_path']);

                    }
                ?>
                <img src="<?= $imageUrl ?>" alt="Image pour <?= htmlspecialchars($event['title']) ?>">
                 <p class="text-center">
                    <a href="/event/<?= htmlspecialchars($event['id']) ?>" class="button">Voir les détails</a>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php


?>