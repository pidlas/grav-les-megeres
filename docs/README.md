# Documentation Grav du site Les Mégères de l'Humus

Cette documentation remplace progressivement l’ancien document unique de migration et regroupe les informations par thématique pour faciliter la maintenance du site.

## Sommaire

- [Thème et personnalisation](docs/theme/theme-customization.md)
- [Configuration système et sécurité](docs/system/grav-configuration.md)
- [Extensions et shortcodes](docs/extensions/custom-shortcodes.md)
- [Migration et structure legacy](docs/migration/legacy-migration.md)
- [Référence rapide](docs/quick-reference.md)

## Références rapides

- Thème enfant principal : [user/themes/quark2-child](user/themes/quark2-child)
- Configuration utilisateur : [user/config](user/config)
- Pages de contenu : [user/pages](user/pages)
- Shortcodes personnalisés : [user/custom/shortcodes](user/custom/shortcodes)
- Environnement local : le site est servi par nginx sur la base locale, notamment via l’URL grav.localhost

## Déploiement et permissions

Les déploiements vers les environnements dev/prod doivent conserver les permissions de type web suivantes :
- répertoires : 2750 (drwsr-s---)
- fichiers : 640
- propriétaire/groupe : grav:www-data

Le script de déploiement dans [scripts/deploy.sh](scripts/deploy.sh) applique cette convention automatiquement.

## Maintenance du contenu

Deux scripts d’aide ont été ajoutés pour garder les pages Grav propres et portables :
- [scripts/normalize_links.py](scripts/normalize_links.py) remplace les liens internes codés en dur par des liens Twig basés sur base_url_relative.
- [scripts/normalize_page_format.py](scripts/normalize_page_format.py) normalise le formatage du contenu Markdown/HTML de façon conservative, sans toucher au frontmatter YAML ni aux blocs de code.

Ces scripts peuvent être lancés en prévisualisation puis en mode application selon les besoins.
