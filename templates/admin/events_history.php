<?php
$esc = 'htmlspecialchars';
?>

<div class="admin-events-history-container"> <!-- Conteneur spécifique -->

<h2><?= $esc($layout_vars['page_title'] ?? 'Historique des Événements Clôturés') ?></h2>

<?php if (isset($layout_vars['message'])): ?>
    <div class="alert alert-<?= $esc($layout_vars['message_type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= $esc($layout_vars['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover admin-events-table"> <!-- Classe réutilisée -->
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Date de l'événement</th>
                <th>Lieu</th>
                <th>Prix (€)</th>
                <th>Points</th>
                <th>Date de clôture</th> <!-- Adapté -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($layout_vars['closed_events'])): // Variable adaptée ?>
                <tr>
                    <td colspan="8" class="text-center">Aucun événement clôturé trouvé.</td> <!-- Colspan adapté -->
                </tr>
            <?php else: ?>
                <?php foreach ($layout_vars['closed_events'] as $event): // Variable adaptée ?>
                    <tr>
                        <td data-label="ID"><?= $esc($event['id']) ?></td>
                        <td data-label="Titre"><?= $esc($event['title']) ?></td>
                        <td data-label="Date"><?= $esc(date('d/m/Y H:i', strtotime($event['event_date']))) ?></td>
                        <td data-label="Lieu"><?= $esc($event['location'] ?? 'N/A') ?></td>
                        <td data-label="Prix (€)"><?= $esc(number_format($event['price'], 2, ',', ' ')) ?></td>
                        <td data-label="Points"><?= $esc($event['points_awarded']) ?></td>
                        <td data-label="Date de clôture"><?= $esc(date('d/m/Y H:i', strtotime($event['updated_at']))) ?></td> <!-- Utilise updated_at comme date de clôture/modif -->
                        <td data-label="Actions" class="admin-event-actions">
                            <!-- Actions : Rouvrir, Archiver -->
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form action="/admin/events/reopen/<?= $esc($event['id']) ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir rouvrir cet événement ? Il sera de nouveau visible publiquement.');" class="d-inline">
                                    <button type="submit" class="btn btn-sm btn-success" title="Rouvrir l'événement">Rouvrir</button>
                                </form>
                                <form action="/admin/events/delete/<?= $esc($event['id']) ?>" method="POST" onsubmit="return confirm('ATTENTION : Êtes-vous sûr de vouloir supprimer définitivement cet événement ? Cette action est irréversible et ne permet aucune récupération.');" class="d-inline">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer définitivement l'événement">Supprimer Définitivement</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div> <!-- Fermeture du conteneur -->