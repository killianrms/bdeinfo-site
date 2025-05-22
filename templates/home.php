<?php $title = 'Accueil - BDE Info'; ?>

<div class="hero-section">
    <div class="hero-text">
        <h1>APII - BDE Informatique 2025-2026</h1>
    </div>
    <div class="hero-image">

    </div>
</div>
<?php
$esc = 'htmlspecialchars'; // S'assurer que la fonction d'échappement est disponible si elle n'est pas déjà définie globalement dans le layout
$upcomingEvents = $layout_vars['upcoming_events'] ?? [];
?>

<section class="upcoming-events py-5">
    <div class="container">
        <h2 class="text-center mb-4">Événements à Venir</h2>

        <?php if (!empty($layout_vars['error_message'])): ?>
            <div class="alert alert-warning" role="alert">
                <?= $esc($layout_vars['error_message']) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($upcomingEvents)): ?>
            <p class="text-center">Aucun événement à venir pour le moment.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm event-card">
                            <?php if (!empty($event['image_path'])): ?>
                                <img src="/uploads/events/<?= $esc($event['image_path']) ?>" class="card-img-top" alt="<?= $esc($event['title']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/images/placeholder-event.png" class="card-img-top" alt="Image par défaut" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= $esc($event['title']) ?></h5>
                                <p class="card-text mb-2">
                                    <i class="bi bi-calendar-event"></i> <?= $esc(date('d/m/Y H:i', strtotime($event['event_date']))) ?>
                                </p>
                                <?php if (!empty($event['location'])): ?>
                                <p class="card-text mb-2">
                                    <i class="bi bi-geo-alt-fill"></i> <?= $esc($event['location']) ?>
                                </p>
                                <?php endif; ?>
                                <p class="card-text mb-3">
                                    <i class="bi bi-tag-fill"></i> <?= $event['price'] > 0 ? $esc(number_format($event['price'], 2, ',', ' ')) . ' €' : 'Gratuit' ?>
                                </p>
                                <div class="mt-auto">
                                    <a href="/events/<?= $esc($event['id']) ?>" class="btn btn-primary w-100">Voir Détails</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

