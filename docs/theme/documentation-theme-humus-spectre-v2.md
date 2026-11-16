# Documentation Technique : Création du Thème Autonome `humus` et Intégration de Spectre.css

Ce document récapitule l'architecture complète du thème autonome **`humus`** pour **Grav 2.0 / Admin 2**, ainsi que la configuration de compatibilité (pont CSS) permettant d'utiliser le framework **Spectre.css v0.5.9** tout en préservant l'identité visuelle sur-mesure de la compagnie *Les Mégères de l'Humus*.

---

## 1. Architecture du Thème Autonome `humus`

Contrairement à un thème enfant (`quark2-child`) qui dépend d'un thème parent (`quark2` / PicoCSS), le thème **`humus`** embarque l'ensemble de ses gabarits Twig, partiels, feuilles de style, polices, scripts et le socle **Spectre.css v0.5.9** dans un répertoire unique et portable.

### Arborescence du thème (`user/themes/humus/`)
```text
user/themes/humus/
├── blueprints.yaml              # Métadonnées et activation du thème dans Admin 2
├── humus.php                    # Classe PHP du thème (shortcodes et cascade CSS)
├── humus.yaml                   # Configuration autonome (sans flux streams parent)
├── css/
│   ├── spectre.min.css          # Socle officiel Spectre.css v0.5.9 (46 657 octets)
│   ├── all.min.css              # Polices locales (CocoGothic, TanMonCheri) et FontAwesome
│   ├── _variables.css           # Variables CSS + Pont de compatibilité Spectre.css
│   ├── _layout.css              # Grille structurelle principale (.wrapper)
│   ├── _components.css          # Composants UI (navigation, cartes, accordéons)
│   ├── video.css                # Lecteur vidéo sur-mesure
│   ├── audio.css                # Lecteur audio sur-mesure
│   ├── spectacle-opression.css  # Styles dédiés au spectacle O'Pression
│   └── custom.css               # Ajustements ponctuels finaux
├── images/                      # Assets visuels et favicons du thème
├── js/                          # Scripts d'interaction (navigation mobile, lecteurs)
├── shortcodes/                  # Shortcodes personnalisés
└── templates/
    ├── main-layout.html.twig    # Gabarit maître (HTML <head>, SEO centralisé, <h1>, header/footer)
    ├── partials/
    │   ├── header.html.twig     # En-tête du site
    │   ├── navigation.html.twig # Barre de navigation principale et sous-menus
    │   ├── diaporama.html.twig  # Diaporama d'accueil
    │   └── footer.html.twig     # Pied de page
    └── [templates de pages]     # accueil, ateliers, compagnie, contact, cours, equipe, etc.
```

---

## 2. Fichiers de Configuration du Thème

### `user/themes/humus/humus.yaml`
Suppression de la dépendance `streams` vers `quark2` et des options inutilisées :
```yaml
enabled: true
favicon: /user/themes/humus/images/favicon/favicon-48x48.png
```

### `user/themes/humus/blueprints.yaml`
```yaml
name: Humus
slug: humus
type: theme
version: 1.0.0
description: "Thème autonome pour Les Mégères de l'Humus"
icon: leaf
author:
  name: Philippe Guerrini
  email: philippe.guerrini@tutanota.com
homepage: https://grav.lesmegeresdelhumus.fr/grav
keywords: "humus, spectre, theatre, autonome"
form:
  fields:
    tabs:
      type: tabs
      active: 1
      fields:
        theme:
          type: tab
          title: Thème
          fields:
            header.enabled:
              type: toggle
              label: Activer le thème
              highlight: 1
              default: 1
              options:
                1: Oui
                0: Non
              validate:
                type: bool
```

### `user/themes/humus/humus.php`
Dans le gestionnaire d'assets de Grav, **plus la priorité numérique est élevée, plus le fichier est chargé tôt** dans la balise `<head>`. Le socle `spectre.min.css` est donc placé en priorité `120` afin que toutes vos feuilles de style (`110` à `80`) puissent le surcharger proprement :

