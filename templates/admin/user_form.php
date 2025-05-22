<?php
// templates/admin/user_form.php

// Ensure this template is loaded within the layout
// $page_title is set in the router (public/index.php)
// $errors and $old_input might be passed from the router on validation failure
$errors = $errors ?? [];
$old_input = $old_input ?? [];
?>

<div class="container mt-4">
    <h1 class="mb-4"><?= $page_title ?? 'Ajouter un Utilisateur' ?></h1>

    <form action="/admin/users/add" method="POST">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="first_name" class="form-label">Prénom</label>
            <input type="text" class="form-control<?= isset($errors['first_name']) ? ' is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= htmlspecialchars($old_input['first_name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">Nom</label>
            <input type="text" class="form-control<?= isset($errors['last_name']) ? ' is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= htmlspecialchars($old_input['last_name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($old_input['email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" required>
            <div id="passwordHelp" class="form-text">Le mot de passe sera hashé pour la sécurité.</div>
        </div>

        <div class="mb-3">
            <label for="is_admin" class="form-label">Rôle</label>
            <select class="form-select<?= isset($errors['is_admin']) ? ' is-invalid' : '' ?>" id="is_admin" name="is_admin" required>
                <option value="0" <?= (isset($old_input['is_admin']) && $old_input['is_admin'] == '0') ? 'selected' : '' ?>>Utilisateur Standard</option>
                <option value="1" <?= (isset($old_input['is_admin']) && $old_input['is_admin'] == '1') ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter l'Utilisateur</button>
        <a href="/admin/users" class="btn btn-secondary">Annuler</a> <?php // Link back to manage users page ?>
    </form>
</div>