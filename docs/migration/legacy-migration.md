# Migration legacy et structure du contenu

## Contexte

Ce document résume les étapes de migration initiale du site PHP vers Grav et les décisions techniques qui restent pertinentes.

## Principaux points retenus

- Les actifs statiques ont été répartis dans les dossiers de contenu adaptés à Grav.
- Les pages ont été migrées sous [user/pages](user/pages) avec une structure hiérarchique propre.
- Les routes internes ont été adaptées pour fonctionner avec l’arborescence Grav.
- Les images et médias de contenu sont désormais gérés via le système de médias de Grav lorsque possible.

## Structure de base du contenu

- [user/pages/01.home](user/pages/01.home) : accueil.
- [user/pages/02.infos](user/pages/02.infos) : informations, compagnie, équipe, partenaires.
- [user/pages/03.spectacles](user/pages/03.spectacles) : spectacles et sous-pages associées.
- [user/pages/04.theatre-forum](user/pages/04.theatre-forum) : pages de présentation et laboratoire.
- [user/pages/05.prestations](user/pages/05.prestations) : prestations et règlement intérieur.
- [user/pages/06.contact](user/pages/06.contact) : contact.

## Maintenance à prévoir

- Garder les contenus et les assets alignés avec la hiérarchie réelle des pages.
- Documenter toute évolution de routage ou de nommage de fichiers ici.
- Utiliser les nouveaux documents thématiques pour éviter de surcharger un seul fichier de migration.
