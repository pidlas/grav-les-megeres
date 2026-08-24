document.addEventListener('DOMContentLoaded', () => {
    const burgerButton = document.querySelector('button.hamburger.box');
    const navBar = document.querySelector('nav.navbar');
    const mainContent = document.querySelector('main');
    const menuTriggers = document.querySelectorAll('.menu-trigger');
    const hoverIntentMenus = document.querySelectorAll('.menu');
    const hoverIntentEnabled = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const hoverOpenDelay = 180;
    // === GESTION DU DIAPORAMA (slideshow) ===
    // Problème: Cliquer n'importe où sur une carte fermée déclenchait le lien
    // au lieu d'ouvrir d'abord l'image (transition CSS 0.6s).
    // Solution: Bloquer le lien tant que le radio button n'est pas coché.
    // Premier clic = ouvre l'image. Deuxième clic = navigue vers le lien.
    // Écouter le clic sur TOUTE la surface de la carte (.card)
    const sliderCards = document.querySelectorAll('.button-container .card');
    
    sliderCards.forEach(card => {
        card.addEventListener('click', (event) => {
            // Trouver le radio button associé à cette carte
            const radioId = card.getAttribute('for');
            const radioButton = radioId ? document.getElementById(radioId) : null;
            
            if (radioButton) {
                // CAS 1 : La carte est fermée -> On l'ouvre
                if (!radioButton.checked) {
                    event.preventDefault(); // Empêche tout comportement par défaut
                    radioButton.checked = true; // Ouvre la slide
                } 
                // CAS 2 : La carte est DÉJÀ ouverte -> On redirige vers le lien PHP
                else {
                    const link = card.querySelector('.description a');
                    if (link && link.href) {
                        window.location.href = link.href; // Navigue vers l'URL du lien PHP
                    }
                }
            }
        });
    });

    // Variable pour tracker le timing de l'ouverture des sous-menus
    let lastSubmenuOpenTime = 0;
    const SUBMENU_CLICK_DELAY = 150; // Délai (ms) avant d'accepter les clics sur les liens du sous-menu

    if (mainContent) {
        if (!mainContent.id) {
            mainContent.id = 'main-content';
        }
        mainContent.setAttribute('tabindex', '-1');
    }

    function setMenuState(isOpen) {
        navBar.classList.toggle('responsive', isOpen);
        burgerButton.classList.toggle('active', isOpen);
        burgerButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('menu-open', isOpen);

        // Piège le focus dans le menu overlay (accessibilité clavier et lecteurs d'écran)
        if (mainContent) {
            if (isOpen) {
                mainContent.setAttribute('inert', '');
            } else {
                mainContent.removeAttribute('inert');
            }
        }
        if (!isOpen) {
            closeSubmenus();
        }
    }

    function closeSubmenus() {
        document.querySelectorAll('.menu.is-open').forEach((menu) => {
            menu.classList.remove('is-open');
        });

        menuTriggers.forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });

        if (navBar) navBar.classList.remove('submenu-active');
    }

    function myBurger() {
        if (!burgerButton || !navBar) {
            return;
        }

        const isOpen = !navBar.classList.contains('responsive');
        setMenuState(isOpen);
    }

    window.myBurger = myBurger;

    function toggleSubmenu(trigger) {
        const parentMenu = trigger.closest('.menu');
        const willOpen = !parentMenu.classList.contains('is-open');

        closeSubmenus();

        if (willOpen) {
            parentMenu.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            lastSubmenuOpenTime = Date.now();
            if (navBar) navBar.classList.add('submenu-active');
        }
    }

    if (hoverIntentEnabled) {
        hoverIntentMenus.forEach((menu) => {
            let hoverTimer = null;

            const clearHoverTimer = () => {
                if (hoverTimer) {
                    window.clearTimeout(hoverTimer);
                    hoverTimer = null;
                }
            };

            const removeHoverIntent = () => {
                clearHoverTimer();
                menu.classList.remove('is-hovered');
            };

            menu.addEventListener('mouseenter', () => {
                clearHoverTimer();
                hoverTimer = window.setTimeout(() => {
                    menu.classList.add('is-hovered');
                }, hoverOpenDelay);
            });

            menu.addEventListener('mouseleave', removeHoverIntent);

            menu.addEventListener('focusin', () => {
                clearHoverTimer();
                menu.classList.add('is-hovered');
            });

            menu.addEventListener('focusout', (event) => {
                if (!menu.contains(event.relatedTarget)) {
                    removeHoverIntent();
                }
            });
        });
    }

    menuTriggers.forEach((trigger) => {
        trigger.addEventListener('touchstart', (event) => {
            if (!window.matchMedia('(max-width: 60em)').matches) {
                return;
            }

            // Sur tactile mobile: le 1er appui ouvre uniquement le sous-menu.
            event.preventDefault();
            trigger.dataset.touchHandled = 'true';
            toggleSubmenu(trigger);
        }, { passive: false });

        trigger.addEventListener('click', (e) => {
            if (trigger.dataset.touchHandled === 'true') {
                trigger.dataset.touchHandled = 'false';
                e.preventDefault();
                return;
            }

            toggleSubmenu(trigger);
        });
    });

    // Protéger les liens du sous-menu contre les clics accidentels
    if (navBar) {
        navBar.querySelectorAll('.niv1 a').forEach((link) => {
            link.addEventListener('click', (e) => {
                const timeSinceSubmenuOpen = Date.now() - lastSubmenuOpenTime;
                
                // Si le clic arrive trop rapidement après l'ouverture du sous-menu, c'est probablement accidentel
                if (timeSinceSubmenuOpen < SUBMENU_CLICK_DELAY) {
                    e.preventDefault();
                    return;
                }

                // Si on est sur mobile et le menu est ouvert, fermer le menu avant de naviguer
                if (window.matchMedia('(max-width: 60em)').matches && navBar.classList.contains('responsive')) {
                    setMenuState(false);
                }
            });
        });
    }

    if (navBar && burgerButton) {
        navBar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.matchMedia('(max-width: 60em)').matches && navBar.classList.contains('responsive')) {
                    setMenuState(false);
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && navBar.classList.contains('responsive')) {
                setMenuState(false);
                burgerButton.focus();
                return;
            }

            if (event.key === 'Escape') {
                closeSubmenus();
            }
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.menu')) {
                closeSubmenus();
            }

            if (!window.matchMedia('(max-width: 60em)').matches) {
                return;
            }

            if (!navBar.classList.contains('responsive')) {
                return;
            }

            if (navBar.contains(event.target) || burgerButton.contains(event.target)) {
                return;
            }

            setMenuState(false);
        });
    }

    // === INTERSECTION OBSERVER POUR LES ANIMATIONS AU SCROLL ===
    const scrollObserverOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px 10% 0px' // Déclenche l'animation quand le texte est rentré de 10% dans le viewer
    };

    // Renommé en scrollObserver pour éviter les conflits de nommage globaux
    const scrollObserver = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const listItems = entry.target.querySelectorAll('li');
                
                listItems.forEach((item, index) => {
                    // Au lieu d'un setTimeout JS qui consomme de la mémoire,
                    // on injecte un délai de transition CSS progressif directement.
                    // Exemple : le 1er li aura 0s, le 2e aura 0.05s, le 3e aura 0.10s, etc.
                    item.style.transitionDelay = `${index * 0.15}s`;
                    
                    // On ajoute la classe immédiatement, le CSS gère le décalage tout seul
                    item.classList.add('animate-in');
                });
                
                // On arrête d'observer l'élément pour libérer le processeur du smartphone
                currentObserver.unobserve(entry.target);
            }
        });
    }, scrollObserverOptions);

    // Observer toutes les listes avec la classe slide-list
    document.querySelectorAll('.slide-list').forEach(list => {
        scrollObserver.observe(list);
    });
})