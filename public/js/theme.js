document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    const currentTheme = localStorage.getItem('theme');


    const applyTheme = (theme) => {
        if (theme === 'light') {
            htmlElement.classList.add('light-mode');
            if (themeToggle) themeToggle.textContent = '🌙'; // Lune pour thème clair -> passer au sombre
        } else {

            htmlElement.classList.remove('light-mode');
            if (themeToggle) themeToggle.textContent = '☀️'; // Soleil pour thème sombre -> passer au clair
        }
    };


    applyTheme(currentTheme);


    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            let newTheme;
            if (htmlElement.classList.contains('light-mode')) {

                newTheme = 'dark';
            } else {

                newTheme = 'light';
            }

            applyTheme(newTheme);

            localStorage.setItem('theme', newTheme);
        });
    } else {
        console.warn('Theme toggle button (#theme-toggle) not found.');
    }
    const hamburgerButton = document.getElementById('hamburger-menu');
    const navLinks = document.getElementById('navbar-links');

    if (hamburgerButton && navLinks) {
        hamburgerButton.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburgerButton.classList.toggle('active');

            // Mettre à jour l'attribut aria-expanded pour l'accessibilité
            const isExpanded = hamburgerButton.getAttribute('aria-expanded') === 'true';
            hamburgerButton.setAttribute('aria-expanded', !isExpanded);
        });
    } else {
        console.warn('Hamburger menu button (#hamburger-menu) or navigation links (#navbar-links) not found.');
    }
});