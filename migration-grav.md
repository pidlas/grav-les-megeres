# Migration Grav

Ce fichier a été réduit à un résumé de la migration et pointe désormais vers la documentation structurée.

## À retenir

- Le contenu a été migré vers [user/pages](user/pages).
- Les assets ont été répartis de façon cohérente dans l’arborescence du site.
- Le thème enfant [user/themes/quark2-child](user/themes/quark2-child) a été utilisé pour la personnalisation.
- Les shortcodes personnalisés sont désormais gérés depuis [user/custom/shortcodes](user/custom/shortcodes).

## Documentation actuelle

Pour toute information opérationnelle, utiliser la documentation structurée suivante :

- [docs/README.md](docs/README.md)
- [docs/quick-reference.md](docs/quick-reference.md)
- [docs/archives/migration-grav-archive.md](docs/archives/migration-grav-archive.md)

---

### 1. Redistribution des assets par page

Les fichiers sont copiés depuis les dossiers sources vers le dossier de la page qui les utilise.

| Dossier source | Type | Dossier destination (exemple) |
|---|---|---|
| `/images/` | Images jpg/webp/png | `user/pages/<section>/<page>/` |
| `/pdf/` | PDFs | `user/pages/<section>/<page>/` |
| `/multimedia/` | mp3/ogg/mp4/webm | `user/pages/<section>/<page>/` |

**Images décoratives partagées** (`i_champignons.svg`, `i_feuilles.svg`) : placées dans `user/themes/quark2-child/images/` pour être accessibles depuis les templates Twig.

**Détail par page** :

| Page | Assets copiés |
|---|---|
| `02.infos/01.actualites` | `actu-event.*`, `le-croco-bleu.jpg`, `les-7-petits-cauchemars.jpg` |
| `02.infos/02.compagnie` | `co_*`, `rapport*.png`, `rapport-*.pdf` |
| `02.infos/03.equipe` | `webJolie_*.jpg` |
| `02.infos/03.equipe/01.sabine` … `08.philippe` | `webRigolote_*.jpg` (une par bio) |
| `02.infos/04.partenaires` | `p_prefet-*.png`, `p_fdva.png`, `p_aude.png`, etc. |
| `03.spectacles/01.gresilhette-et-la-salimonde` | `sp_*.png`, `btn-prez.*`, `dossier-prez_compressed.pdf`, `teaser-*.mp4/webm` |
| `.../01.theatre-d-ombres` | `montage-inspiration.jpg`, `btn-tech-ombres.*`, `fiche-theatre-d-ombres.pdf` |
| `.../02.sieste-contee` | `sigean-{400w,800w,1200w}.{jpg,webp}` |
| `.../03.projet-pedagogique` | `btn-tech-projets.*` |
| `03.spectacles/02.ta-grand-mere-sous-le-figuier` | `lampe-horloge.jpg`, `spectacle5EHPAD.{mp3,ogg}` |
| `05.prestations/01.cours` | `les-7-petits-cauchemars.*`, `photo-cours.*`, `les-cours.*`, `objectifs-des-cours.*` |
| `05.prestations/02.stages` | `stages.*`, `les-stages-recto.*`, `les-stages-verso.*` |
| `05.prestations/03.ateliers` | `ateliers.*` |
| `06.contact` | `iconeTel.png`, `iconeMail.png`, `iconeAdresse.png`, `connect.png` |

---

### 2. Nouveau schéma de références dans les pages

#### Images propres à la page

```html
<!-- avant -->
{{ base_url_relative }}/images/co_dossier-prez-400w.webp

<!-- après -->
{{ page.route }}/co_dossier-prez-400w.webp
```

`page.route` retourne le chemin de la page (ex. `/infos/compagnie`). Les assets du dossier de la page sont servis à `<route>/<fichier>`.

#### Images du dossier parent (boutons `sp_*` dans les sous-pages de Grésilhette)

Les trois sous-pages (`theatre-d-ombres`, `sieste-contee`, `projet-pedagogique`) affichent les miniatures de navigation qui sont stockées dans le dossier du spectacle parent.

