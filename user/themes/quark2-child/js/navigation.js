document.addEventListener('DOMContentLoaded', () => {
    const burgerButton = document.querySelector('button.hamburger.box');
    const navBar = document.querySelector('nav.navbar');
    const mainContent = document.querySelector('main');
    const menuTriggers = document.querySelectorAll('.menu-trigger');
    const hoverIntentMenus = document.querySelectorAll('.menu');
    const hoverIntentEnabled = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const hoverOpenDelay = 180;

    // === GESTION DU DIAPORAMA (slideshow) ===
    const sliderCards = document.querySelectorAll('.button-container .card');
    sliderCards.forEach(card => {
        card.addEventListener('click', (event) => {
            const radioId = card.getAttribute('for');
            const radioButton = radioId ? document.getElementById(radioId) : null;
            if (radioButton) {
                if (!radioButton.checked) {
                    event.preventDefault();
                    radioButton.checked = true;
                } else {
                    const link = card.querySelector('.description a');
                    if (link && link.href) {
                        window.location.href = link.href;
                    }
                }
            }
        });
    });

    let lastSubmenuOpenTime = 0;
    const SUBMENU_CLICK_DELAY = 150;

    function setMenuState(isOpen) {
        navBar.classList.toggle('responsive', isOpen);
        burgerButton.classList.toggle('active', isOpen);
        burgerButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('menu-open', isOpen);

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
        if (!burgerButton || !navBar) return;
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
                if (hoverTimer) { window.clearTimeout(hoverTimer); hoverTimer = null; }
            };
            const removeHoverIntent = () => {
                clearHoverTimer();
                menu.classList.remove('is-hovered');
            };
            menu.addEventListener('mouseenter', () => {
                clearHoverTimer();
                hoverTimer = window.setTimeout(() => { menu.classList.add('is-hovered'); }, hoverOpenDelay);
            });
            menu.addEventListener('mouseleave', removeHoverIntent);
            menu.addEventListener('focusin', () => { clearHoverTimer(); menu.classList.add('is-hovered'); });
            menu.addEventListener('focusout', (event) => {
                if (!menu.contains(event.relatedTarget)) removeHoverIntent();
            });
        });
    }

    menuTriggers.forEach((trigger) => {
        trigger.addEventListener('touchstart', (event) => {
            if (!window.matchMedia('(max-width: 60em)').matches) return;
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

    if (navBar) {
        navBar.querySelectorAll('.niv1 a').forEach((link) => {
            link.addEventListener('click', (e) => {
                const timeSinceSubmenuOpen = Date.now() - lastSubmenuOpenTime;
                if (timeSinceSubmenuOpen < SUBMENU_CLICK_DELAY) {
                    e.preventDefault();
                    return;
                }
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
            if (event.key === 'Escape') closeSubmenus();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.menu')) closeSubmenus();
            if (!window.matchMedia('(max-width: 60em)').matches) return;
            if (!navBar.classList.contains('responsive')) return;
            if (navBar.contains(event.target) || burgerButton.contains(event.target)) return;
            setMenuState(false);
        });
    }

    // === INTERSECTION OBSERVER ANIMATIONS ===
    const scrollObserverOptions = { threshold: 0.1, rootMargin: '0px 0px 10% 0px' };
    const scrollObserver = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const listItems = entry.target.querySelectorAll('li');
                listItems.forEach((item, index) => {
                    item.style.transitionDelay = `${index * 0.15}s`;
                    item.classList.add('animate-in');
                });
                currentObserver.unobserve(entry.target);
            }
        });
    }, scrollObserverOptions);

    document.querySelectorAll('.slide-list').forEach(list => {
        scrollObserver.observe(list);
    });
});