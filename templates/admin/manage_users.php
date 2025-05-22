<?php
// templates/admin/manage_users.php

// Ensure this template is loaded within the layout
// $page_title is set in the router (public/index.php)
// $users and $searchTerm are passed from the router
?>

<div class="container mt-4">
    <h1 class="mb-4">Gestion des Utilisateurs</h1>

    <?php if (isset($_GET['user_success'])): // Renamed query param for clarity ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['user_success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['user_error'])): // Renamed query param for clarity ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['user_error']) ?></div>
    <?php endif; ?>

<!-- Add User Button -->
    <div class="mb-3">
        <a href="/admin/users/add" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter un Utilisateur
        </a>
    </div>
    <!-- Search Bar -->
    <form method="GET" action="/admin/users" class="mb-3"> <?php // Action points to manage users page ?>
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Rechercher des utilisateurs par email, prénom ou nom..." value="<?= htmlspecialchars($searchTerm ?? '') // Use null coalescing ?>">
            <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Verrouillé</th>
                    <th>Admin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucun utilisateur trouvé<?= isset($searchTerm) && $searchTerm ? ' correspondant à "' . htmlspecialchars($searchTerm) . '"' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['first_name']) ?></td>
                            <td><?= htmlspecialchars($user['last_name']) ?></td>
                            <td><?= $user['is_locked'] ? '<span class="badge bg-danger">Oui</span>' : '<span class="badge bg-success">Non</span>' ?></td>
                            <td><?= $user['is_admin'] ? '<span class="badge bg-info">Oui</span>' : '<span class="badge bg-secondary">Non</span>' ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="text-muted fst-italic">(Admin - Aucune action)</span>
                                <?php else: ?>
                                    <!-- Lock/Unlock Form -->
                                    <form action="/admin/users/<?= $user['is_locked'] ? 'unlock' : 'lock' ?>" method="POST" style="display: inline-block;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-<?= $user['is_locked'] ? 'success' : 'warning' ?>">
                                            <?= $user['is_locked'] ? 'Déverrouiller' : 'Verrouiller' ?>
                                        </button>
                                    </form>

                                    <!-- Modify Button -->
                                    <a href="/admin/users/modify?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>

                                    <!-- Delete Form -->
                                    <form action="/admin/users/delete" method="POST" style="display: inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>