<?php
$esc = 'htmlspecialchars';
$event = $layout_vars['event'] ?? null;
$registrations = $layout_vars['registrations'] ?? [];
?>

<h2><?= $esc($layout_vars['page_title'] ?? 'Inscriptions à l\'événement') ?></h2>

<?php if ($event): ?>
    <h3>Événement : <?= $esc($event['title']) ?></h3>
<?php endif; ?>

<?php if (isset($layout_vars['message'])): ?>
    <div class="alert alert-<?= $esc($layout_vars['message_type'] ?? 'info') ?>" role="alert">
        <?= $esc($layout_vars['message']) ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registrations)): ?>
                <tr>
                    <td colspan="3" class="text-center">Aucun utilisateur inscrit pour cet événement.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registrations as $reg): ?>
                    <tr>
                        <td><?= $esc($reg['first_name'] ?? 'N/A') ?></td>
                        <td><?= $esc($reg['last_name'] ?? 'N/A') ?></td>
                        <td><?= $esc($reg['email'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3">
    <a href="/admin/events" class="btn btn-secondary">Retour à la liste des événements</a>
</div>