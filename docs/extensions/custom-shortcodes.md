# Extensions et shortcodes personnalisés

## Shortcode Core

Le plugin shortcode-core est utilisé pour enrichir le contenu Markdown/HTML avec des balises spécialisées.

### Configuration

La configuration du plugin se trouve dans [user/config/plugins/shortcode-core.yaml](user/config/plugins/shortcode-core.yaml).

### Dossier de shortcodes personnalisés

Les shortcodes personnalisés sont désormais conservés dans : [user/custom/shortcodes](user/custom/shortcodes).

Cette approche permet de préserver les extensions même en cas de mise à jour du plugin.

## Shortcodes actuellement utilisés

- [picture] : shortcode natif du plugin pour produire des balises `<picture>` à partir d’un média de page.
- [img] : shortcode personnalisé pour résoudre une image à partir du dossier média de la page et conserver les attributs HTML utiles.

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
