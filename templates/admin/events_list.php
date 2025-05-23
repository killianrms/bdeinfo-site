<?php


$esc = 'htmlspecialchars';

?>

<div class="admin-events-list-container">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $esc($layout_vars['page_title'] ?? 'Gestion des Événements') ?></h2>
    <div>
        <a href="/admin/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
        </a>
    </div>
</div>

<?php if (isset($layout_vars['message'])): ?>
    <div class="alert alert-<?= $esc($layout_vars['message_type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= $esc($layout_vars['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-light">
        <h3 class="h5 mb-0">Actions</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-2 mb-md-0">
                <a href="/admin/events/create" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle me-2"></i>Créer un nouvel événement
                </a>
            </div>
            <div class="col-md-6">
                <?php if (isset($layout_vars['is_history_view']) && $layout_vars['is_history_view']): ?>
                    <a href="/admin/events" class="btn btn-primary w-100">
                        <i class="bi bi-list-check me-2"></i>Voir les événements actifs
                    </a>
                <?php else: ?>
                    <a href="/admin/events/history" class="btn btn-secondary w-100">
                        <i class="bi bi-clock-history me-2"></i>Voir l'historique des événements
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover admin-events-table">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Date de l'événement</th>
                <th>Lieu</th>
                <th>Prix (€)</th>
                <th>Points</th>
                <th>Créé le</th>
                <th>Modifié le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($layout_vars['events'])): ?>
                <tr>
                    <td colspan="9" class="text-center">Aucun événement trouvé.</td> <!-- Colspan augmenté -->
                </tr>
            <?php else: ?>
                <?php foreach ($layout_vars['events'] as $event): ?>
                    <tr>
                        <td data-label="ID"><?= $esc($event['id']) ?></td>
                        <td data-label="Titre"><?= $esc($event['title']) ?></td>
                        <td data-label="Date"><?= $esc(date('d/m/Y H:i', strtotime($event['event_date']))) ?></td>
                        <td data-label="Lieu"><?= $esc($event['location'] ?? 'N/A') ?></td>
                        <td data-label="Prix (€)"><?= $esc(number_format($event['price'], 2, ',', ' ')) ?></td>
                        <td data-label="Points"><?= $esc($event['points_awarded']) ?></td>
                        <td data-label="Créé le"><?= $esc(date('d/m/Y H:i', strtotime($event['created_at']))) ?></td>
                        <td data-label="Modifié le"><?= $esc(date('d/m/Y H:i', strtotime($event['updated_at']))) ?></td>
                        <td data-label="Actions" class="admin-event-actions">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <?php if (isset($layout_vars['is_history_view']) && $layout_vars['is_history_view']): ?>
                                    <!-- Actions for History View (Closed Events) -->
                                    <form action="/admin/events/reopen/<?= $esc($event['id']) ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir réouvrir cet événement ?');" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-success" title="Réouvrir l'événement">Réouvrir</button>
                                    </form>
                                    <form action="/admin/events/archive/<?= $esc($event['id']) ?>" method="POST" onsubmit="return confirm('ATTENTION : Cette action est irréversible et supprimera définitivement cet événement et son image associée. Êtes-vous sûr ?');" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer définitivement l'événement">Archiver (Supprimer)</button>
                                    </form>
                                <?php else: ?>
                                    <!-- Actions for Main Event List (Open Events) -->
                                    <a href="/admin/events/<?= $esc($event['id']) ?>/registrations" class="btn btn-sm btn-info" title="Voir les inscriptions">Inscriptions</a>
                                    <a href="/admin/events/edit?id=<?= $esc($event['id']) ?>" class="btn btn-sm btn-warning" title="Modifier l'événement">Modifier</a>
                                    <form action="/admin/events/close/<?= $esc($event['id']) ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir clôturer cet événement ? Il sera déplacé vers l\'historique.');" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Clôturer l'événement">Clôturer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div> <!-- Fermeture du conteneur -->