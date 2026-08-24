# Thème et personnalisation

## Thème actif

Le site utilise un thème enfant basé sur Quark 2 : [user/themes/quark2-child](user/themes/quark2-child).

## Fichiers à surveiller

- [user/themes/quark2-child/css/style.css](user/themes/quark2-child/css/style.css) : styles spécifiques du site.
- [user/themes/quark2-child/templates](user/themes/quark2-child/templates) : templates Twig surchargés si nécessaire.
- [user/themes/quark2-child/blueprints](user/themes/quark2-child/blueprints) : configurations de présentation et de champs si le thème en a.

## Bonnes pratiques

- Centraliser les modifications de style dans le thème enfant plutôt que dans le thème parent.
- Conserver les règles spécifiques à une page dans le CSS du thème enfant, avec des sélecteurs ciblés.
- Documenter les changements de mise en page ici dès qu’une nouvelle section est ajoutée.

## Éléments déjà personnalisés

- Structure de la page d’accueil et de la page compagnie.
- Styles de grilles et d’images flottantes.
- Utilisation de Twig dans le contenu des pages lorsque cela est nécessaire.

## Règles de style importantes

- Les styles spécifiques au site sont centralisés dans [user/themes/quark2-child/css/style.css](user/themes/quark2-child/css/style.css).
- Les classes liées aux pages de contenu, comme `.g_compagnie`, `.g_co-item10` et `.forme-masque`, sont définies ici.
- Les images flottantes ou intégrées dans une mise en page spécifique doivent conserver leurs classes dédiées pour rester cohérentes entre les pages.

## Bonnes pratiques de maintenance

- Toujours préférer une classe dédiée à un style ponctuel inline.
- Si une page reçoit un nouveau layout, documenter les classes et leurs effets ici.
- Lorsque plusieurs pages partagent un même rendu, envisager une règle commune dans le thème enfant plutôt qu’une duplication locale.
