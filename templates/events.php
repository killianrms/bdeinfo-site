<?php
// Les événements sont maintenant passés via $layout_vars['events'] depuis le contrôleur
$events = isset($events) ? $events : [];
?>

<div class="container">
    <h1 class="page-title">Agenda des Événements</h1>

    <div class="events-header">
        <p>Découvrez tous nos événements à venir et inscrivez-vous dès maintenant !</p>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="search-filters" style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <form method="GET" action="/events" id="filter-form">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <!-- Recherche -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">🔍 Rechercher</label>
                    <input type="text" name="search"
                           placeholder="Nom de l'événement..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <!-- Filtre Prix -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">💰 Prix</label>
                    <select name="price" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">Tous</option>
                        <option value="free" <?= ($_GET['price'] ?? '') === 'free' ? 'selected' : '' ?>>Gratuit</option>
                        <option value="paid" <?= ($_GET['price'] ?? '') === 'paid' ? 'selected' : '' ?>>Payant</option>
                    </select>
                </div>

                <!-- Filtre Date -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">📅 Période</label>
                    <select name="date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">Toutes</option>
                        <option value="week" <?= ($_GET['date'] ?? '') === 'week' ? 'selected' : '' ?>>Cette semaine</option>
                        <option value="month" <?= ($_GET['date'] ?? '') === 'month' ? 'selected' : '' ?>>Ce mois</option>
                        <option value="later" <?= ($_GET['date'] ?? '') === 'later' ? 'selected' : '' ?>>Plus tard</option>
                    </select>
                </div>

                <!-- Boutons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Filtrer
                    </button>
                    <a href="/events" style="padding: 10px 20px; background: #f0f0f0; color: #333; border: none; border-radius: 5px; text-decoration: none; display: inline-block;">
                        Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($events)): ?>
        <div class="no-events">
            <p>Aucun événement à venir pour le moment.</p>
            <p>Revenez bientôt pour découvrir notre programme !</p>
        </div>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-image">
                        <?php
                            $imageUrl = '/images/events/placeholder-default.jpg';
                            if (!empty($event['image_path']) && $event['image_path'] !== 'null') {
                                $imageUrl = '/images/events/' . htmlspecialchars($event['image_path']);
                            }
                        ?>
                        <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    </div>
                    <div class="event-content">
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        
                        <?php
                            try {
                                $date = new DateTime($event['event_date']);
                                $formattedDate = $date->format('d/m/Y à H:i');
                            } catch (Exception $e) {
                                error_log("Error formatting date for event ID {$event['id']}: " . $e->getMessage());
                                $formattedDate = 'Date à confirmer';
                            }
                        ?>
                        
                        <div class="event-details">
                            <p><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($formattedDate) ?></p>
                            <p><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($event['location'] ?? 'Lieu à confirmer') ?></p>
                            <p><i class="bi bi-cash"></i> 
                                <?php if ((float)$event['price'] <= 0): ?>
                                    <span class="free-event">Gratuit</span>
                                <?php else: ?>
                                    <?= htmlspecialchars(number_format((float)$event['price'], 2, ',', ' ')) ?> €
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if (!empty($event['description'])): ?>
                            <div class="event-description">
                                <p><?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-actions">
                            <a href="/events/<?= (int)$event['id'] ?>" class="btn btn-primary">Voir les détails</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-title {
    text-align: center;
    margin-bottom: 30px;
    color: var(--primary-color);
}

.events-header {
    text-align: center;
    margin-bottom: 40px;
}

.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.event-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background-color: #fff;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.event-image {
    height: 180px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.event-content {
    padding: 20px;
}

.event-title {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.3rem;
    color: var(--primary-color);
}

.event-details {
    margin-bottom: 15px;
}

.event-details p {
    margin: 8px 0;
    display: flex;
    align-items: center;
}

.event-details i {
    margin-right: 10px;
    color: var(--primary-color);
}

.event-description {
    margin-bottom: 20px;
    color: #666;
    font-size: 0.9rem;
}

.event-actions {
    display: flex;
    justify-content: center;
}

.btn {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.free-event {
    color: #28a745;
    font-weight: bold;
}

.no-events {
    text-align: center;
    padding: 50px 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 40px;
}

.no-events p:first-child {
    font-size: 1.2rem;
    margin-bottom: 10px;
    color: #555;
}

.no-events p:last-child {
    color: #888;
}

@media (max-width: 768px) {
    .event-grid {
        grid-template-columns: 1fr;
    }
}
</style>