```php
<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use RocketTheme\Toolbox\Event\Event;

class Humus extends Theme
{
    public static function getSubscribedEvents()
    {
        return [
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onAssetsInitialized' => ['onAssetsInitialized', 0]
        ];
    }

    public function onShortcodeHandlers(Event $e)
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    public function onAssetsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        $assets = $this->grav['assets'];

        // --- NIVEAU 0 : SOCLE FRAMEWORK SPECTRE.CSS (AUTONOME) ---
        $assets->addCss('theme://css/spectre.min.css', ['priority' => 120]);

        // --- NIVEAU 1 : FONDATIONS & POLICES ---
        $assets->addCss('theme://css/all.min.css', ['priority' => 110]);
        $assets->addCss('theme://css/_variables.css', ['priority' => 105]);

        // --- NIVEAU 2 : SQUELETTE STRUCTUREL ---
        $assets->addCss('theme://css/_layout.css', ['priority' => 100]);

        // --- NIVEAU 3 : COMPOSANTS INTERACTIFS STANDARDS ---
        $assets->addCss('theme://css/_components.css', ['priority' => 95]);

        // --- NIVEAU 4 : COMPOSANTS SPÉCIFIQUES MÉDIAS ET PAGES ---
        $assets->addCss('theme://css/video.css', ['priority' => 90]);
        $assets->addCss('theme://css/audio.css', ['priority' => 90]);
        $assets->addCss('theme://css/spectacle-opression.css', ['priority' => 85]);

        // --- NIVEAU 5 : AJUSTEMENTS DE DERNIÈRE MINUTE ---
        $assets->addCss('theme://css/custom.css', ['priority' => 80]);
    }
}
```

---

## 3. Pont de Compatibilité vers Spectre.css (`_variables.css`)

Le framework **Spectre.css v0.5.9** impose par défaut plusieurs règles globales (`html { font-size: 20px; }`, `body { font-size: .8rem; }`, liens violets `#5755d9` soulignés au survol, composants `.navbar`, `.menu` et `.card` en `flex-direction: column`).

