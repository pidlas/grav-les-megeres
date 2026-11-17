# Extensions et shortcodes personnalisés

## Shortcode Core

Le plugin shortcode-core est utilisé pour enrichir le contenu Markdown/HTML avec des balises spécialisées.

### Configuration

La configuration du plugin se trouve dans [user/config/plugins/shortcode-core.yaml](user/config/plugins/shortcode-core.yaml).

### Dossier de shortcodes personnalisés

Les shortcodes personnalisés sont désormais conservés dans : [user/custom/shortcodes](user/custom/shortcodes).

Cette approche permet de préserver les extensions même en cas de mise à jour du plugin.

## Shortcodes actuellement utilisés

- [img] : ImgShortcode.php - shortcode personnalisé pour résoudre une image à partir du dossier média de la page et conserver les attributs HTML utiles.
- [a] : MylinkShortcode.php - shortcode personnalisé pour détecter atomatiquement le type de lien.
- [picture] : PictureShortcode.php - shortcode natif du plugin pour produire des balises `<picture>` à partir d’un média de page. 
- [p] : PShortcode.php

### Rôle et fonctionnement des shortcodes du thème (`user/themes/humus/shortcodes/`)

#### 1. `[img]` (`ImgShortcode.php`) — Images responsives intelligentes avec support `parent://`
* **Syntaxe** : `[img name="photo.avif" alt="..." class="ma-classe" lazy="true|false"]`
* **Fonctionnement** :
  * Recherche l'image dans les médias de la page courante, ou dans le dossier de la **page parente** si le nom commence par `parent://`.
  * Génère automatiquement des variantes responsives (`400px`, `800px`, `1200px`) aux formats **AVIF**, **WebP** et **JPEG** dans une balise `<picture>`.
  * Intègre une **protection anti-crash** pour les fichiers sources déjà au format `.avif` (évite les erreurs de conversion JPEG fantôme de la bibliothèque graphique).
  * Applique `class="wrapper-<classe>"` sur `<picture>`, `class="<classe>"` sur `<img>` et gère le chargement différé (`loading="lazy"` par défaut ou `eager` si `lazy="false"`).

#### 2. `[a]` (`MylinkShortcode.php`) — Liens enrichis, typés et anti-spam
* **Syntaxe** : `[a url="https://..." class="..." alt="..." target="_blank"]Texte du lien[/a]`
* **Fonctionnement** :
  * **Détection automatique du type de lien (`data-type`)** : classe le lien en `mailto`, `tel`, `file` (PDF, MP3, MP4, images, archives…), `external` ou `internal`.
  * **Adaptation production vs local** : ajoute automatiquement le préfixe `/grav/` aux URL relatives lorsque le site tourne sur le domaine de production `grav.lesmegeresdelhumus.fr`.
  * **Icônes visuelles automatiques** : ajoute après le lien un `<span class="lien-e-seul"></span>` (externe), `<span class="lien-f-seul"></span>` (fichier) ou `<span class="lien-i-seul"></span>` (interne / `/contact`).
  * **Sécurité et anti-spam** : ajoute `target="_blank" rel="noopener noreferrer"` sur les liens externes et fichiers. Pour les adresses `mailto:`, encode l'adresse en **Base64** dans `data-secure`, obfusque l'affichage texte en entités HTML aléatoires et injecte un script JS qui ne décode l'adresse qu'au survol ou au clic.

#### 3. `[picture]` (`PictureShortcode.php`) — Ancienne version de `[img]` (Doublon historique)
* **Syntaxe** : `[picture name="photo.jpg" alt="..." class="..."]`
* **Fonctionnement** :
  * Génère une balise `<picture>` avec variantes **AVIF / WebP / JPEG** (`400px` à `1200px`) uniquement depuis la page courante.
  * **Différence avec `[img]`** : `[picture]` est l'ancêtre de `[img]`. Il ne gère ni le préfixe `parent://`, ni l'option `lazy="false"`, et sa gestion des sources `.avif` tente encore une conversion `.format('jpg')` (alors que `[img]` est plus robuste et complet).

#### 4. `[p]` (`PShortcode.php`) — Paragraphe stylisé dans le Markdown
* **Syntaxe** : `[p class="ma-classe"]Texte du paragraphe[/p]`
* **Fonctionnement** :
  * Permet d'entourer un bloc de texte rédigé en Markdown d'une balise `<p class="ma-classe">...</p>` sans avoir à écrire du HTML brut dans les contenus éditoriaux.

Le correctif retenu consiste à laisser le shortcode natif du plugin ne rien enregistrer pour le nom [img], afin que le shortcode personnalisé de [user/custom/shortcodes/ImgShortcode.php](user/custom/shortcodes/ImgShortcode.php) prenne le relais. Cette approche a permis de réafficher correctement les images de la page compagnie après la modification du shortcode.

## Emplacement de maintenance

En cas d’ajout d’un nouveau shortcode, créer un nouveau fichier PHP dans [user/custom/shortcodes](user/custom/shortcodes) et l’enregistrer avec un nom de classe dédié.

## Pages déjà impactées

Le shortcode [img] est actuellement utilisé sur la page compagnie : [user/pages/02.infos/02.compagnie/default.fr.md](user/pages/02.infos/02.compagnie/default.fr.md).

Les images concernées sont :
- rapport 2024,
- rapport 2025,
- image de la page “Les Érynies”,
- image “Les Érynies et Oreste”,
- image “L’humus”.

## Règles d’usage recommandées

- Utiliser [img] pour les images de contenu liées à une page.
- Garder [picture] pour les cas où une vraie génération de sources responsive est nécessaire.
- Éviter la duplication de chemins en dur dans le contenu Markdown/HTML.