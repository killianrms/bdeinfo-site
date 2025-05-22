<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($layout_vars['title']) ? htmlspecialchars($layout_vars['title']) : (isset($title) ? htmlspecialchars($title) : 'APII - BDE Informatique 2025-2026'); ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/events.css">
    <link rel="stylesheet" href="/css/footer.css">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>

    <header>
        <nav>
            <div class="navbar-container">
                <!-- Logo -->
                <div class="navbar-logo">
                    <a href="/home"><img src="/bde.webp" alt="Logo BDE" class="logo-img"></a>
                </div>

                <!-- Bouton Menu Hamburger (Mobile Uniquement) -->
                <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle menu" aria-expanded="false">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>

                <!-- Liens de Navigation -->
                <div class="navbar-links" id="navbar-links">
                    <ul>
                        <li><a href="/home">Accueil</a></li>
                        <li><a href="/events">Événements</a></li>
                        <li><a href="/memberships">Adhésions</a></li>
                        <li><a href="/leaderboard">Classement</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/faq">FAQ</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="/account">Mon Compte</a></li>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                                <li><a href="/admin/dashboard">Admin</a></li>
                                <?php endif; ?>
                            <li><a href="/logout">Déconnexion</a></li>
                            <?php else: ?>
                            <li><a href="/login">Connexion</a></li>
                            <?php endif; ?>
                    </ul>
                </div>
            </div>
         </nav>
        <button id="theme-toggle" title="Changer le thème"></button>
    </header>

    <main>
        <?php


        if (!empty($layout_vars) && is_array($layout_vars)) {
            extract($layout_vars);
        }


        if (!empty($page_content) && file_exists($page_content)) {
            include $page_content;
        } elseif ($request_uri !== '/404') {
             $notFoundTemplate = TEMPLATE_PATH . '404.php';
             if (file_exists($notFoundTemplate)) {
                 include $notFoundTemplate;
             } else {
                echo "<p>Erreur : Contenu non trouvé.</p>";
             }
        } else {
             echo "<p>Erreur : Contenu non trouvé.</p>";
        }
        ?>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="/bde.webp" alt="Logo BDE" class="footer-logo-img">
                <h3>APII - BDE Informatique</h3>
            </div>
            <div class="footer-links">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="/home">Accueil</a></li>
                    <li><a href="/events">Événements</a></li>
                    <li><a href="/leaderboard">Classement</a></li>
                    <li><a href="/memberships">Adhésions</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Informations</h4>
                <ul>
                    <li><a href="/mentions-legales">Mentions Légales</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <li><a href="/faq">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-social">
                <h4>Suivez-nous</h4>
                <div class="social-icons">
                    <a href="#" class="social-icon" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-icon" title="Discord"><i class="bi bi-discord"></i></a>
                    <a href="#" class="social-icon" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="social-icon" title="GitHub"><i class="bi bi-github"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> APII - BDE Informatique 2025-2026 - Tous droits réservés.</p>
        </div>
    </footer>


<script src="/js/theme.js" defer></script>
</body>
</html>