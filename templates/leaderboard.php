<?php
$title = "Classement BDE";


?>

<h1><?= htmlspecialchars($title) ?></h1>

<p>Découvrez le classement des membres les plus actifs et engagés du BDE ! Les points sont attribués en fonction de la participation aux événements.</p>

<?php if (empty($leaderboard_users)): ?>
    <p>Le classement n'est pas encore disponible ou personne n'a encore marqué de points.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Nom d'utilisateur</th>
                <th>Points d'XP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard_users as $index => $user): ?>
                <tr>
                    <td data-label="Rang"><?= $index + 1 ?></td>
                    <td data-label="Nom"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td data-label="Points d'XP"><?= htmlspecialchars($user['total_points']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php

?>