```html
<!-- avant -->
{{ base_url_relative }}/images/sp_theatre-d-ombres.png

<!-- après -->
{{ page.parent.route }}/sp_theatre-d-ombres.png
```

#### Images décoratives partagées (champignons, feuilles)

Ces images ne doivent **pas** apparaître dans le contenu des pages : elles sont entièrement gérées par les templates.

```twig
{# dans default.html.twig et main-layout.html.twig #}
{{ url('theme://images/i_champignons.svg') }}
{{ url('theme://images/i_feuilles.svg') }}
```

---

### 3. Nettoyage du contenu des pages

Les blocs HTML suivants, présents dans toutes les pages migrées, ont été **supprimés** du corps des fichiers `.md` car ils sont désormais rendus par les templates :

```html
<!-- supprimé de toutes les pages -->
<div class="conteneur-champignons">
    <img ...>
    <h1>Titre de la page</h1>
</div>

<!-- supprimé de toutes les pages -->
<div class="conteneur-feuilles">
    <img ...>
</div>
```

**26 fichiers** nettoyés automatiquement.

---

### 4. Modifications des templates

#### `default.html.twig`

- `url('image://i_champignons.svg')` → `url('theme://images/i_champignons.svg')`
- Ajout du support d'un sous-titre optionnel via `page.header.subtitle` :

```twig
<h1>{{ page.title }}</h1>
{% if page.header.subtitle %}<p class="sous-titre">{{ page.header.subtitle | raw }}</p>{% endif %}
```

#### `main-layout.html.twig`

- `url('image://i_champignons.svg')` → `url('theme://images/i_champignons.svg')`
- `url('image://i_feuilles.svg')` → `url('theme://images/i_feuilles.svg')`

#### Pourquoi `theme://` plutôt que `image://` ?

`image://` pointe sur `user/images/` (dossier utilisateur global). `theme://` pointe sur le dossier du thème actif (`user/themes/quark2-child/`). Les SVG décoratifs étant liés à la charte graphique du thème, `theme://images/` est l'emplacement sémantiquement correct.

---

### 5. Sous-titre de la page Contact

Le sous-titre "…Pour tout renseignement, fiche technique & tarif du spectacle" était imbriqué dans le bloc `conteneur-champignons` supprimé. Il est désormais stocké dans le frontmatter de la page :

```yaml
subtitle: '&hellip;Pour tout renseignement, fiche technique &amp; tarif du spectacle'
```

Et rendu automatiquement par `default.html.twig` via `page.header.subtitle`.

---

### 6. Pourquoi `page.route` plutôt que `page.media['filename'].url`

`page.media['filename']` nécessite un accès indexé (`ArrayAccess`) sur l'objet `MediaCollection` dans le sandbox Twig de Grav 2.x. Or le sandbox restreint les accès aux méthodes et propriétés explicitement listées dans `security.yaml`.

`page.route` est une méthode explicitement autorisée par le sandbox, et les assets en dossier de page sont servis par Grav à l'URL `<page.route>/<fichier>`. C'est donc la référence la plus simple et la plus fiable dans ce contexte.

---

## Serveur local Nginx + PHP-FPM (session du 2026-08-08)

Cette section documente la mise en place du serveur local Nginx pour Grav, ainsi que les incidents rencontres et leurs correctifs.

### 1. Objectif

- Remplacer le serveur web integre PHP par Nginx + PHP-FPM.
- Exposer le site en local sur `http://grav.localhost`.
- Stabiliser l'acces front et l'acces admin.

### 2. Configuration Nginx retenue

Base de travail: `webserver-configs/nginx.conf` (template Grav present dans le repo).

Vhost cree dans `/etc/nginx/sites-available/grav-local` puis active via symlink dans `/etc/nginx/sites-enabled/grav-local`.

Points importants de la conf:

