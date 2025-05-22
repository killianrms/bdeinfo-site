<?php $title = 'Accueil - BDE Info'; ?>

<div class="hero-section">
    <div class="hero-text">
        <h1>APII - BDE Informatique 2025-2026</h1>
        <p class="hero-subtitle">Bienvenue sur le site officiel du Bureau des Étudiants en Informatique.</p>
        <p class="hero-description">Notre mission est de créer une communauté dynamique et inclusive pour tous les étudiants en informatique. Nous organisons des événements, des ateliers techniques, des conférences et des moments de convivialité tout au long de l'année.</p>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-number">5+</span>
                <span class="hero-stat-label">Événements à venir</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-number">200+</span>
                <span class="hero-stat-label">Membres actifs</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-number">15+</span>
                <span class="hero-stat-label">Partenaires</span>
            </div>
        </div>
        <div class="hero-buttons">
            <a href="/events" class="btn btn-primary"><i class="bi bi-calendar-event"></i> Voir les événements</a>
            <a href="/memberships" class="btn btn-secondary"><i class="bi bi-person-plus"></i> Devenir membre</a>
        </div>
    </div>
    <div class="hero-image">
        <img src="/bde.webp" alt="BDE Informatique" class="hero-img">
    </div>
</div>
<?php
$esc = 'htmlspecialchars'; // S'assurer que la fonction d'échappement est disponible si elle n'est pas déjà définie globalement dans le layout
$upcomingEvents = $layout_vars['upcoming_events'] ?? [];
?>

<section class="events-section">
    <h2 class="section-title">Événements à Venir</h2>

    <?php if (!empty($layout_vars['error_message'])): ?>
        <div class="alert alert-warning" role="alert">
            <?= $esc($layout_vars['error_message']) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($upcomingEvents)): ?>
        <div class="no-events">
            <i class="bi bi-calendar-x"></i>
            <p>Aucun événement à venir pour le moment.</p>
            <p class="sub-text">Revenez bientôt pour découvrir nos prochains événements !</p>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="event-card">
                    <div class="event-date">
                        <span class="day"><?= date('d', strtotime($event['event_date'])) ?></span>
                        <span class="month"><?= date('M', strtotime($event['event_date'])) ?></span>
                    </div>
                    <div class="event-image">
                        <?php if (!empty($event['image_path'])): ?>
                            <img src="/uploads/events/<?= $esc($event['image_path']) ?>" alt="<?= $esc($event['title']) ?>">
                        <?php else: ?>
                            <img src="/images/event-default.jpg" alt="<?= $esc($event['title']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="event-content">
                        <h3 class="event-title"><?= $esc($event['title']) ?></h3>
                        <div class="event-info">
                            <div class="event-info-item event-time">
                                <?= date('H:i', strtotime($event['event_date'])) ?>
                            </div>
                            <?php if (!empty($event['location'])): ?>
                            <div class="event-info-item event-location">
                                <?= $esc($event['location']) ?>
                            </div>
                            <?php endif; ?>
                            <div class="event-info-item event-price">
                                <?php if ($event['price'] > 0): ?>
                                    <?= number_format($event['price'], 2, ',', ' ') ?> €
                                <?php else: ?>
                                    Gratuit
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="event-actions">
                            <a href="/events/<?= $esc($event['id']) ?>" class="btn">
                                Voir Détails
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="view-all-events">
            <a href="/events" class="btn btn-secondary">
                <i class="bi bi-calendar3"></i> Voir tous les événements
            </a>
        </div>
    <?php endif; ?>
</section>

