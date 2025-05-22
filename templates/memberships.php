<?php
$title = "Adhésions BDE";




$allMemberships = $allMemberships ?? [];
$userActiveSoutien = $userActiveSoutien ?? null;
$userActivePremium = $userActivePremium ?? null;
$upgradePrice = $upgradePrice ?? null;
$isUserLoggedIn = $isUserLoggedIn ?? isset($_SESSION['user_id']);
$dbError = $dbError ?? null;
?>

<h1><?= htmlspecialchars($title) ?></h1>

<p>Devenez membre du BDE Informatique pour soutenir nos actions et bénéficier d'avantages exclusifs tout au long de l'année !</p>

<?php if (isset($dbError)): ?>
    <div class="alert-danger">
        <p><?= htmlspecialchars($dbError) ?></p>
    </div>
<?php endif; ?>

<?php if (!$isUserLoggedIn): ?>
    <div class="alert-info">
        <p>Vous devez être <a href="/login?redirect=/memberships">connecté</a> pour adhérer.</p>
    </div>
<?php endif; ?>

<div class="membership-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
    <?php if (empty($allMemberships) && !isset($dbError)): ?>
        <p>Aucune adhésion n'est disponible pour le moment.</p>
    <?php else: ?>
        <?php foreach ($allMemberships as $membership):
            $isSoutien = (strpos($membership['name'], 'Soutien') !== false);
            $isPremium = (strpos($membership['name'], 'Premium') !== false);
        ?>
            <div class="membership-item" style="border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; background-color: var(--header-bg); display: flex; flex-direction: column;">
                <h2><?= htmlspecialchars($membership['name']) ?></h2>


                <?php if ($isPremium && $userActiveSoutien && !$userActivePremium && isset($upgradePrice)): ?>
                    <p class="membership-price" style="font-size: 1.2em; color: var(--text-color); margin-bottom: 5px;">
                        <span style="text-decoration: line-through; color: var(--muted-color);"><?= htmlspecialchars(number_format($membership['price'], 2, ',', ' ')) ?> €</span>
                        <span style="font-size: 1.3em; font-weight: bold; color: var(--primary-color); display: block;">
                            Mise à niveau: <?= htmlspecialchars(number_format($upgradePrice, 2, ',', ' ')) ?> €
                        </span>
                         <span style="font-size: 0.8em; color: var(--muted-color); display: block;">pour le reste de l'année</span>
                    </p>
                <?php else: ?>
                    <p class="membership-price" style="font-size: 1.5em; font-weight: bold; color: var(--primary-color); margin-bottom: 15px;">
                        <?= htmlspecialchars(number_format($membership['price'], 2, ',', ' ')) ?> € / an
                    </p>
                <?php endif; ?>


                <p style="flex-grow: 1;"><?= nl2br(htmlspecialchars($membership['description'])) ?></p>


                <div style="margin-top: auto;">
                    <?php if ($isUserLoggedIn): ?>
                        <?php if ($userActivePremium): ?>
                            <?php if ($isPremium): ?>
                                <p><button class="button" disabled>Déjà Membre Premium</button></p>
                                <p style="font-size: 0.9em; text-align: center;">Valide jusqu'au: <?= htmlspecialchars(date('d/m/Y', strtotime($userActivePremium['end_date']))) ?></p>
                            <?php else: ?>
                                <p><button class="button" disabled>Adhésion Premium Active</button></p>
                            <?php endif; ?>
                        <?php elseif ($userActiveSoutien): ?>
                            <?php if ($isSoutien): ?>
                                <p><button class="button" disabled>Déjà Membre Soutien</button></p>
                                <p style="font-size: 0.9em; text-align: center;">Valide jusqu'au: <?= htmlspecialchars(date('d/m/Y', strtotime($userActiveSoutien['end_date']))) ?></p>
                            <?php elseif ($isPremium && isset($upgradePrice)): ?>
                                <a href="https://pay.sumup.com/b2c/QHBY03LH" class="button" target="_blank" rel="noopener noreferrer">Mettre à niveau vers Premium (13€)</a>
                                <p style="font-size: 0.8em; text-align: center; margin-top: 5px;">(Ouvre une page de paiement SumUp)</p>
                            <?php else: ?>
                                 <p><button class="button" disabled>Action non disponible</button></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($isSoutien): ?>
                                <a href="https://pay.sumup.com/b2c/QB5S7MZU" class="button" target="_blank" rel="noopener noreferrer">Choisir Soutien BDE (5€)</a>
                                <p style="font-size: 0.8em; text-align: center; margin-top: 5px;">(Ouvre une page de paiement SumUp)</p>
                            <?php elseif ($isPremium): ?>
                                <a href="https://pay.sumup.com/b2c/QHBY03LH" class="button" target="_blank" rel="noopener noreferrer">Choisir Premium BDE (13€)</a>
                                <p style="font-size: 0.8em; text-align: center; margin-top: 5px;">(Ouvre une page de paiement SumUp)</p>
                            <?php else: // Solution de repli pour d'éventuels autres types d'adhésion ?>
                                <button class="button" disabled>Adhésion non configurable</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                         <p><button class="button" disabled>Connectez-vous pour adhérer</button></p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php

?>