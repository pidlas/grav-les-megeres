# Mémoire Technique & Guide d'Architecture : Centralisation Flex-Objects & Sécurisation de Production

**Compagnie Les Mégères de l'Humus**  
*Ce document retrace l'intégralité des séances de débogage, les anomalies d'infrastructure résolues et les choix architecturaux validés pour sécuriser la coexistence entre le CMS Grav et le serveur de production YunoHost.*

---

## I. L'Énigme Fondamentale : Le Conflit d'Alias Nginx & Le Piège du SSOwat

### 1. Le Symptôme d'origine
Lors du passage des biographies de l'Équipe en système virtuel via Flex-Objects, le fait de cliquer sur la vignette d'un artiste ou de charger son image de biographie provoquait une déconnexion instantanée du site et renvoyait l'utilisateur vers la mire de connexion du SSO (SSOwat) de YunoHost. En local, le comportement était pourtant parfait.

### 2. Le Diagnostic de l'infrastructure Nginx
L'analyse profonde du fichier `grav.conf` du serveur YunoHost a révélé un bug architectural classique de Nginx lié à l'utilisation de la directive `alias`. 
Dans la configuration standard du paquet YunoHost, le bloc de sécurité gérant l'accès au dossier de données était imbriqué à l'intérieur de la directive principale :
```nginx
location /grav/ {
    alias /var/www/grav/;
    ...
    location ~* ^/grav/user/data/.*\$ { ... }
}
```
En architecture Nginx, lorsqu'un bloc d'expression régulière (`~*`) est imbriqué sous un bloc utilisant un `alias`, le moteur de réécriture calcule mal le chemin sur le disque en y injectant à tort la variable globale `$uri`. Nginx levait une erreur 404 invisible, et le pare-feu SSOwat, voyant passer une requête en échec hors du périmètre applicatif, interceptait le navigateur pour le déconnecter.

### 3. La Solution sémantique : La Désimbriquer au premier niveau
Nous avons extrait la règle de sécurité pour en faire un bloc autonome, positionné au tout premier niveau de configuration du serveur, déconnecté de l'alias :
```nginx
location ~* ^/grav/user/data/.*\.(jpe?g|png|gif|webp|avif|bmp|ico|mp4|webm|ogg|ogv|mov|mp3|wav|m4a|flac|pdf)\$ {
    root /var/www;
    try_files \$uri =404;
    access_log off;
    expires 30d;
}
```
En orientant la racine physique (`root`) sur `/var/www`, Nginx calcule désormais au millimètre près l'accès direct aux fichiers statiques (`/var/www/grav/user/data/...`), court-circuitant définitivement les interceptions du SSOwat.

---

## II. Le Mystère du `/fr/` Parasite & La Portabilité des URL

### 1. L'Anomalie linguistique locale
Lors de l'assemblage des chemins d'images à l'aide de la variable globale système `base_url`, un comportement asymétrique est apparu : en local, l'URL des deux premières fondatrices (Suzie et Sabine) se voyait injecter un préfixe linguistique fantôme `/fr/` (`src="/fr/user/data/..."`), rendant leurs images invisibles, tandis que les autres comédiens restaient épargnés.

### 2. Le mécanisme interne de Grav Multilang
Dans Grav, la variable `base_url` (ainsi que sa variante `base_url_relative`) est dynamique et surveille l'état de la session linguistique du navigateur. Si le système détecte un cookie d'administration actif ou indexe une collection Flex au sein d'une page multilingue, il force l'injection de la langue courante pour sécuriser la navigation. Ce comportement, excellent pour des liens hypertextes, est destructeur pour des fichiers physiques sur le disque dur, car le dossier `/fr/` n'existe pas dans l'arborescence Linux.

### 3. Le Verrouillage par `base_url_simple` et `page.url`
* **Pour les Images (`<img>`) :** Nous avons banni l'usage de `base_url` au profit de la variable isolée du système de langue : **`base_url_simple`**. Elle garantit le renvoi d'une chaîne vide en local (préservant le chemin relatif direct) et renvoie strictement `"/grav"` sur le serveur.
* **Pour les Liens (`<a>`) :** Contrairement aux images, un lien virtuel a besoin du contexte linguistique pour aiguiller Grav. Nous avons supprimé les routes codées en dur (`/infos/equipe/`) issues de l'ancienne arborescence pour utiliser la propriété sémantique de l'objet courant : **`page.url`**. Elle calcule nativement le sous-dossier serveur et la langue sans aucune maintenance manuelle.
---

