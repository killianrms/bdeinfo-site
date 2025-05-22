<?php $title = "Inscription"; ?>

<h2>Inscription</h2>

<?php if (isset($error_message)): ?>
    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
<?php endif; ?>
<?php if (isset($success_message)): ?>
    <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
<?php endif; ?>

<form action="/register" method="post">
    <div>
        <label for="first_name">Prénom :</label>
        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
    </div>
    <div>
        <label for="last_name">Nom :</label>
        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
    </div>
    <div>
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>
    <div>
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>

        <small>Minimum 8 caractères, dont 1 majuscule, 1 chiffre et 1 caractère spécial (!@#$%^&*()_+-=[]{};':"\\|,.<>/?~).</small>
    </div>
    <div>
        <button type="submit">S'inscrire</button>
    </div>
</form>