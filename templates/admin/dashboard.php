<?php
?>

<div class="container mt-4">
    <h1 class="mb-4">Tableau de bord Admin</h1>

    <p>Bienvenue dans l'espace d'administration. D'ici, vous pouvez gérer les événements, les utilisateurs et d'autres paramètres du site.</p>

    <section class="mb-4">
        <h2>Gestion Événements</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="/admin/events/create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Créer événement
            </a>
            <a href="/admin/events" class="btn btn-secondary">
                <i class="fas fa-list me-2"></i>Gestion événement
            </a>
            <a href="/admin/events/history" class="btn btn-info">
                <i class="fas fa-history me-2"></i>Historique
            </a>
        </div>
    </section>

    <section class="mb-4">
        <h2>Gestion Utilisateurs</h2>
        <a href="/admin/users" class="btn">
            <i class="fas fa-users-cog me-2"></i>Gérer les Utilisateurs
        </a>
    </section>
</div>