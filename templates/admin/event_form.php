<?php




$esc = 'htmlspecialchars';
$event = $layout_vars['event'] ?? null; // Données de l'événement existant pour la modification
$form_data = $layout_vars['form_data'] ?? $_SESSION['form_data'] ?? []; // Données soumises (ex: en cas d'erreur de validation) ou données de session
$isEditing = $layout_vars['isEditing'] ?? ($event !== null); // Déterminer si nous sommes en mode édition

// Effacer les données du formulaire de session après utilisation
unset($_SESSION['form_data']);

function getValue($field_name, $default = '') {
    global $form_data, $event, $esc;
    if (isset($form_data[$field_name])) {
        return $esc($form_data[$field_name]);
    }
    if ($event && isset($event[$field_name])) {

        if ($field_name === 'event_date' && !empty($event['event_date'])) {
             try {

                 $date = new DateTime($event['event_date']);

                 return $date->format('Y-m-d\TH:i');
             } catch (Exception $e) {

                 error_log("Error parsing event_date '{$event['event_date']}' for form: " . $e->getMessage());
                 return '';
             }
        }
        return $esc($event[$field_name]);
    }
    return $esc($default);
}

?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $isEditing ? 'Modifier l\'Événement' : 'Créer un Nouvel Événement' ?></h2>
        <a href="/admin/events" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
        </a>
    </div>

    <?php
    // Afficher les messages flash de session si disponibles
    $message = $_SESSION['message'] ?? $layout_vars['error_message'] ?? null;
    $message_type = $_SESSION['message_type'] ?? ($layout_vars['error_message'] ? 'danger' : null); // Par défaut à danger pour error_message

    if ($message):
        $alert_class = 'alert-danger'; // Par défaut
        if ($message_type === 'success') $alert_class = 'alert-success';
        if ($message_type === 'warning') $alert_class = 'alert-warning';
        if ($message_type === 'info') $alert_class = 'alert-info';
    ?>
        <div class="alert <?= $alert_class ?> alert-dismissible fade show" role="alert">
            <?= $message /* Already HTML escaped in controller or contains safe HTML */ ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
        // Effacer le message flash après l'avoir affiché
        unset($_SESSION['message'], $_SESSION['message_type']);
    endif;
    ?>

<form action="<?= $esc($layout_vars['form_action']) ?>" method="POST" enctype="multipart/form-data" class="card">
    <div class="card-body">
        <?php // Ajouter un jeton CSRF si implémenté plus tard ?>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= getValue('title') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?= getValue('description') ?></textarea>
                    <div class="form-text">Décrivez l'événement en détail (activités, programme, etc.)</div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="image" class="form-label">Image de l'événement</label>
                    <?php if ($event && !empty($event['image_path'])): ?>
                        <div class="mb-2 text-center">
                            <img src="/uploads/events/<?= $esc($event['image_path']) ?>" alt="Image actuelle" class="img-thumbnail" style="max-height: 200px; max-width: 100%;">
                            <p class="mt-2"><small>Image actuelle. Télécharger une nouvelle image la remplacera.</small></p>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/gif">
                    <div class="form-text">Formats acceptés : JPG, PNG, GIF. Taille max : 2MB.</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="location" class="form-label">Lieu <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" value="<?= getValue('location') ?>" required>
                    <div class="form-text">Ex: Salle Polyvalente, Campus Centre, En ligne (Discord)</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="event_date" class="form-label">Date et Heure <span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control" id="event_date" name="event_date" value="<?= getValue('event_date') ?>" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="price" class="form-label">Prix (€) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= getValue('price', '0.00') ?>" required>
                        <span class="input-group-text">€</span>
                    </div>
                    <div class="form-text">Indiquez 0 pour un événement gratuit</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="points_awarded" class="form-label">Points Attribués <span class="text-danger">*</span></label>
                    <input type="number" step="1" min="0" class="form-control" id="points_awarded" name="points_awarded" value="<?= getValue('points_awarded', '50') ?>" required>
                    <div class="form-text">Points gagnés par les participants pour le classement</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="/admin/events" class="btn btn-secondary">
            <i class="bi bi-x-circle me-2"></i>Annuler
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-<?= $isEditing ? 'save' : 'plus-circle' ?> me-2"></i>
            <?= $isEditing ? 'Mettre à jour l\'événement' : 'Créer l\'événement' ?>
        </button>
    </div>
</form>