Pour neutraliser ces conflits en un seul endroit sans altérer vos feuilles existantes, le bloc suivant est placé à la fin de **`user/themes/humus/css/_variables.css`** (après l'accolade fermante `}` de `:root`) :

```css
/* ==========================================================================
   3. PONT VERS SPECTRE.CSS (Alignement des classes natives du framework)
   ========================================================================== */

/* 1. Rétablit l'échelle standard 1rem = 16px (neutralise le 20px de Spectre) */
html {
    font-size: 16px;
}

/* 2. Rétablit la typographie, la taille de base et le fond du site */
body {
    font-family: var(--font-main);
    font-size: 1rem;
    color: var(--color-brand-secondary);
    background-color: var(--color-whitesmoke);
}

h1, .display-title {
    font-family: var(--font-display);
}

/* 3. Rétablit l'héritage naturel des liens dans les zones structurelles */
header a,
header a:visited,
header a:hover,
header a:focus,
.navbar a,
.navbar a:visited,
.navbar a:hover,
.navbar a:focus,
footer a,
footer a:visited,
footer a:hover,
footer a:focus {
    color: inherit;
    text-decoration: none;
}

/* 4. Liens éditoriaux standards dans le contenu <main> */
main a,
main a:visited {
    color: var(--color-brand-active);
}
main a:hover,
main a:focus,
main a:active {
    color: var(--color-brand-primary);
}

/* 5. Neutralise le composant .menu natif de Spectre (fond blanc + ombre portée) */
.navbar .menu {
    background: transparent;
    box-shadow: none;
    padding: 0;
    min-width: 0;
}

/* 6. Rétablit la pleine largeur alignée sur l'en-tête et évite le retour à la ligne PC */
@media screen and (min-width: 960px) {
    nav.navbar {
        width: 86%;
        max-width: none;
        margin: 0 7%;
        flex-wrap: nowrap;
        background-color: transparent;
    }

    /* Comble l'écart sous-pixel entre deux onglets (effet border-box de Spectre) */
    nav.navbar > .menu {
        box-shadow: 1px 0 0 0 var(--color-brand-primary);
    }
}

/* 7. Rétablit l'alignement vertical en bas des cartes de spectacles (au repos ET active) */
.button-container .card,
.button-container input:checked + label.card {
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    border: 0;
}

/* 8. Harmonisation des boutons natifs Spectre (.btn et .btn-primary) */
.btn {
    border-color: var(--color-brand-primary);
    color: var(--color-brand-active);
    border-radius: var(--radius-standard);
}
.btn:hover, .btn:focus {
    background-color: var(--color-whitesmoke);
    border-color: var(--color-brand-active);
    color: var(--color-brand-active);
}

.btn.btn-primary {
    background-color: var(--color-brand-primary);
    border-color: var(--color-brand-primary);
    color: var(--color-white);
}
.btn.btn-primary:hover, .btn.btn-primary:focus {
    background-color: var(--color-brand-active);
    border-color: var(--color-brand-active);
    color: var(--color-white);
}

.container {
    max-width: var(--content-max-desktop);
}
```

---

## 4. Architecture Flex Objects & SEO Centralisé

* **Onglet commun (`user/blueprints/flex-objects/partials/onglet-commun.yaml`)** : Injecté via `extends@:` sur les blueprints de pages (`accueil`, `ateliers`, `compagnie`, `contact`, `cours`, `equipe`, `gresilhette`, `le-spectacle`, `pages-textuelles`, `stages`, `ta-grand-mere`). Centralise les champs `page_title`, `meta_description` et `meta_robots`.
* **Listes unitaires (`partenaires.yaml`)** : Pas d'onglet commun sur les fiches individuelles afin d'éviter toute confusion éditoriale ; le titre et les balises SEO de la page `/infos/partenaires` sont pilotés par le frontmatter de `partenaires.fr.md` (ex. `metadata: robots: 'noindex, nofollow'`).
* **Gabarit maître (`main-layout.html.twig`)** : Récupère l'objet `flex_seo` transmis par chaque template enfant pour générer automatiquement `<title>`, `<meta name="description">`, `<meta name="robots">`, `<link rel="canonical">` et le bloc surchargable `{% block page_h1 %}`.

---

## 5. Checklist Pas-à-Pas pour le Nettoyage Futur de `_components.css`

Lorsque vous souhaiterez alléger `user/themes/humus/css/_components.css` en tirant parti des classes natives de **Spectre.css**, procédez dans cet ordre pour éviter toute régression :

1. **Sauvegarde préalable** :
   * Copiez `_components.css` vers `_components.css.bak` avant toute suppression.
2. **Sécurisation des classes homonymes** :
   * Vérifiez que toutes les occurrences de `.card` dans `_components.css` sont bien préfixées par `.button-container` (`.button-container .card`, `.button-container .card:hover`) afin d'éviter toute collision avec `.card` de Spectre.
3. **Remplacement des grilles personnalisées par la grille Spectre** :
   * Identifiez dans `_components.css` les anciens conteneurs flex/grid à 2 ou 3 colonnes (ex. grilles d'images ou colonnes de texte) et remplacez-les progressivement dans vos templates Twig par la syntaxe native Spectre : `<div class="columns"><div class="column col-6 col-sm-12">...</div></div>`.
   * Supprimez ensuite les classes de grille obsolètes dans `_components.css`.
4. **Remplacement des classes utilitaires redondantes** :
   * Remplacez les classes utilitaires maison d'alignement et d'espacement par les utilitaires Spectre déjà chargés (`text-center`, `text-left`, `text-right`, `mt-1`, `mt-2`, `mb-2`, `my-2`, `p-2`, `img-responsive`, `float-left`, `float-right`).
5. **Validation multi-écrans et vidage du cache** :
   * Testez chaque page modifiée sur PC (`>= 960px`), tablette (`840px`) et mobile (`480px`), puis exécutez **`bin/grav clearcache`**.

---

## 6. Commandes Utiles de Maintenance Grav

* **Vider l'intégralité du cache Grav (Twig, assets, configuration)** :
  ```bash
  bin/grav clearcache
  ```
* **Vérifier le thème actif dans `user/config/system.yaml`** :
  ```bash
  grep "theme:" user/config/system.yaml
  ```
