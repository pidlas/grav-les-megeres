# Configuration Grav et paramètres système

## Fichiers de configuration principaux

- [user/config/system.yaml](user/config/system.yaml) : comportement global du cœur Grav.
- [user/config/site.yaml](user/config/site.yaml) : métadonnées du site et valeurs de configuration métier.
- [user/config/security.yaml](user/config/security.yaml) : gestion de la sécurité et de l’évaluation du Twig dans le contenu.
- [user/config/media.yaml](user/config/media.yaml) : comportement des médias et des images.

## Points importants pour ce site

- Le Twig dans le contenu des pages est autorisé via la configuration de sécurité.
- Les pages migrées utilisent parfois des chemins relatifs et des URLs de base adaptées à l’arborescence Grav.
- Les fichiers de configuration sont prioritaires sur les réglages par défaut et doivent rester cohérents avec les templates personnalisés.

## Maintenance recommandée

- Après chaque changement de page ou de configuration, vider le cache avec :

```bash
bin/grav cache:clear
```

- Vérifier l’état des pages après une modification de structure ou de routes.

## Paramètres spécialement utiles pour ce site

- [user/config/plugins/shortcode-core.yaml](user/config/plugins/shortcode-core.yaml) : active les shortcodes et charge aussi le dossier personnalisé [user/custom/shortcodes](user/custom/shortcodes).
- [user/config/security.yaml](user/config/security.yaml) : permet l’évaluation du Twig dans le contenu des pages migrées.
- [user/config/site.yaml](user/config/site.yaml) : centralise les métadonnées générales du site.

## Procédures de dépannage

- Si un shortcode ne s’affiche plus, vérifier d’abord la configuration du plugin shortcode-core.
- Si une image ne se charge plus, confirmer la présence du fichier dans le dossier média de la page concernée.
- Si un rendu HTML semble cassé, vider le cache et vérifier si une ancienne version compilée reste en mémoire.
