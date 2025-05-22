<?php


$pageTitle = "Paiement Annulé";
ob_start();
?>

<h1><?= htmlspecialchars($pageTitle) ?></h1>

<div class="alert alert-warning" role="alert">
    Le processus de paiement a été annulé. Votre achat ou inscription n'a pas été finalisé(e).
</div>

<p>Aucun débit ne sera effectué.</p>

<?php if (!empty($event_id)): ?>
    <p><a href="/event/<?= htmlspecialchars($event_id) ?>" class="btn btn-secondary">Retour à l'événement</a></p>
<?php else: ?>
    <p><a href="/events" class="btn btn-secondary">Retour à l'agenda</a></p>
<?php endif; ?>
<p><a href="/home" class="btn btn-primary">Retour à l'accueil</a></p>


<?php
$content = ob_get_clean();
require 'layout.php';
?>