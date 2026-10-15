# Guide Technique : Gestion des ACL sous Grav CMS dans l'écosystème YunoHost

Ce document récapitule le fonctionnement des **ACL (Access Control Lists)**, leur utilité, leurs inconvénients majeurs dans un environnement de développement local ou de production, et comment les manipuler (ajouter/supprimer) en toute autonomie.

---

## 1. Qu'est-ce qu'une ACL Linux ? (Le "Topo")

Par défaut, Linux utilise le système d'autorisations traditionnelles **UGO** (User, Group, Other) visible via un `ls -la` (ex: `drwxrwsr-x`). Ce système standard impose une limite stricte : un fichier ne peut appartenir qu'à **un seul utilisateur** et **un seul groupe** à la fois.

L'**ACL (Access Control List)** est une extension du système de fichiers qui permet d'attribuer des droits fins, nominatifs et multiples à plusieurs utilisateurs ou plusieurs groupes sur un même dossier, sans modifier le propriétaire principal.
* **Le signe distinctif :** La présence d'un symbole **`+`** à la fin des droits traditionnels (ex: `drwxrwsr-x+`) lors d'un `ls -la`.

---

## 2. Le contexte : Pourquoi Grav CMS + YunoHost imposent-ils des ACL ?

Dans un environnement de serveur de production administré par **YunoHost**, la sécurité est segmentée :
1. L'application **Grav CMS** s'exécute sous l'utilisateur du serveur web : **`www-data`** (ou `grav`).
2. Les administrateurs ou les outils de déploiement (comme Git, FTP, ou l'utilisateur principal du serveur SSH) agissent sous un autre compte, par exemple **`admin`** ou **`pilipe`**.

### Pourquoi vous aviez configuré des ACL ensemble :
Pour éviter que l'un ne bloque l'autre, des **ACL par défaut (Default ACLs)** ont été configurées sur le répertoire `/user/pages/`. Leur but initial était génial : imposer une règle stricte pour que **tout nouveau fichier créé, qu'il le soit par l'interface d'administration de Grav (via `www-data`) ou par votre éditeur VS Code (via `pilipe`), hérite automatiquement des droits de lecture/écriture pour les deux entités**.

### Le grain de sable en environnement local :
Lorsque vous rapatriez ou manipulez ce projet en **local** (sans la couche de gestion fine des utilisateurs de YunoHost), le moteur PHP (ici **PHP 8.4**) et sa classe native `SplFileInfo` se heurtent à la complexité des métadonnées étendues des ACL. Si les identifiants d'utilisateurs (UID/GID) diffèrent entre votre serveur YunoHost distant et votre PC Debian local, la fonction système `stat()` de PHP s'emmêle les pinceaux, renvoie un échec de bas niveau (`stat failed`) et provoque l'exclusion des pages (Erreur 404).

---

## 3. Faut-il garder ou enlever les ACL ? (Aide à la décision)

### ❌ En Local (Votre PC sous Debian Trixie) :
**Il faut les ENLEVER.**
* **Pourquoi ?** Votre utilisateur local `pilipe` partage déjà le même groupe `www-data` avec le serveur web local (`nginx`). Les droits standards avec le bit SGID actif (`drwxrwsr-x`, le petit `s` à la place du `x` du groupe) suffisent amplement à ce que tout fichier créé appartienne nativement au groupe commun. Les ACL complexifient inutilement les appels PHP 8.4 et créent des corruptions d'index de routage (comme celle que vous venez de vivre).

###  En Production (Sur votre serveur YunoHost) :
**Il faut les GARDER (si configurées par le paquet YunoHost).**
* **Pourquoi ?** YunoHost s'appuie sur ces règles pour garantir que les sauvegardes système, l'application des mises à jour globales, et l'usage de l'administration Grav ne se bloquent pas mutuellement.

---

## 4. Guide des commandes : Comment les manipuler

Voici votre boîte à outils en ligne de commande pour piloter vos permissions sous Debian.

### A. Inspecter les détails d'une ACL
Pour lire les règles masquées derrière le symbole `+` :
```bash
getfacl /var/www/grav/user/pages/04.theatre-forum/
```

### B. Enlever TOUTES les ACL (Ce que nous venons de faire)
Pour purger récursivement tous les `+` et revenir à des droits Linux standards propres sur un dossier et ses enfants :
```bash
sudo setfacl -bR /var/www/grav/user/pages/04.theatre-forum/
```
* `-b` : Supprime toutes les ACL (efface le `+`).
* `-R` : Récursif (s'applique à tous les sous-dossiers et fichiers).

### C. Mettre en place des ACL spécifiques
Si un jour, en production, vous devez forcer l'utilisateur `www-data` et l'utilisateur `pilipe` à avoir les pleins pouvoirs constants sur le répertoire des pages :

```bash
# 1. Accorder les droits d'accès rwx à l'utilisateur www-data et pilipe
sudo setfacl -R -m u:www-data:rwx,u:pilipe:rwx /var/www/grav/user/pages/

# 2. Définir ces règles par défaut (Default) pour que les FUTURS fichiers créés en héritent
sudo setfacl -R -d -m u:www-data:rwx,u:pilipe:rwx /var/www/grav/user/pages/
```
* `-m` : Modifier/Ajouter une règle.
* `-d` : Par défaut (s'applique aux créations futures).
* `u:nom:` : Cible un utilisateur spécifique.