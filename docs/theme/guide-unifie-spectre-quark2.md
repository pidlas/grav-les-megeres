# Guide Unifié d'Intégration de Spectre.css dans `quark2-child`

**Environnement :** CMS Grav 2.0+ / Admin 2 / Thème Enfant `quark2-child` (*Compagnie Les Mégères de l'Humus*)

Ce document fusionne et synthétise l'ensemble des règles techniques, des choix d'architecture et des retours d'expérience validés pour intégrer harmonieusement votre identité visuelle au framework **Spectre.css**.

---

## 1. Architecture Réelle et Cascade CSS

Contrairement à une approche nécessitant une compilation Sass/SCSS, votre thème parent **`user/themes/quark2/`** embarque déjà la version complète et précompilée de **Spectre.css** dans `css/theme.css` (63 Ko).

Votre thème enfant **`user/themes/quark2-child/`** surcharge ce socle en **100 % CSS natif** via `quark2-child.php` (`onAssetsInitialized`). En production, Grav fusionne et minifie automatiquement toute la chaîne grâce à `{{ assets.css() | raw }}`.

### Hiérarchie des feuilles de style (`quark2-child/css/`)
1. **`_variables.css` (Priorité 105)** : Déclare vos typographies (`CocoGothic`, `TanMonCheri`, `The Seasons`), vos variables racines `:root` (terracotta, prune, abricot) et le **pont d'alignement vers Spectre.css**.
2. **`_layout.css` (Priorité 100)** : Définit le squelette CSS Grid global (`.wrapper`, `header`, `main`, `section`, `footer`). **À conserver intégralement.**
3. **`_components.css` (Priorité 95)** : Contient vos composants graphiques sur-mesure (encadrés fleuris, grilles équipe, animations, listes à puces).
4. **`video.css`, `audio.css`, `spectacle-opression.css` (Priorités 90-85)** : Styles dédiés aux lecteurs médias et pages spécifiques.
5. **`custom.css` (Priorité 80)** : Ajustements ponctuels de fin de cascade.

---

## 2. Le Pont CSS vers Spectre.css (`_variables.css`)

Pour que les composants natifs de Spectre (liens, boutons, textes, conteneurs) adoptent automatiquement votre charte graphique sans recourir à `!important`, ajoutez ce bloc à la fin de `user/themes/quark2-child/css/_variables.css` :

```css
/* ==========================================================================
   3. PONT VERS SPECTRE.CSS (Alignement des classes natives du framework)
   ========================================================================== */

body {
    font-family: var(--font-main);
    color: var(--color-brand-secondary);
    background-color: var(--color-whitesmoke);
}

h1, .display-title {
    font-family: var(--font-display);
}

a {
    color: var(--color-brand-active);
}
a:hover, a:focus, a:active {
    color: var(--color-brand-primary);
}

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

## 3. Règles de Cohabitation et Points de Vigilance

### A. Isoler `.card` dans le diaporama d'accueil (`_components.css`, lignes 584 et 600)
Spectre.css utilise nativement la classe `.card` pour ses encadrés standards. Dans `_components.css`, vous avez défini `.card` et `.card:hover` pour le diaporama d'accueil.
* **Action** : Préfixez `.card {` par `.button-container .card {` (ligne 584) et `.card:hover {` par `.button-container .card:hover {` (ligne 600).
* **Résultat** : Le diaporama d'accueil conserve 100 % de son comportement, et la classe `.card` de Spectre reste disponible sans conflit pour le reste du site.

### B. Préserver les espacements verticaux (`.marge-haute` vs `mt-2`)
Les utilitaires Spectre suivent une échelle stricte (`mt-1` = `0.2rem` / `4px`, `mt-2` = `0.4rem` / `8px`).
* **Règle** : Vérifiez la valeur de `.marge-haute` (ligne 113 de `_components.css`) avant de la remplacer. Si elle correspond à un grand espacement (ex. `1.5rem` ou `2rem`), conservez `.marge-haute` ou utilisez `--space-m` / `--space-l` afin de ne pas tasser le design.

### C. Sanctuariser la table de la page Contact (`.com td`, ligne 1355)
* **Règle** : **Ne jamais appliquer la classe `.table` de Spectre** sur `<table class="com">`. Les bordures et marges par défaut de Spectre altéreraient vos cellules fusionnées (`rowspan`) et les alignements sur-mesure de vos pôles téléphoniques.

### D. Blindage géométrique des grilles spécifiques (Exemple : Page Partenaires)
Lorsque les grilles fluides de Spectre (`columns`) montrent leurs limites face à des contraintes métiers strictes (comme aligner 7 logos XXL sur une seule ligne horizontale sur PC `≥ 960px` sans effondrement), conservez une grille Flexbox dédiée et étanche :
* **Conteneur** : `.g-partenaires-container` avec `flex-wrap: nowrap !important` sur ordinateur.
* **Cellule tampon** : `.col-partenaire` avec `padding: 0.6rem` et `max-width: calc(100% / 7)`.
* **Carte & Action** : `.partenaire-carte-pro` (encadré) et `.partenaire-btn-pro` (bouton autonome utilisant `--color-brand-primary` avec un lien unique).

---

## 4. Cartographie de `_components.css` et Correspondances

### Classes utilitaires remplaçables par Spectre.css
Une fois remplacées dans l'ensemble de vos templates Twig, ces classes pourront être supprimées de `_components.css` :

| Besoin de mise en page | Ancienne classe métier                         | Équivalent natif Spectre.css  | Impact visuel                  |
| :---                   | :---                                           | :---                          | :---                           |
| Conteneur centré       | Wrappers manuels                               | `<div class="container">`     | Identique (`max-width` aligné) |
| Ligne de grille        | Flexbox basique                                | `<div class="columns">`       | Identique                      |
| Colonne pleine largeur | Bloc 100 %                                     | `<div class="column col-12">` | Identique                      |
| Centrage texte / image | `.p_centrer` (l. 116), `.img-centre` (l. 1292) | `text-center`                 | Strictement identique          |
| Graisse de texte       | `.gras` (l. 122), `.tresgras` (l. 125)         | `text-bold`                   | Strictement identique (`700`)  |
| Soulignement           | `.souligne` (l. 143)                           | `<u>`                         | Identique                      |

### Composants sur-mesure à conserver impérativement
Spectre.css n'offre aucun équivalent pour ces classes qui constituent l'identité graphique du site :
* **Typographies** : `.display-title`, `.coco`, `.tan`, `.theseasonsregular`, `.theseasonsbold`.
* **Navigation & Accessibilité** : `.skip-link`, `.navbar`, `.container-lignes`, `.morph.logo`, `.lien-i-seul`, `.lien-e-seul`.
* **Animations & Effets** : `.anime_hover`, `.fade-zoom`, `.morph`, `.slide-list`, `.jolie-ombres`.
* **Grilles artistiques asymétriques** : `.g_equipe`, `.g_bio`, `.g_compagnie`, `.g_compagnie2`, `.g_compagnie3`.
* **Encadrés & Pages dédiées** : `.encadre`, `.encadre-lenga`, `.encadre-extrait`, `.com` (Contact), `.g-partenaires-container` (Partenaires).

---

## 5. Feuille de Route d'Exécution

1. **Sécuriser `_components.css`** : Préfixer `.card` et `.card:hover` par `.button-container` aux lignes 584 et 600.
2. **Activer le pont CSS** : Coller le bloc de la section 2 à la fin de `_variables.css`.
3. **Poursuivre la standardisation Twig** : Utiliser `container`, `columns`, `column` et `text-center` sur les gabarits standards (comme déjà validé sur `ateliers.html.twig` et `cours.html.twig`).
4. **Purger les utilitaires obsolètes** : Supprimer `.p_centrer`, `.img-centre`, `.gras` et `.tresgras` de `_components.css` uniquement après avoir vérifié qu'aucun template ne les utilise encore.
