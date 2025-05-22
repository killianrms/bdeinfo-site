<?php $title = "Connexion"; ?>

<h2>Connexion</h2>

<?php if (isset($error_message)): ?>
    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
<?php endif; ?>
<?php if (isset($_GET['registered'])): ?>
    <p class="success-message">Inscription réussie ! Vous pouvez maintenant vous connecter.</p>
<?php endif; ?>
<?php if (isset($_GET['logged_out'])): ?>
    <p class="success-message">Vous avez été déconnecté avec succès.</p>
<?php endif; ?>


<form action="/login" method="post">
    <div>
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>
    <div>
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div>
        <button type="submit">Se connecter</button>
    </div>
    <div style="margin-top: 10px;">
        <p>Je n'ai pas encore de compte ? <a href="/register">S'inscrire</a></p>
    </div>
</form>