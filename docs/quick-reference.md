# Référence rapide

## Modifier le contenu d’une page

- Page de contenu : [user/pages](user/pages)
- Exemple de page compagnie : [user/pages/02.infos/02.compagnie/default.fr.md](user/pages/02.infos/02.compagnie/default.fr.md)

## Modifier le style du site

- Styles du thème enfant : [user/themes/quark2-child/css/style.css](user/themes/quark2-child/css/style.css)
- Documentation liée : [docs/theme/theme-customization.md](docs/theme/theme-customization.md)

## Modifier la configuration Grav

- Configuration système : [user/config/system.yaml](user/config/system.yaml)
- Configuration sécurité : [user/config/security.yaml](user/config/security.yaml)
- Configuration site : [user/config/site.yaml](user/config/site.yaml)
- Documentation liée : [docs/system/grav-configuration.md](docs/system/grav-configuration.md)

## Ajouter ou modifier un shortcode

- Shortcodes personnalisés : [user/custom/shortcodes](user/custom/shortcodes)
- Exemple de shortcode image : [user/custom/shortcodes/ImgShortcode.php](user/custom/shortcodes/ImgShortcode.php)
- Configuration du plugin : [user/config/plugins/shortcode-core.yaml](user/config/plugins/shortcode-core.yaml)
- Documentation liée : [docs/extensions/custom-shortcodes.md](docs/extensions/custom-shortcodes.md)

## Dépanner un rendu ou un asset

- Vider le cache :

```bash
bin/grav cache:clear
```

- Vérifier le rendu local via nginx (URL habituelle du site) :

```bash
curl -sL http://grav.localhost/infos/compagnie | grep -i "<img\|rapport2024\|rapport2025\|co_les-erynies"
```

- Vérifier les fichiers de page : [user/pages](user/pages)
- Vérifier les médias de page : [user/pages](user/pages)

## Maintenance du contenu

Pour garder les pages Grav propres et portables, utiliser les scripts suivants :

- Normaliser les liens internes vers des URL plus robustes :

```bash
python3 scripts/normalize_links.py
python3 scripts/normalize_links.py --apply
```

- Normaliser le formatage du contenu de façon conservative :

```bash
python3 scripts/normalize_page_format.py
python3 scripts/normalize_page_format.py --apply
```

## Déploiement et permissions

Sur les environnements dev/prod, conserver les permissions suivantes :
- répertoires : 2750 (drwsr-s---)
- fichiers : 640
- propriétaire/groupe : grav:www-data

Le script de déploiement dans [scripts/deploy.sh](scripts/deploy.sh) applique cette convention automatiquement.
