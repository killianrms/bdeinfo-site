<?php


$title = "Mon Compte";


function formatDateFrench($dateString) {
    if (!$dateString) return 'N/A';
    try {
        $date = new DateTime($dateString);
        if (class_exists('IntlDateFormatter')) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Europe/Paris');
            return $formatter->format($date);
        } else {
            return $date->format('d/m/Y');
        }
    } catch (Exception $e) {
        error_log("Error formatting date '$dateString': " . $e->getMessage());
        return 'Date invalide';
    }
}

?>

<h1><?= htmlspecialchars($title) ?></h1>

<?php

if (isset($_SESSION['message'])): ?>
    <div class="alert <?= isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'error' ? 'alert-danger' : 'alert-success' ?>" role="alert">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
<?php

    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
endif;
?>

<div class="account-details" style="margin-bottom: 30px;">
    <h2>Vos Informations</h2>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($user['first_name'] ?? 'N/A') ?></p>
    <p><strong>Nom :</strong> <?= htmlspecialchars($user['last_name'] ?? 'N/A') ?></p>
    <p><strong>Email :</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
    <p><small><em>(Votre nom et email ne peuvent pas être modifiés ici.)</em></small></p>
</div>

<div class="account-membership">
    <h2>Votre Adhésion</h2>

    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
        <div class="active-membership-info admin-premium" style="border: 1px solid var(--primary-color); padding: 15px; border-radius: 5px; background-color: rgba(var(--primary-color-rgb, 74, 144, 226), 0.15); ">
            <p><strong>Type d'adhésion :</strong> Premium (Administrateur)</p>
            <p><strong>Statut :</strong> Actif (accordé automatiquement)</p>
            <p><small>Les administrateurs bénéficient automatiquement des avantages Premium.</small></p>
        </div>
         <p style="margin-top: 15px;"><a href="/memberships" class="button secondary">Voir les offres pour les membres</a></p>
    <?php elseif (empty($activeMembership)): ?>
        <p>Vous n'avez actuellement aucune adhésion active.</p>
        <p><a href="/memberships" class="button">Découvrir nos offres d'adhésion !</a></p>
    <?php else: ?>
        <div class="active-membership-info" style="border: 1px solid var(--primary-color); padding: 15px; border-radius: 5px; background-color: rgba(var(--primary-color-rgb, 74, 144, 226), 0.1); ">
            <p><strong>Type d'adhésion :</strong> <?= htmlspecialchars($activeMembership['membership_name']) ?></p>
            <p><strong>Expire le :</strong> <?= htmlspecialchars(formatDateFrench($activeMembership['end_date'])) ?></p>
            <?php if (!empty($activeMembership['membership_description'])): ?>
                <p><small><?= nl2br(htmlspecialchars($activeMembership['membership_description'])) ?></small></p>
            <?php endif; ?>
        </div>
         <p style="margin-top: 15px;"><a href="/memberships" class="button secondary">Voir toutes les offres</a></p>
    <?php endif; ?>
</div>

<div class="account-password-change" style="margin-top: 30px; margin-bottom: 30px;">
    <h2>Changer votre mot de passe</h2>
    <form action="/change-password" method="POST">
        <div class="form-group">
            <label for="current_password">Mot de passe actuel :</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">Nouveau mot de passe :</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">
            <small>Minimum 8 caractères.</small>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="button">Mettre à jour le mot de passe</button>
    </form>
</div>


<?php

?>