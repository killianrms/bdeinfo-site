<?php


$pageTitle = "Paiement Réussi"; // Titre plus spécifique
ob_start();
?>

<h1><?= htmlspecialchars($pageTitle) ?></h1>

<div class="alert alert-success" role="alert">
    Votre paiement a été traité avec succès !
</div>

<p>Merci pour votre inscription ! Vous devriez recevoir une confirmation par email sous peu.</p>
<p>Vous pouvez consulter vos inscriptions sur la page <a href="/account">Mon Compte</a>.</p>

<?php if (!empty($event_id)): // Utiliser l'event_id passé depuis le callback ?>
    <p><a href="/events/<?= htmlspecialchars($event_id) ?>" class="button">Retourner à l'événement</a></p>
<?php else: ?>
    <p><a href="/events" class="button">Voir les événements</a></p>
<?php endif; ?>

<p><a href="/home" class="button secondary">Retour à l'accueil</a></p>


<?php
$content = ob_get_clean();
// En supposant que $layout_vars['event_id'] est défini dans index.php avant d'inclure ce template
// Le require 'layout.php' devrait idéalement utiliser $layout_vars passé depuis index.php
require 'layout.php';
?>