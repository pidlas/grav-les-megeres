// Script: menu-active-state.js
// Fonction: Ajoute une classe 'menu--visible' au menu parent quand un lien est cliqué
// Gère à la fois les sous-menus (.niv1) et le lien direct du menu-contact
// Supprime l'état actif pour les pages sans menu (mentions-légales, crédits, accueil)
document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('nav.navbar');
    
    if (!navbar) return;

    // Vérifier si l'écran est desktop (>= 768px)
    function isDesktopView() {
        return window.matchMedia('(min-width: 768px)').matches;
    }

    function setActiveSubmenuState(submenuId) {
        try {
            sessionStorage.setItem('activeSubmenuId', submenuId);
        } catch (error) {
            console.warn('Impossible d’écrire l’état du menu actif dans sessionStorage:', error);
        }
    }

    function clearActiveSubmenuState() {
        try {
            sessionStorage.removeItem('activeSubmenuId');
        } catch (error) {
            console.warn('Impossible de supprimer l’état du menu actif dans sessionStorage:', error);
        }
    }

    // Fonction pour mettre à jour l'état du menu actif
    function updateMenuActiveState(clickedLink) {
        // N'appliquer la classe que sur desktop
        if (!isDesktopView()) return;

        // Vérifier si c'est un lien du sous-menu ou le lien contact
        const submenu = clickedLink.closest('.niv1');
        const menuElement = submenu ? submenu.closest('.menu') : clickedLink.closest('.menu-contact');
        
        if (!menuElement) return;

        // Retirer la classe 'menu--visible' de tous les menus
        navbar.querySelectorAll('.menu').forEach(menu => {
            menu.classList.remove('menu--visible');
        });

        // Ajouter la classe au menu actuel
        menuElement.classList.add('menu--visible');

        // Sauvegarder l'état dans sessionStorage pour la session courante du navigateur
        if (submenu && submenu.id) {
            // Cas des sous-menus : sauvegarder l'ID du sous-menu
            setActiveSubmenuState(submenu.id);
        } else if (menuElement.classList.contains('menu-contact')) {
            // Cas du menu contact : sauvegarder un marqueur
            setActiveSubmenuState('contact');
        }
    }

    // Fonction pour désactiver l'état du menu actif
    function clearMenuActiveState() {
        // Retirer la classe 'menu--visible' de tous les menus
        navbar.querySelectorAll('.menu').forEach(menu => {
            menu.classList.remove('menu--visible');
        });

        // Effacer l'état sauvegardé
        clearActiveSubmenuState();
    }

    // Fonction pour restaurer l'état sauvegardé au chargement
    function restoreMenuActiveState() {
        // N'appliquer la classe que sur desktop
        if (!isDesktopView()) return;

        const activeSubmenuId = sessionStorage.getItem('activeSubmenuId');
        
        if (!activeSubmenuId) return;

        if (activeSubmenuId === 'contact') {
            // Restaurer le menu contact comme actif
            const contactMenu = navbar.querySelector('.menu-contact');
            if (contactMenu) {
                contactMenu.classList.add('menu--visible');
            }
        } else {
            // Restaurer le sous-menu actif
            const submenu = document.getElementById(activeSubmenuId);
            if (submenu) {
                const menu = submenu.closest('.menu');
                if (menu) {
                    menu.classList.add('menu--visible');
                }
            }
        }
    }

    // Ajouter des écouteurs sur tous les liens des sous-menus
    navbar.querySelectorAll('.niv1 a').forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(() => {
                updateMenuActiveState(link);
            }, 0);
        });
    });

    // Ajouter des écouteurs sur le lien du menu contact
    navbar.querySelectorAll('.menu-contact a').forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(() => {
                updateMenuActiveState(link);
            }, 0);
        });
    });

    // Ajouter des écouteurs sur les liens "sans menu" (pages sans menu actif)
    // Ces liens désactiveront l'état du menu actif
    const noMenuLinks = document.querySelectorAll(
        'a[href*="/mentions-legales"], ' +
        'a[href*="/credits"], ' +
        'a[href*="/politique-de-securite"], ' +
        '.logo a, ' +
        '.titre a'
    );
    
    noMenuLinks.forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(() => {
                clearMenuActiveState();
            }, 0);
        });
    });

    // Restaurer l'état au chargement de la page
    restoreMenuActiveState();

    // Gérer le changement de taille d'écran (responsive)
    const mediaQuery = window.matchMedia('(min-width: 768px)');
    mediaQuery.addEventListener('change', (e) => {
        if (!e.matches) {
            // On passe en mode mobile/tablette : supprimer la classe menu--visible
            clearMenuActiveState();
        }
    });
});