- `root /home/pilipe/sites/grav-local;`
- `server_name grav.localhost localhost;`
- `location / { try_files $uri $uri/ /index.php?$query_string; }`
- Bloc `location ~ \.php$` avec `fastcgi_pass unix:/run/php/php-fpm.sock;`
- Regles de securite Grav (deny `cache`, `logs`, `backup`, `user/config`, etc.)
- Ecoute IPv4 + IPv6:
    - `listen 80;`
    - `listen [::]:80;`

### 3. Activation

Commandes utilisees:

```bash
sudo ln -sf /etc/nginx/sites-available/grav-local /etc/nginx/sites-enabled/grav-local
sudo nginx -t
sudo systemctl reload nginx
```

Ajout host local:

```bash
echo "127.0.0.1 grav.localhost" | sudo tee -a /etc/hosts
```

### 4. Incident 1: 404 Nginx immediat

Symptome:

- `curl -I http://grav.localhost` -> `404 Not Found` (page Nginx generique)

Causes trouvees:

1. Listener incomplet (IPv4/IPv6) a un moment de la configuration.
2. Permissions de traversee sur les dossiers parents insuffisantes.

Correctifs:

```bash
sudo chmod 711 /home/pilipe
sudo chmod 711 /home/pilipe/sites
```

Note:

- Le proprietaire `pilipe:pilipe` n'est pas un probleme.
- Le point critique est la traversee (`x`) pour l'utilisateur du worker Nginx/PHP-FPM.

### 5. Incident 2: 500 Grav (Essential Folders non writable)

Symptome:

- Page "Grav Problems"
- Dossiers signales non inscriptibles (selon les phases): `backup`, `assets`, `user/accounts`, etc.

Correctifs appliques:

```bash
cd /home/pilipe/sites/grav-local
sudo chgrp -R www-data cache logs tmp user/data images backup assets user/accounts
sudo chmod -R g+rwX cache logs tmp user/data images backup assets user/accounts
sudo find cache logs tmp user/data images backup assets user/accounts -type d -exec chmod g+s {} \;
```

### 6. Incident 3: 500 nonce key

Symptome:

- `RuntimeException: Failed to write nonce key file`

Cause:

- `user/config` et/ou fichiers secrets non accessibles en ecriture pour `www-data`.

Correctif:

```bash
sudo chgrp www-data /home/pilipe/sites/grav-local/user/config
sudo chmod 2775 /home/pilipe/sites/grav-local/user/config
sudo rm -f /home/pilipe/sites/grav-local/user/config/security-private.php.tmp
```

### 7. Incident 4: 404 Grav (applicative, pas Nginx)

Symptome:

- Le serveur repond, mais Grav affiche `Error 404`.

Cause:

- Le contenu est majoritairement en `default.fr.md` alors que la langue FR n'etait pas activee en config.

Correctif applique dans `user/config/system.yaml`:

```yaml
languages:
    enabled: true
    supported:
        - fr
    default_lang: fr
    include_default_lang: false
```

Puis purge cache:

```bash
cd /home/pilipe/sites/grav-local
php bin/grav clearcache
```

### 8. Incident 5: boucle de connexion Admin2

Symptome:

- Login admin accepte, puis retour immediate a l'ecran de connexion.

Contexte:

- Versions locales deja a jour pour les correctifs connus:
    - Admin2 `2.0.15`
    - API `1.0.12`
    - Login `3.8.13`

Cause locale identifiee:

- `user/config/plugins/api-private.php` en `600 pilipe:pilipe` (non lisible par `www-data`).
- Invalidation des tokens/sessions API cote admin.

Correctif retenu (permissions Unix classiques):

```bash
cd /home/pilipe/sites/grav-local
sudo chgrp www-data user/config/security-private.php user/config/plugins/api-private.php
sudo chmod 660 user/config/security-private.php user/config/plugins/api-private.php
```

Note importante:

- Sur cette machine, l'ajout d'ACL etendues (`setfacl -m`) echoue avec `Argument invalide`.
- Le mode stable est donc: groupe `www-data` + droits `660/770` selon le type de fichier/dossier.

Purge cache avec le bon user (important):

```bash
sudo -u www-data php bin/grav clearcache
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx
```

### 9. Verifications rapides

