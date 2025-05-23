<?php
?>

<div class="container mt-4">
    <h1 class="mb-4">Tableau de bord Admin</h1>

    <p>Bienvenue dans l'espace d'administration du BDE Informatique de Montpellier. D'ici, vous pouvez gérer les événements, les utilisateurs et d'autres paramètres du site.</p>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">Gestion des Événements</h2>
                </div>
                <div class="card-body">
                    <p>Créez, modifiez et gérez les événements du BDE.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="/admin/events/create" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Créer un événement
                        </a>
                        <a href="/admin/events" class="btn btn-primary">
                            <i class="bi bi-list-check me-2"></i>Gérer les événements
                        </a>
                        <a href="/admin/events/history" class="btn btn-secondary">
                            <i class="bi bi-clock-history me-2"></i>Historique
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h2 class="h5 mb-0">Gestion des Utilisateurs</h2>
                </div>
                <div class="card-body">
                    <p>Gérez les comptes utilisateurs, les adhésions et les permissions.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="/admin/users" class="btn btn-info">
                            <i class="bi bi-people me-2"></i>Gérer les utilisateurs
                        </a>
                        <a href="/admin/users/add" class="btn btn-outline-info">
                            <i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h2 class="h5 mb-0">Statistiques</h2>
                </div>
                <div class="card-body">
                    <p>Consultez les statistiques du site et des événements.</p>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Événements actifs
                            <span class="badge bg-primary rounded-pill"><?= $layout_vars['active_events_count'] ?? '?' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Utilisateurs inscrits
                            <span class="badge bg-primary rounded-pill"><?= $layout_vars['users_count'] ?? '?' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Adhésions actives
                            <span class="badge bg-primary rounded-pill"><?= $layout_vars['active_memberships_count'] ?? '?' ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h2 class="h5 mb-0">Actions rapides</h2>
                </div>
                <div class="card-body">
                    <p>Accédez rapidement aux fonctionnalités principales.</p>
                    <div class="d-grid gap-2">
                        <a href="/leaderboard" class="btn btn-outline-success">
                            <i class="bi bi-trophy me-2"></i>Voir le classement
                        </a>
                        <a href="/memberships" class="btn btn-outline-success">
                            <i class="bi bi-card-checklist me-2"></i>Gérer les adhésions
                        </a>
                        <a href="/faq" class="btn btn-outline-success">
                            <i class="bi bi-question-circle me-2"></i>Consulter la FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>