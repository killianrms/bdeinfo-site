<?php
$esc = 'htmlspecialchars';
$event = $layout_vars['event'] ?? null;
$registrations = $layout_vars['registrations'] ?? [];
?>

<h2><?= $esc($layout_vars['page_title'] ?? 'Inscriptions à l\'événement') ?></h2>

<?php if ($event): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Événement : <?= $esc($event['title']) ?></h3>
        <a href="/admin/events/<?= $event['id'] ?>/export-csv" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exporter en CSV
        </a>
    </div>
<?php endif; ?>

<?php if (isset($layout_vars['message'])): ?>
    <div class="alert alert-<?= $esc($layout_vars['message_type'] ?? 'info') ?>" role="alert">
        <?= $esc($layout_vars['message']) ?>
    </div>
<?php endif; ?>

<?php
// Séparer les inscriptions par statut
$completed_registrations = array_filter($registrations, function($reg) {
    return ($reg['payment_status'] ?? '') === 'completed';
});
$pending_registrations = array_filter($registrations, function($reg) {
    return ($reg['payment_status'] ?? '') === 'pending';
});
$failed_registrations = array_filter($registrations, function($reg) {
    return in_array($reg['payment_status'] ?? '', ['failed', 'cancelled']);
});

$total_count = count($registrations);
$completed_count = count($completed_registrations);
$pending_count = count($pending_registrations);
$failed_count = count($failed_registrations);
?>

<div class="mb-3">
    <div class="row">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <p class="card-text display-4"><?= $total_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-success">
                <div class="card-body">
                    <h5 class="card-title">Confirmés</h5>
                    <p class="card-text display-4"><?= $completed_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-warning">
                <div class="card-body">
                    <h5 class="card-title">En attente</h5>
                    <p class="card-text display-4"><?= $pending_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-danger">
                <div class="card-body">
                    <h5 class="card-title">Échoués</h5>
                    <p class="card-text display-4"><?= $failed_count ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<h3>Participants confirmés</h3>
<div class="table-responsive mb-4">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Date d'inscription</th>
                <th>ID Transaction</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($completed_registrations)): ?>
                <tr>
                    <td colspan="5" class="text-center">Aucun participant confirmé pour cet événement.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($completed_registrations as $reg): ?>
                    <tr>
                        <td><?= $esc($reg['first_name'] ?? 'N/A') ?></td>
                        <td><?= $esc($reg['last_name'] ?? 'N/A') ?></td>
                        <td><?= $esc($reg['email'] ?? 'N/A') ?></td>
                        <td><?= $esc(date('d/m/Y H:i', strtotime($reg['registration_date']))) ?></td>
                        <td>
                            <?php if (!empty($reg['transaction_id'])): ?>
                                <small><?= $esc(substr($reg['transaction_id'], 0, 8)) ?>...</small>
                            <?php else: ?>
                                <span class="text-muted">Gratuit</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($pending_registrations) || !empty($failed_registrations)): ?>
<h3>Autres inscriptions</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Date</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_merge($pending_registrations, $failed_registrations) as $reg): ?>
                <tr>
                    <td><?= $esc($reg['first_name'] ?? 'N/A') ?></td>
                    <td><?= $esc($reg['last_name'] ?? 'N/A') ?></td>
                    <td><?= $esc($reg['email'] ?? 'N/A') ?></td>
                    <td><?= $esc(date('d/m/Y H:i', strtotime($reg['registration_date']))) ?></td>
                    <td>
                        <?php
                        $status = $reg['payment_status'] ?? 'unknown';
                        $badge_class = 'badge-secondary';
                        $status_text = 'Inconnu';
                        
                        switch($status) {
                            case 'pending':
                                $badge_class = 'badge-warning';
                                $status_text = 'En attente';
                                break;
                            case 'failed':
                                $badge_class = 'badge-danger';
                                $status_text = 'Échoué';
                                break;
                            case 'cancelled':
                                $badge_class = 'badge-danger';
                                $status_text = 'Annulé';
                                break;
                        }
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= $esc($status_text) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="mt-3">
    <a href="/admin/events" class="btn btn-secondary">Retour à la liste des événements</a>
</div>