```bash
curl -I http://grav.localhost
curl -I http://grav.localhost/admin
```

Attendus:

- Front: HTTP 200
- Admin: HTTP 200 et session stable (plus de boucle login)

### 10. Lessons learned

- Distinguer `404 Nginx` et `404 Grav` des la premiere reponse HTTP/HTML.
- Sur un site dans `/home/...`, penser tres tot aux droits de traversee (`x`) des repertoires parents.
- Sur Grav 2 + Admin2, verifier les droits de lecture/ecriture des fichiers secrets dans `user/config/*private.php`.
- Pour les caches Grav, lancer le clearcache avec le meme user que PHP-FPM si ownership mixte.

### 11. Incident final: admin login en 500 (retabli)

Symptome:

- `GET /admin/login` retourne `HTTP 500`.
- La page d'erreur Grav Problems indique:
    - `/home/pilipe/sites/grav-local/assets` non writable
    - `/home/pilipe/sites/grav-local/user/data` non writable

Diagnostic:

- Les dossiers runtime `cache`, `logs`, `tmp` avaient ete corriges, mais pas `assets` et `user/data`.
- Les fichiers secrets admin/API devaient aussi etre explicitement accessibles a `www-data`.

Correctif applique:

```bash
cd /home/pilipe/sites/grav-local

# Secrets Admin2/API
sudo chgrp www-data user/config/security-private.php user/config/plugins/api-private.php
sudo chmod 660 user/config/security-private.php user/config/plugins/api-private.php

# Runtime Grav
sudo chgrp -R www-data cache logs tmp
sudo chmod -R u+rwX,g+rwX,o-rwx cache logs tmp
sudo find cache logs tmp -type d -exec chmod 2770 {} \;

# Dossiers requis par Grav Problems
sudo chgrp -R www-data assets user/data
sudo chmod -R u+rwX,g+rwX,o-rwx assets user/data
sudo find assets user/data -type d -exec chmod 2770 {} \;
```

Validation:

- `curl -i -H 'Host: grav.localhost' http://127.0.0.1/admin/login` -> `HTTP/1.1 200 OK`
- Page Admin2 servie normalement (plus d'ecran Grav Problems).

Commande de controle rapide:

```bash
cd /home/pilipe/sites/grav-local
stat -c '%A %a %U:%G %n' user/config/security-private.php user/config/plugins/api-private.php assets user/data cache logs tmp
curl -I http://grav.localhost/admin/login
```

### 12. Incident recurrent: "session expired" en boucle sur Admin2

Symptome:

- L'ecran de login admin accepte les identifiants puis revient au login.
- Messages "session expired" / "refresh token expired" cote interface Admin2.

Cause confirmee dans les logs:

- `api.auth: could not persist JWT secret to user/config/plugins/api-private.php`
- Tant que ce secret ne peut pas etre ecrit, les tokens ne sont valables que pour la requete courante et la session boucle.

Correctif immediat (deblocage):

```bash
cd /home/pilipe/sites/grav-local

# Debloquer l'ecriture des secrets utilises par Login/Admin2/API
chmod 666 user/config/plugins/api-private.php user/config/security-private.php

# Verification rapide
stat -c '%A %a %U:%G %n' user/config/plugins/api-private.php user/config/security-private.php
```

Validation apres correctif:

- Les nouvelles lignes de `logs/grav.log` ne doivent plus contenir `could not persist JWT secret`.
- Les `401` sur `/api/v1/...` restent normaux si la requete n'est pas authentifiee.

Procedure anti-recurrence (mode durable):

Objectif: supprimer la derive des proprietaires/groupes et revenir a un modele unique `pilipe:www-data`.

```bash
cd /home/pilipe/sites/grav-local

# 1) Normaliser proprietaire/groupe
sudo chown -R pilipe:www-data .

# 2) Droits de base
sudo find . -type d -exec chmod 2775 {} \;
sudo find . -type f -exec chmod 664 {} \;

# 3) Fichiers secrets stricts
sudo chmod 660 user/config/security-private.php user/config/plugins/api-private.php

# 4) Dossiers runtime Grav stricts
sudo chmod -R 2770 cache logs tmp assets user/data user/accounts
sudo chgrp -R www-data cache logs tmp assets user/data user/accounts user/config user/config/plugins

# 5) Purge cache avec l'utilisateur web
sudo -u www-data php bin/grav cache --all
```

Important:

- Si `sudo` demande un mot de passe, executer ces commandes dans un terminal utilisateur interactif.
- Eviter de melanger les executions CLI entre comptes differents (sinon fichiers recrees en `nobody:nogroup` ou groupe inattendu).

### 13. Checklist diagnostic en 60 secondes (admin loop/session expired)

Objectif: identifier en moins d'une minute si la boucle vient des secrets JWT, des droits runtime ou d'un probleme de cache.

1) Verifier la route admin

