<?php


$pageTitle = "Confirmation d'Adhésion";




?>

<h1><?= htmlspecialchars($pageTitle) ?></h1>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?= htmlspecialchars($success_message) ?>
    </div>
    <p>Merci de soutenir le BDE Info ! Vous pouvez consulter vos adhésions actives sur <a href="/account">votre page de compte</a>.</p>
<?php elseif (!empty($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Erreur :</strong> <?= htmlspecialchars($error_message) ?>
    </div>
    <p>Si le problème persiste ou si vous pensez que votre paiement a été effectué, veuillez <a href="/contact">contacter le BDE Info</a>.</p>
<?php else: ?>
    <div class="alert alert-warning" role="alert">
        Impossible de déterminer l'état de votre adhésion. Veuillez vérifier <a href="/account">votre compte</a> ou <a href="/contact">contacter le support</a>.
    </div>
<?php endif; ?>

<p><a href="/memberships">Retourner à la liste des adhésions</a></p>
<p><a href="/home">Retourner à l'accueil</a></p>