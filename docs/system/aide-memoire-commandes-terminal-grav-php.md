# Aide-mémoire : Commandes Terminal & Routines Bash pour Sites Web (Grav & PHP)

Ce guide regroupe les commandes essentielles utilisées pour explorer, diagnostiquer, convertir les médias et modifier en lot les fichiers de vos sites **Grav** (`/var/www/grav`) et **PHP** (`/var/www/lesmegeres` et `/var/www/lesmegeres-app`).

---

## 1. Recherche de texte et de code dans les fichiers (`grep`)

La commande `grep` permet de localiser instantanément une balise, une classe CSS, un chemin d'image ou un shortcode dans l'ensemble d'un projet.

### Options indispensables
* `-r` : recherche récursive dans tous les sous-dossiers.
* `-n` : affiche le numéro de ligne exact.
* `-i` : ignore la casse (majuscules/minuscules).
* `-E` : active les expressions régulières étendues (ex: `jpg|png`).
* `-C 3` : affiche 3 lignes de contexte avant et après chaque résultat.
* `--include="*.php"` : restreint la recherche à un type de fichier précis.

### Exemples pratiques
* **Lister toutes les images `.jpg`, `.jpeg` et `.png` dans les fichiers PHP/HTML** :
  ```bash
  grep -rniE "\.(jpe?g|png)" /var/www/lesmegeres/index.php /var/www/lesmegeres-app/ --include="*.php" --include="*.html"
  ```
* **Obtenir uniquement la liste triée et sans doublons des chemins d'images** :
  ```bash
  grep -rohE "[a-zA-Z0-9_./-]+\.(jpe?g|png)" /var/www/lesmegeres/index.php /var/www/lesmegeres-app/ --include="*.php" | sort -u
  ```
* **Rechercher toutes les balises `<picture>`, `<img>` et `<source>` avec 2 lignes de contexte** :
  ```bash
  grep -rniE -C 2 "<(picture|img|source)" /var/www/lesmegeres-app/includes/ /var/www/lesmegeres-app/pages/
  ```
* **Vérifier l'utilisation d'un shortcode dans Grav (ex. `[picture`)** :
  ```bash
  grep -rn "\[picture" /var/www/grav/user/pages/ /var/www/grav/user/data/flex-objects/
  ```
* **Repérer toutes les références à `theme://` ou `images/` dans un thème Grav** :
  ```bash
  grep -rnE "(theme://|images/)" /var/www/grav/user/themes/humus/
  ```

---

## 2. Lecture ciblée de fichiers (`cat`, `sed -n`, `ls`, `tree`)

* **Afficher l'intégralité d'un fichier court** :
  ```bash
  cat user/themes/humus/templates/partials/navigation.html.twig
  ```
* **Lire uniquement un extrait précis d'un grand fichier CSS ou PHP (ex. lignes 1520 à 1560)** :
  ```bash
  sed -n '1520,1560p' user/themes/humus/css/_components.css
  ```
* **Vérifier la présence et les permissions des fichiers d'un dossier** :
  ```bash
  ls -la user/data/flex-objects/accueil/*.svg
  ```
* **Visualiser l'arborescence d'un répertoire** :
  ```bash
  tree user/config/themes/
  ```

---

## 3. Diagnostic réseau, serveur Nginx et cache Grav (`curl`, `nginx`, `grav`)

* **Tester l'en-tête HTTP renvoyé par Nginx pour une URL (code 200, 403, 404 et type MIME)** :
  ```bash
  curl -Ik http://localhost/user/data/flex-objects/accueil/i_champignons.svg
  ```
* **Rechercher une règle `location` dans la configuration Nginx** :
  ```bash
  grep -rn -C 3 "user/data" /etc/nginx/
  ```
* **Tester la syntaxe Nginx et recharger la configuration sans coupure** :
  ```bash
  sudo nginx -t && sudo systemctl reload nginx
  ```
* **Vider le cache complet de Grav après une modification Twig, YAML ou CSS** :
  ```bash
  cd /var/www/grav && bin/grav clearcache
  ```

---

## 4. Conversion d'images en lot (`find` + `magick`)

* **Convertir toutes les images `.png`, `.jpg` et `.jpeg` en `.webp` (qualité 80) sans écraser les fichiers existants ni toucher aux favicons** :
  ```bash
  find /var/www/lesmegeres -type f \
      \( -iname "*.png" -o -iname "*.jpg" -o -iname "*.jpeg" \) \
      -not -iname "*favicon*" \
      -not -iname "*apple-touch-icon*" | while read -r img; do
      ext="${img##*.}"
      webp_file="${img%.$ext}.webp"
      if [ ! -f "$webp_file" ]; then
          echo "Conversion : $img -> $webp_file"
          magick "$img" -quality 80 "$webp_file"
      fi
  done
  ```

---

## 5. Remplacement sécurisé en lot dans le code (`sed -i.bak`)

### Fonctionnement de la sauvegarde `.bak`
* L'option `-i.bak` demande à `sed` de modifier le fichier sur place **tout en créant immédiatement une copie de sauvegarde exacte** portant l'extension `.bak` (par exemple `actualites.php.bak` juste à côté de `actualites.php`).
* **En cas d'erreur** : il suffit de restaurer les originaux (`for f in *.bak; do mv "$f" "${f%.bak}"; done`).
* **Une fois validé** : il suffit de supprimer tous les fichiers `.bak` (`find /var/www/lesmegeres /var/www/lesmegeres-app -name "*.bak" -delete`).

### Commande `sed` sécurisée pour passer `.jpg`, `.jpeg` et `.png` en `.webp`
Cette commande exclut automatiquement les lignes contenant `favicon`, `apple-touch-icon` ou `og:image` afin de préserver les icônes système et les aperçus réseaux sociaux :

```bash
sed -i.bak -E '/(favicon|apple-touch-icon|og:image)/! s/\.(jpe?g|png)/.webp/gi'     /var/www/lesmegeres/index.php     /var/www/lesmegeres-app/includes/*.php     /var/www/lesmegeres-app/pages/*.php
```

### Nettoyage des sauvegardes `.bak` après vérification du site
```bash
find /var/www/lesmegeres /var/www/lesmegeres-app -maxdepth 2 -name "*.php.bak" -delete
```