```bash
curl -sS -i -H 'Host: grav.localhost' http://127.0.0.1/admin/login | sed -n '1,40p'
```

Attendu: `HTTP/1.1 200 OK`

2) Verifier les 2 fichiers secrets critiques

```bash
cd /home/pilipe/sites/grav-local
stat -c '%A %a %U:%G %n' user/config/security-private.php user/config/plugins/api-private.php
getfacl -p user/config/security-private.php user/config/plugins/api-private.php 2>/dev/null | sed -n '1,120p'
```

Attendu:

- Fichiers existants, ecriture possible pour le process web.
- Pas de masque ACL bloquant (exemple typique a eviter: `group::---` sur `api-private.php`).

3) Verifier les dossiers runtime Grav

```bash
cd /home/pilipe/sites/grav-local
stat -c '%A %a %U:%G %n' cache logs tmp assets user/data user/accounts
```

Attendu: tous presents et inscriptibles pour l'utilisateur/groupe PHP-FPM.

4) Chercher le signal fort dans les logs

```bash
cd /home/pilipe/sites/grav-local
grep -nE 'api\.auth: could not persist JWT secret|Invalid or expired refresh token|No valid authentication credentials' logs/grav.log | tail -n 40
```

Interpretation rapide:

- `could not persist JWT secret` => cause principale de boucle session: corriger en priorite `user/config/plugins/api-private.php`.
- `Invalid or expired refresh token` en rafale => symptome secondaire d'un secret JWT non persiste.

5) Correctif express (deblocage)

```bash
cd /home/pilipe/sites/grav-local
chmod 666 user/config/plugins/api-private.php user/config/security-private.php
curl -I http://grav.localhost/admin/login
```

6) Correctif durable (anti-recurrence)

- Appliquer la section precedente "Procedure anti-recurrence (mode durable)".

### 14. Incident cache admin (retour d'experience) + comparaison /home vs /var/www

Symptome observe:

- Le bouton "Vider le cache" dans l'admin echoue.
- Log Grav: `API unhandled exception: touch(): Utime failed: Permission denied` (dans `Grav/Common/Cache.php`, au moment du `touch()` de `user/config/system.yaml`).

Ce qu'il a fallu faire pour debloquer:

1) Reparer les fichiers secrets/session quand ils ont derive (notamment `user/config/plugins/api-private.php` redevenu parfois `nobody:nogroup` en `600`).
2) Purger/recreer les dossiers runtime (`cache`, `tmp`, `logs`) quand des sous-dossiers internes etaient recrees en ownership incompatible.
3) Verifier que `user/config/system.yaml` est writable au moment du clear cache.

Pourquoi c'est plus fragile en local ici:

- Le site est sous `/home/pilipe/...`: il faut que le process web ait la traversee (`x`) sur chaque dossier parent (`/home`, `/home/pilipe`, `/home/pilipe/sites`, etc.).
- Si des commandes/agents differents ecrivent avec des identites differentes (pilipe, www-data, parfois nobody), l'ownership derive vite.
- Des sous-dossiers runtime peuvent alors devenir non modifiables par le process qui execute l'action admin.

