# Documentation Technique — Thème Autonome `humus` & Pont Spectre.css (Grav 2.0)

## 1. Architecture du Thème `humus`

Le thème **`humus`** (`user/themes/humus/`) est un thème autonome complet fonctionnant sans dépendance à `quark2` ou `quark2-child`.

### Arborescence principale
* `user/themes/humus/humus.yaml` : Configuration du thème (`enabled: true`, chemin du favicon).
* `user/themes/humus/humus.php` : Enregistrement des événements Grav (`onShortcodeHandlers`, `onTwigTemplatePaths`).
* `user/themes/humus/css/` :
  * `spectre.min.css` & `spectre-exp.min.css` & `spectre-icons.min.css` : Framework CSS Spectre v0.5.9.
  * `_variables.css` : Variables CSS globales, charte graphique et **bloc de pont Spectre.css**.
  * `_layout.css` : Structure générale des pages et conteneurs.
  * `_components.css` : Styles des composants métier (cartes d'accueil, lecteur audio/vidéo, formes ovales, etc.).
* `user/themes/humus/templates/` :
  * `main-layout.html.twig` : Gabarit maître (`<head>` SEO/Schema.org, chargement CSS/JS, décors globaux).
  * `partials/header.html.twig` & `partials/footer.html.twig` : En-tête (logo, navigation) et pied de page.
  * `macros/media.html.twig` : Macro Twig réutilisable pour l'affichage standardisé des médias Flex Objects.
  * Templates métier : `accueil.html.twig`, `ateliers.html.twig`, `compagnie.html.twig`, `contact.html.twig`, `equipe.html.twig`, `gresilhette.html.twig`, `le-spectacle.html.twig`, `partenaires.html.twig`, `stages.html.twig`, `ta-grand-mere.html.twig`, etc.
* `user/themes/humus/shortcodes/` :
  * `ImgShortcode.php` (`[img]`) : Images responsives multi-formats (AVIF/WebP/JPEG) pour médias de pages Markdown (`page://` et `parent://`).
  * `MylinkShortcode.php` (`[a]`) : Liens intelligents typés (`internal`, `external`, `file`, `mailto` anti-spam Base64, `tel`).
  * `PShortcode.php` (`[p]`) : Paragraphes stylisés dans le Markdown.
  * `PictureShortcode.php` (`[picture]`) : Ancienne version historique de `[img]`.
* `user/themes/humus/images/` : Uniquement les assets techniques du thème (`favicon/*`).

---

## 2. Centralisation des Médias dans `user/data/flex-objects/`

Toutes les images éditoriales et décoratives du site sont centralisées dans `user/data/flex-objects/` afin d'être administrables via **Admin 2** :
* **Identité & décors globaux (`user/data/flex-objects/accueil/`)** :
  * `logo.png` : Logo officiel (en-tête, OpenGraph et Schema.org).
  * `i_champignons.svg` & `i_feuilles.svg` : Illustrations décoratives globales appelées dans `main-layout.html.twig`.
* **Règle Nginx obligatoire** : Pour autoriser l'affichage direct des fichiers `.svg` stockés dans `user/data/`, la règle `location` du vhost Nginx doit inclure `svg` :
  ```nginx
  location ~* /user/data/.*\.(jpe?g|png|gif|webp|svg|avif|bmp|ico|mp4|webm|ogg|ogv|mov|mp3|wav|m4a|flac|pdf)$ {
      try_files $uri =404;
  }
  ```

---

## 3. Pont CSS entre `humus` et `Spectre.css v0.5.9` (`_variables.css`)

Le bloc de pont situé dans `user/themes/humus/css/_variables.css` neutralise les conflits entre le reset/composants de Spectre.css et la charte graphique `humus` :

```css
/* ==========================================================================
   PONT DE COMPATIBILITÉ : HUMUS <-> SPECTRE.CSS v0.5.9
   ========================================================================== */

/* 1. Préserve la typographie et les tailles de base du thème Humus */
html {
    font-size: 16px;
}

body {
    font-family: var(--font-corps, sans-serif);
    color: var(--couleur-texte, #222);
    background-color: var(--couleur-fond, #fff);
    line-height: 1.6;
}

/* 2. Rétablit l'alignement vertical en bas des cartes d'accueil (au repos ET sélectionnée) */
.button-container .card,
.button-container input:checked + label.card {
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    border: 0;
}

/* 3. Empêche Spectre de déformer les images et figures */
img {
    max-width: 100%;
    height: auto;
}

figure {
    margin: 0;
}
```

### Checklist pour le nettoyage futur de `_components.css`
1. **Conserver intactes** toutes les classes métier spécifiques (`forme-lampe`, `forme-masque`, `forme-erinyes`, `forme-montage`, `button-container`, `morph`, `fade-zoom`, lecteur audio/vidéo personnalisé).
2. **Supprimer progressivement** les anciennes règles de grille maison (`g_bio`, `g_partenaires`, `g_art-tech`) uniquement au fur et à mesure qu'elles sont remplacées par `columns` / `col-*` de Spectre.css dans les templates.
3. **Éviter les grilles Flexbox (`columns`)** autour des éléments utilisant `float` + `shape-outside` (comme `.forme-lampe` dans `ta-grand-mere.html.twig`) ; privilégier un conteneur fluide avec `.clearfix`.

---

## 4. Guide de Mise en Place : Macro Twig `media.flex_img()`

Pour remplacer les balises `<img>` répétitives dans les templates Twig sans alourdir le rendu serveur (contrairement au shortcode `[img]` réservé aux pages Markdown), utiliser la macro dédiée `templates/macros/media.html.twig`.

### Étape 1 : Créer le fichier `user/themes/humus/templates/macros/media.html.twig`

```twig
{# user/themes/humus/templates/macros/media.html.twig #}
{% macro flex_img(dossier, fichier, alt='', class='', options={}) %}
    {% if fichier is not empty %}
        {# 1. Résolution automatique du chemin (nom de fichier seul ou chemin complet) #}
        {% set src = (fichier starts with 'user/' or fichier starts with '/')
            ? base_url_simple ~ '/' ~ (fichier|trim('/'))
            : base_url_simple ~ '/user/data/flex-objects/' ~ dossier ~ '/' ~ fichier %}

        {# 2. Options de l'image #}
        {% set lazy = options.lazy ?? true %}
        {% set width = options.width ?? '' %}
        {% set height = options.height ?? '' %}
        {% set onload = options.onload ?? '' %}

        {# 3. Options de légende (<figure>) #}
        {% set caption = options.caption ?? '' %}
        {% set caption_class = options.caption_class ?? 'p_centrer legende' %}

        {# 4. Options de lien (<a>) #}
        {% set link = options.link ?? '' %}
        {% set link_class = options.link_class ?? '' %}
        {% set link_title = options.link_title ?? '' %}
        {% set target = options.target ?? '' %}
        {% set rel = options.rel ?? (target == '_blank' ? 'noopener noreferrer' : '') %}

        {% if caption is not empty %}<figure>{% endif %}

        {% if link is not empty %}
            <a href="{{ link }}"
               {% if link_class is not empty %}class="{{ link_class }}"{% endif %}
               {% if link_title is not empty %}title="{{ link_title|e }}"{% endif %}
               {% if target is not empty %}target="{{ target }}"{% endif %}
               {% if rel is not empty %}rel="{{ rel }}"{% endif %}>
        {% endif %}

        <img src="{{ src }}"
             alt="{{ alt|e }}"
             {% if class is not empty %}class="{{ class }}"{% endif %}
             {% if width is not empty %}width="{{ width }}"{% endif %}
             {% if height is not empty %}height="{{ height }}"{% endif %}
             {% if onload is not empty %}onload="{{ onload }}"{% endif %}
             loading="{{ lazy ? 'lazy' : 'eager' }}">

        {% if link is not empty %}</a>{% endif %}

        {% if caption is not empty %}
            <figcaption class="{{ caption_class }}">{{ caption|raw }}</figcaption>
        </figure>
        {% endif %}
    {% endif %}
{% endmacro %}
```

### Étape 2 : Importer la macro en haut de chaque template concerné

Ajouter cette ligne au début du template (juste après `{% extends 'main-layout.html.twig' %}`) :

```twig
{% import 'macros/media.html.twig' as media %}
```

*(Note : dans Twig, un `{% import %}` effectué dans `main-layout.html.twig` n'est pas hérité automatiquement par les templates enfants ; il faut donc déclarer `{% import 'macros/media.html.twig' as media %}` dans chaque template qui appelle `media.flex_img(...)`).*

### Étape 3 : Référence complète des paramètres de `media.flex_img()`

| Paramètre               | Type      | Défaut                              | Rôle |
| :---                    | :---      | :---                                | :--- |
| `dossier`               | `string`  | *(requis)*                          | Nom du sous-dossier dans `user/data/flex-objects/` (ex. `'equipe'`, `'accueil'`). |
| `fichier`               | `string`  | *(requis)*                          | Nom du fichier (ex. `'photo.avif'`) ou chemin complet commençant par `user/` ou `/`. |
| `alt`                   | `string`  | `''`                                | Texte alternatif échappé automatiquement (`alt|e`). |
| `class`                 | `string`  | `''`                                | Classes CSS appliquées à la balise `<img>`. |
| `options.lazy`          | `bool`    | `true`                              | `true` génère `loading="lazy"`, `false` génère `loading="eager"`. |
| `options.width`         | `int/str` | `''`                                | Attribut HTML `width` (évite les sauts de mise en page CLS). |
| `options.height`        | `int/str` | `''`                                | Attribut HTML `height`. |
| `options.onload`        | `string`  | `''`                                | Script inline `onload` (ex. `"this.classList.add('loaded')"`). |
| `options.caption`       | `string`  | `''`                                | Si renseigné, entoure l'image de `<figure>` et ajoute `<figcaption>`. |
| `options.caption_class` | `string`  | `'p_centrer legende'`               | Classe CSS appliquée à `<figcaption>`. |
| `options.link`          | `string`  | `''`                                | Si renseigné, entoure l'image d'un lien `<a href="...">`. |
| `options.link_class`    | `string`  | `''`                                | Classe CSS appliquée à la balise `<a>`. |
| `options.link_title`    | `string`  | `''`                                | Attribut `title` sur la balise `<a>`. |
| `options.target`        | `string`  | `''`                                | Attribut `target` (ex. `'_blank'`). |
| `options.rel`           | `string`  | `'noopener noreferrer'` si `_blank` | Attribut `rel` du lien (surchargeable librement, ex. `'nofollow'`). |

### Étape 4 : Exemples de migration selon vos cas d'usage

1. **Logo de l'en-tête (`partials/header.html.twig`)** :
   ```twig
   {{ media.flex_img('accueil', logo_site, "Le logo des Mégères de l'Humus réalisé par Noam", '', {
       width: 230,
       height: 230,
       lazy: false,
       link: base_url_simple ~ '/'
   }) }}
   ```

2. **Image simple avec classe (`ateliers.html.twig`, `ta-grand-mere.html.twig`)** :
   ```twig
   {{ media.flex_img('ateliers', donnees_page.photo, 'Un atelier de théâtre', 'photo-stages-ateliers') }}
   ```

3. **Photo biographie avec `onload` (`equipe.html.twig`)** :
   ```twig
   {{ media.flex_img('equipe', membre.photo_rigolote, membre.nom, 'fade-zoom', {
       onload: "this.classList.add('loaded')"
   }) }}
   ```

4. **Portrait cliquable (`equipe.html.twig`)** :
   ```twig
   {{ media.flex_img('equipe', membre.photo_jolie, membre.nom, '', {
       link: base_url_simple ~ '/equipe/' ~ membre.slug
   }) }}
   ```

5. **Image avec légende (`compagnie.html.twig`)** :
   ```twig
   {{ media.flex_img('compagnie', donnees.photo_humus, "L'humus, peinture stylisée", '', {
       caption: donnees.legende_humus
   }) }}
   ```

6. **Bouton PDF ou lien externe sécurisé (`gresilhette.html.twig`)** :
   ```twig
   {{ media.flex_img('gresilhette', 'btn-prez.avif', 'Dossier artistique', '', {
       width: 300,
       height: 66,
       link: base_url_simple ~ '/user/data/flex-objects/gresilhette/dossier-prez.pdf',
       link_title: 'Visualiser le dossier artistique',
       target: '_blank',
       rel: 'noopener noreferrer'
   }) }}
   ```

### Étape 5 : Vérification finale après migration
* Vider le cache Twig de Grav (`bin/grav clearcache`) afin que le nouveau dossier `templates/macros/` soit indexé.
* Inspecter le code source HTML généré sur une page de chaque type (logo, portrait `onload`, légende `<figure>`, lien PDF `target="_blank"`) pour confirmer la conformité des attributs.
