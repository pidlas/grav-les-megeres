# 📘 DOC ADMIN : Migration des Images & Gestion des Médias sous Grav (v1.7+)

Ce guide technique documente les procédures d'intégration d'images optimisées (format `<picture>`, conversion WebP automatique, `srcset` dynamique et lazy-loading) lors de la migration d'un site HTML/CSS/JS statique vers un thème enfant Grav (ici `quark2-child`).

---

## 🛠️ CONFIGURATION ET INFRASTRUCTURE REQUISE

### 1. Droits d'accès et Système (CHMOD)
* Les scripts PHP personnalisés dans les dossiers du thème doivent impérativement être configurés en **`chmod 644`** (lecture pour tous, écriture pour le propriétaire).
* Les dossiers parents doivent être en **`chmod 755`**.

### 2. Configuration Globale de l'Analyseur (`user/config/system.yaml`)
Pour s'assurer que le contenu HTML brut et le traitement Twig soient autorisés globalement, vérifiez la présence de ces variables :
```yaml
pages:
  process:
    markdown: true
    twig: true
```

---

## 🚀 OPTION 1 : La Méthode Shortcode PHP (Recommandée)

Cette méthode permet d'insérer des composants personnalisés via les balises `[picture]` et `[img]` directement dans le texte Markdown, sans conflit avec le parseur de sécurité de Grav.

### A. Arborescence des fichiers à valider
📂 user/
├── 📂 config/
│   └── 📂 plugins/
│       └── 📄 shortcode-core.yaml
└── 📂 themes/
    └── 📂 quark2-child/
        ├── 📂 shortcodes/
        │   ├── 📄 PictureShortcode.php
        │   └── 📄 ImgShortcode.php
        └── 📂 templates/
            └── 📄 default.html.twig

### B. Étape 1 : Installation et configuration du Plugin
1. Installez l'extension requise via le terminal à la racine du projet :
   ```bash
   bin/gpm install shortcode-core
   ```
2. Créez ou modifiez le fichier de configuration `user/config/plugins/shortcode-core.yaml` en forçant l'utilisation de l'analyseur `regex` et en déclarant le dossier de votre thème :
   ```yaml
   enabled: true
   active: true
   parser: regex
   custom_shortcodes: 'user/themes/quark2-child/shortcodes'
   ```

### C. Étape 2 : Code du composant Image Responsive (`PictureShortcode.php`)
Créez le fichier `user/themes/quark2-child/shortcodes/PictureShortcode.php` :
```php
<?php
namespace Grav\Theme;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class PictureShortcode extends \Grav\Common\Shortcode\Shortcode
{
    public function init()
    {
        \(this->shortcode->add('picture', function(ShortcodeInterface\)sc) {
            \(filename =\)sc->getParameter('name');
            \(alt =\)sc->getParameter('alt', \(this->grav['page']->title());\)page = \(this->grav['page'];\)image = \(page->media()[\)filename] ?? null;

            if (!\$image) {
                return "<!-- ❌ Image manquante dans le dossier de la page : '\$filename' -->";
            }

            \(webpSrcset =\)image->derivatives(400, 1200, 400)->format('webp')->srcset();
            \(jpegSrcset =\)image->derivatives(400, 1200, 400)->srcset();
            \(fallbackUrl =\)image->resize(1200)->url();

            return "<picture>
                <source type='image/webp' srcset='{\(webpSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'> <source type='image/jpeg' srcset='{\)jpegSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <img src='{\(fallbackUrl}' alt='{\)alt}' loading='lazy'>
            </picture>";
        });
    }
}
```