Pourquoi ta prod est plus stable (et c'est normal):

- Avec `/var/www/grav` en `grav:www-data` + politique de droits coherente, le process web et les commandes d'admin partagent un modele unique.
- Cette homogeneite evite les bascules `nobody:nogroup` et les erreurs intermittentes sur `cache`/`tmp`/`user/config/*private.php`.

Conclusion pratique:

- Oui, ton intuition est bonne: l'emplacement sous `/home` + un contexte local multi-outils/utilisateurs explique la complexite rencontree.
- En local, le plus propre est de reproduire le modele prod:
    - owner projet dedie (ex: `grav` ou `pilipe`),
    - groupe web unique (`www-data`),
    - runtime Grav (`cache`, `logs`, `tmp`, `assets`, `user/data`) toujours writable par le groupe web,
    - et eviter de lancer des operations Grav avec des utilisateurs differents selon les moments.

### 15. Plan de bascule local vers /var/www/grav (sans reinstallation)

Objectif:

- Deplacer l'instance existante de `/home/pilipe/sites/grav-local` vers `/var/www/grav`.
- Reproduire le modele de prod (`grav:www-data`) pour stabiliser les permissions.
- Conserver tout le contenu et la configuration actuelle (pas de reinstallation Grav).

Duree estimee:

- 15 a 45 minutes selon taille des fichiers et verification finale.

#### 15.1. Prerequis

1) Avoir un utilisateur systeme `grav` (sinon le creer):

```bash
id grav 2>/dev/null || sudo useradd -m -s /bin/bash grav
```

2) Verifier que Nginx et PHP-FPM tournent:

```bash
systemctl is-active nginx
systemctl is-active php8.4-fpm
```

#### 15.2. Sauvegarde et copie

```bash
sudo mkdir -p /var/www

# Copie complete avec attributs et suppression des fichiers obsoletes cote destination
sudo rsync -a --delete /home/pilipe/sites/grav-local/ /var/www/grav/

# Sauvegarde rapide de securite (archive)
sudo tar -czf /var/www/grav-backup-before-switch-$(date +%Y%m%d-%H%M%S).tar.gz -C /var/www grav
```

#### 15.3. Ownership et permissions (mode type prod)

```bash
cd /var/www/grav

sudo chown -R grav:www-data .

# Regle generale
sudo find . -type d -exec chmod 2775 {} \;
sudo find . -type f -exec chmod 664 {} \;

# Fichiers secrets admin/api
sudo chmod 660 user/config/security-private.php user/config/plugins/api-private.php

# Dossiers runtime Grav
sudo chmod -R 2770 cache logs tmp assets user/data user/accounts
sudo chgrp -R www-data cache logs tmp assets user/data user/accounts user/config user/config/plugins
```

#### 15.4. Bascule Nginx

Mettre a jour le vhost local (ex: `/etc/nginx/sites-available/grav-local`):

- Remplacer `root /home/pilipe/sites/grav-local;`
- Par `root /var/www/grav;`

Puis:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### 15.5. Purge cache depuis l'utilisateur web

```bash
cd /var/www/grav
sudo -u www-data php bin/grav cache --all
```

#### 15.6. Validation post-bascule

```bash
curl -I http://grav.localhost
curl -I http://grav.localhost/admin/login

cd /var/www/grav
stat -c '%A %a %U:%G %n' user/config/system.yaml user/config/security-private.php user/config/plugins/api-private.php cache logs tmp
```

Attendus:

- Front en HTTP 200.
- Admin login en HTTP 200.
- Plus d'erreur `touch(): Utime failed: Permission denied` dans `logs/grav.log`.

#### 15.7. Rollback rapide (si besoin)

Si un incident survient juste apres bascule:

1) Remettre le `root` Nginx sur `/home/pilipe/sites/grav-local`.
2) `sudo nginx -t && sudo systemctl reload nginx`.
3) Le site redevient accessible sur l'ancien chemin le temps de corriger `/var/www/grav`.

Option rollback complet des fichiers:

```bash
sudo rm -rf /var/www/grav
sudo tar -xzf /var/www/grav-backup-before-switch-YYYYmmdd-HHMMSS.tar.gz -C /var/www
```

