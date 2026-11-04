# Documentation Administrateur : Gabarit Partenaires Universel Spectre
**Domaine :** Thème Enfant `quark2-child` / CMS Grav 2.0+
**Composant :** Flex-Objects `partenaires`

---

## 1. Refonte du Gabarit Sémantique (`partenaires.html.twig`)
Le template a été intégralement purgé de ses structures de listes rigides (`<ul>`/`<li>`) et de ses attributs de styles en dur (`style=""`) pour adopter la grille Flexbox fluide du framework Spectre.css.

*   **Optimisation UX (Lien unique) :** L'ancien double lien (sur l'image ET sur le bouton) a été supprimé. L'image du logo est désormais une illustration neutre, isolant le bouton comme unique déclencheur d'action étanche, ce qui stabilise définitivement le DOM.
*   **Encapsulation Typographique :** Le contenu éditorial des rédacteurs (`{{ page.content | raw }}`) a été sanctuarisé dans un conteneur typographique standardisé Spectre (`.container .bg-sacred-white`).

## 2. Architecture et Blindage Géométrique (`_components.css`)
Pour interdire l'effondrement des logos XXL (DRAC, Aude, Carcassonne Agglo) et garantir les espacements du design d'origine, le pilotage a été confié à des règles Flexbox étanches :

*   **Principe du Bouclier Invisible :** La classe `.col-partenaire` applique un `padding: 0.6rem` invisible qui fait office de zone tampon pour que les cadres ne se touchent jamais.
*   **Autonomie du Bouton :** La classe `.partenaire-btn-pro` a été enrichie de ses propres attributs de couleur de marque (`--color-brand-primary`) et de comportement pour ne plus dépendre des classes natives de Spectre.
*   **Verrouillage PC (≥ 960px) :** L'utilitaire `flex-wrap: nowrap !important` associé à un calcul de division strict (`max-width: calc(100% / 7)`) contraint le navigateur à aligner les 7 fiches sur une seule ligne horizontale parfaite.

## 3. Synthèse des Classes Cibles Activées
*   `.g-partenaires-top` : Applique la grande respiration haute système `var(--space-l)`.
*   `.g-partenaires-container` : Déploie le conteneur de grille Flexbox adaptatif.
*   `.partenaire-carte-pro` : Dessine l'encadré de la fiche avec sa bordure et son fond blanc.
*   `.partenaire-btn-pro` : Propulse le bouton orange officiel centré en bas de carte.