### D. Étape 3 : Code du composant Image Simple (`ImgShortcode.php`)
Créez le fichier `user/themes/quark2-child/shortcodes/ImgShortcode.php` pour remplacer vos anciennes balises `<img>` standards :
```php
<?php
namespace Grav\Theme;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImgShortcode extends \Grav\Common\Shortcode\Shortcode
{
    public function init()
    {
        \$this->shortcode->add('img', function(ShortcodeInterface \(sc) {\)filename = \(sc->getParameter('name');\)alt = \(sc->getParameter('alt',\)this->grav['page']->title());
            \(class =\)sc->getParameter('class', ''); 

            \$page = \(this->grav['page'];\)image = \(page->media()[\)filename] ?? null;

            if (!\$image) {
                return "<!-- ❌ Image simple manquante dans le dossier de la page : '\$filename' -->";
            }

            \$optimizedUrl = \(image->resize(800)->url();\)classAttr = !empty(\(class) ? " class='{\)class}'" : "";

            return "<img src='{\$optimizedUrl}' alt='{\(alt}'{\)classAttr} loading='lazy'>";
        });
    }
}
```

### E. Étape 4 : Syntaxe d'utilisation dans les fichiers Markdown (`.md`)
Les images sources doivent être stockées directement à la racine du dossier de la page (ex: `user/pages/02.infos/02.compagnie/co_dossier-prez.jpg`).

**⚠️ RÈGLE DE SÉCURITÉ PARSEDOWN :** Pour éviter que le texte brut du shortcode ne s'affiche, vous devez impérativement insérer **une ligne totalement vide avant et après chaque appel de shortcode**, et ne jamais l'entourer de balises HTML bloquantes (`<div>`, etc.).

```markdown
---
title: Compagnie
---

Voici un paragraphe descriptif rédigé en Markdown pur.

[picture name="co_dossier-prez.jpg" alt="Le théâtre d'ombres - Grésilhette et la Salimonde"]

Voici un autre paragraphe intermédiaire.

[img name="petite-photo.jpg" alt="Une illustration simple"]
```

---

## 🛠️ LOGIQUE DE DÉPANNAGE (DEBUG CRITIQUE)

Si le texte `[picture ...]` s'affiche encore en texte brut à l'écran, appliquez les trois vérifications suivantes dans l'ordre :

### Vérification A : L'extension du fichier Markdown (Multi-langue)
Si le site n'est pas nativement configuré en mode multilingue strict, l'extension `.fr.md` bloque l'interprétation des scripts de plugins. 
* **Action :** Renommez `default.fr.md` en **`default.md`**.

### Vérification B : Alternative par Shortcode Twig (Sans PHP)
Si l'analyseur PHP du thème enfant est rejeté par les restrictions du serveur d'hébergement, basculez sur l'arborescence des templates Twig native de Shortcode Core :
1. Supprimez les fichiers PHP `PictureShortcode.php` et `ImgShortcode.php`.
2. Créez à la place le fichier template : `user/themes/quark2-child/templates/shortcodes/picture.html.twig`.
3. Intégrez-y ce code :
   ```twig
   {% set filename = params.name %}
   {% set alt = params.alt | default(page.title) %}
   {% set image = page.media[filename] %}
   {% if image %}
   <picture>
       <source type="image/webp" srcset="{{ image.derivatives(400, 1200, 400).format('webp').srcset }}" sizes="(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw">
       <source type="image/jpeg" srcset="{{ image.derivatives(400, 1200, 400).srcset }}" sizes="(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw">
       <img src="{{ image.resize(1200).url }}" alt="{{ alt }}" loading="lazy">
   </picture>
   {% endif %}
   ```

### Vérification C : La Solution de Secours Ultime (Markdown Natif)
Si le plugin Shortcode Core reste inactif ou bloqué sur votre architecture, utilisez l'API de média native de Grav. Elle ne requiert aucun plugin ni fichier de template et génère exactement le même code HTML `<picture>` optimisé :
* **Syntaxe à copier dans le `.md` :**
  ```markdown
  ![Le théâtre d'ombres](co_dossier-prez.jpg?derivatives=&sizes=(max-width:400px)+100vw,(max-width:800px)+50vw,33vw)
  ```

---

## 🧹 MAINTENANCE ADMINISTRATIVE : PURGE DU CACHE
À chaque modification de la structure de vos fichiers PHP ou de la configuration d'un plugin YAML, la table des caches de Grav doit être purgée en exécutant la commande CLI suivante à la racine :
```bash
bin/grav clear-cache
```