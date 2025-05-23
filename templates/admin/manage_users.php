<?php
// templates/admin/manage_users.php

// Ensure this template is loaded within the layout
// $page_title is set in the router (public/index.php)
// $users and $searchTerm are passed from the router
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestion des Utilisateurs</h1>
        <a href="/admin/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
        </a>
    </div>

    <?php if (isset($_GET['user_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['user_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['user_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['user_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0">Actions</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <a href="/admin/users/add" class="btn btn-success w-100">
                        <i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur
                    </a>
                </div>
                <div class="col-md-6">
                    <form method="GET" action="/admin/users">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher par email, prénom ou nom..." value="<?= htmlspecialchars($searchTerm ?? '') ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search me-1"></i>Rechercher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th>Rôle</th>
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
                                    <div class="d-flex flex-wrap gap-1">
                                        <!-- Lock/Unlock Form -->
                                        <form action="/admin/users/<?= $user['is_locked'] ? 'unlock' : 'lock' ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-<?= $user['is_locked'] ? 'success' : 'warning' ?>">
                                                <i class="bi bi-<?= $user['is_locked'] ? 'unlock' : 'lock' ?>"></i>
                                                <?= $user['is_locked'] ? 'Déverrouiller' : 'Verrouiller' ?>
                                            </button>
                                        </form>

                                        <!-- Modify Button -->
                                        <a href="/admin/users/modify?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square"></i> Modifier
                                        </a>

                                        <!-- Delete Form -->
                                        <form action="/admin/users/delete" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>