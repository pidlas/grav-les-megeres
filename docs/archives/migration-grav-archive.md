# Archive de migration Grav

Ce document conserve la trace historique de la migration du site PHP vers Grav.

## Résumé

- Le contenu a été migré vers [user/pages](user/pages).
- Les assets principaux ont été répartis dans les dossiers de contenu et de médias adaptés à Grav.
- La structure du site a été refaite autour d’une arborescence de pages hiérarchique.
- Les composants principaux du thème enfant ont été mis en place dans [user/themes/quark2-child](user/themes/quark2-child).

## Points techniques conservés

- Les routes internes ont été adaptées à l’arborescence Grav.
- Le Twig a été autorisé dans le contenu des pages via la configuration sécurité.
- Les images de contenu ont été progressivement intégrées au système de médias de Grav.
- Les shortcodes personnalisés sont désormais gérés via [user/custom/shortcodes](user/custom/shortcodes).

## Référence active

Pour la documentation courante, se reporter à :
- [docs/README.md](docs/README.md)
- [docs/quick-reference.md](docs/quick-reference.md)