#### 15.8. Notes importantes

- Pas besoin de reinstaller Grav: on deplace une instance existante.
- Garder un seul modele d'execution pour les operations sensibles (idealement user web ou user projet coherent), afin d'eviter la derive d'ownership.

#### 15.9. Script unique (copier-coller)

Ce script fait toute la bascule en une fois, avec:

- sauvegarde tar auto,
- verification du vhost,
- rollback automatique du vhost si `nginx -t` echoue.

```bash
#!/usr/bin/env bash
set -Eeuo pipefail

# Usage:
#   bash switch-grav-local-to-varwww.sh
#
# Variables modifiables:
SRC="/home/pilipe/sites/grav-local"
DST="/var/www/grav"
OWNER_USER="grav"
OWNER_GROUP="www-data"
WEB_GROUP="www-data"
VHOST_FILE="/etc/nginx/sites-available/grav-local"

timestamp="$(date +%Y%m%d-%H%M%S)"
backup_tar="/var/www/grav-backup-before-switch-${timestamp}.tar.gz"
vhost_backup="${VHOST_FILE}.bak-${timestamp}"

echo "[1/9] Verifications prealables"
test -d "$SRC" || { echo "Source introuvable: $SRC"; exit 1; }
test -f "$VHOST_FILE" || { echo "Vhost introuvable: $VHOST_FILE"; exit 1; }

echo "[2/9] Creation user projet si besoin"
if ! id "$OWNER_USER" >/dev/null 2>&1; then
    sudo useradd -m -s /bin/bash "$OWNER_USER"
fi

echo "[3/9] Copie vers /var/www/grav"
sudo mkdir -p /var/www
sudo rsync -a --delete "$SRC/" "$DST/"

echo "[4/9] Sauvegarde de securite"
sudo tar -czf "$backup_tar" -C /var/www grav
echo "Backup: $backup_tar"

echo "[5/9] Ownership/permissions type prod"
pushd "$DST" >/dev/null
sudo chown -R "$OWNER_USER:$OWNER_GROUP" .
sudo find . -type d -exec chmod 2775 {} \;
sudo find . -type f -exec chmod 664 {} \;
sudo chmod 660 user/config/security-private.php user/config/plugins/api-private.php || true
sudo chmod -R 2770 cache logs tmp assets user/data user/accounts || true
sudo chgrp -R "$WEB_GROUP" cache logs tmp assets user/data user/accounts user/config user/config/plugins || true
popd >/dev/null

echo "[6/9] Bascule vhost Nginx"
sudo cp "$VHOST_FILE" "$vhost_backup"
sudo sed -i "s#root[[:space:]]\+/home/pilipe/sites/grav-local;#root $DST;#g" "$VHOST_FILE"

echo "[7/9] Validation Nginx"
if ! sudo nginx -t; then
    echo "nginx -t a echoue, rollback vhost"
    sudo cp "$vhost_backup" "$VHOST_FILE"
    sudo nginx -t
    echo "Rollback effectue. Rien n'a ete bascule cote Nginx."
    exit 1
fi

echo "[8/9] Reload services + purge cache"
sudo systemctl reload nginx
sudo -u "$WEB_GROUP" php "$DST/bin/grav" cache --all || true

echo "[9/9] Verifications finales"
curl -I http://grav.localhost || true
curl -I http://grav.localhost/admin/login || true
sudo stat -c '%A %a %U:%G %n' \
    "$DST/user/config/system.yaml" \
    "$DST/user/config/security-private.php" \
    "$DST/user/config/plugins/api-private.php" \
    "$DST/cache" "$DST/logs" "$DST/tmp" || true

echo "Termine."
echo "Vhost backup: $vhost_backup"
echo "Backup fichiers: $backup_tar"
```

Notes d'utilisation:

- Enregistrer le script dans un fichier (ex: `switch-grav-local-to-varwww.sh`), puis l'executer.
- Si ton root Nginx actuel differe de `/home/pilipe/sites/grav-local`, adapter `SRC` et la ligne `sed`.