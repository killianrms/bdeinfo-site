<?php


$pageTitle = "Achat d'Adhésion Annulé";



?>

<h1><?= htmlspecialchars($pageTitle) ?></h1>

<div class="alert alert-warning" role="alert">
    Votre processus d'achat d'adhésion a été annulé.
</div>

<p>Vous n'avez pas été débité.</p>

<p>Si vous avez annulé par erreur, vous pouvez retourner à la page des adhésions et réessayer.</p>

<p>
    <a href="/memberships" class="btn btn-primary">Voir les Adhésions</a>
    <a href="/home" class="btn btn-secondary">Retour à l'Accueil</a>
</p>