## III. Anatomie des Formulaires Flex : filepicker vs file

Le basculement des données de la page Actualités a mis en lumière une divergence fondamentale de traitement des variables par le moteur Flex-Objects.

### 1. La nature des données stockées
* **Modèle Équipe / Grésilhette (filepicker) :** Le formulaire enregistre en base de données une chaîne de texte pure contenant le nom brut du fichier (`photo.avif`). Le template Twig peut alors faire une concaténation textuelle classique.
* **Modèle Actualités (file) :** Le formulaire enregistre un tableau associatif complexe de métadonnées (nom, taille, type, chemin). 

### 2. Le crash "Array to string conversion" et sa résolution
Lorsque le blueprint des Actualités a été modernisé pour utiliser le type `file`, le fichier YAML a commencé à stocker ces tableaux associatifs. Tenter de faire une concaténation directe sur un tableau a provoqué le plantage immédiat de Twig. 
De plus, au cœur des boucles de listes imbriquées (`type: list` pour les blocs d'articles), Grav peut perdre le typage fin de l'objet Flex, rendant impossible l'accès direct aux sous-propriétés comme `.path`.

La solution universelle et définitive consiste à utiliser le filtre Twig **`|keys|first`**. Il intercepte directement la clé principale du tableau, laquelle contient textuellement le chemin physique complet du fichier sur le disque :
```twig
{% set path_mini = bloc.miniature|keys|first %}
<img src="{{ base_url_simple ~ '/' ~ path_mini }}">
```

### 3. Le piège du double user/
La directive `destination:` du champ `file` s'exécute de manière relative à l'intérieur du dossier `user/` de Grav. Écrire `destination: 'user/data/...'` crée mécaniquement un doublon en base de données (`user/user/data/...`). Il faut impérativement omettre le premier dossier et écrire : `destination: 'data/flex-objects/actualites'`.

---

## IV. La Bataille du Déploiement : Exclusions vs Protection Rsync

### 1. Le bug du masquage total (exclude)
Lors de la mise en place de l'option de nettoyage de production `--allow-delete`, l'ajout de filtres globaux d'exclusion comme `"data/flex-objects/**/*.avif"` a provoqué un comportement hautement contre-intuitif : `rsync` refusait de transférer les nouvelles images locales vers le serveur, même si le dossier distant était totalement vide. 
Pour `rsync`, une exclusion masque le fichier dès le début du processus : les images locales devenaient invisibles à ses yeux, le script concluait que les deux machines étaient identiques et ne transférait rien.

### 2. La Solution du Bouclier Intelligent : protect
Nous avons purgé le tableau des exclusions des formats de fichiers pour utiliser la directive de filtrage de protection applicative de rsync :
```bash
--filter="protect user/data/flex-objects/**"
```
* **Flux descendant (Envoi) :** Les images locales sont pleinement visibles et sont correctement envoyées vers la production si elles manquent.
* **Flux ascendant (Sécurité) :** Si une utilisatrice téléverse un média en ligne via l'administration du serveur, cette règle dresse un bouclier d'invisibilité sur le serveur. Le script local a l'interdiction stricte de supprimer ce fichier distant, même s'il n'existe pas sur l'ordinateur du développeur.

### 3. La Traque des Dossiers Fantômes Locaux
Le journal de simulation a révélé que des dossiers parasites (`data/` et `user/user/data/`) s'étaient créés localement par accident lors des premiers tests de commandes manquées. `rsync` tentait de reproduire ces structures vides sur le serveur, générant des erreurs de répertoires non vides. Le nettoyage complet de la racine locale via `rm -rf data/` et `rm -rf user/user/` a définitivement purifié le processus de déploiement.

---

## V. Synthèse des Commandes d'Entretien

Pour maintenir le serveur en parfait état de fonctionnement, l'enchaînement des commandes en SSH après chaque déploiement doit être scrupuleusement respecté :

```bash
# 1. Se positionner à la racine du CMS
cd /var/www/grav

# 2. Purger les index Flex, le cache compilé Twig et les routes en mémoire
bin/grav clear
```