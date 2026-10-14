# Résolution de l'erreur PHP LDAP sur le CMS Grav & Maintenance PHP sous YunoHost

## 📝 Description du problème
Suite à une mise à jour système (`apt upgrade`), le site Grav local ainsi que la console d'administration affichent l'erreur fatale suivante :
> `RuntimeException: The PHP LDAP module needs to be installed and enabled`

### Cause de l'erreur
Plusieurs versions de PHP cohabitent sur la machine (8.1, 8.2, 8.3, 8.4, 8.5). Lors de la mise à jour, le système a basculé la version par défaut vers **PHP 8.4**. Nginx utilisait un lien générique (`php-fpm.sock`) pointant vers une version pour laquelle l'extension LDAP n'était pas active ou installée, bloquant ainsi le plugin `login-ldap` de Grav.

---

## 🛠️ Résolution étape par étape

### Étape 1 : Identifier la version PHP active du serveur web
Pour connaître la version exacte lue par Nginx et Grav, créer un fichier temporaire à la racine du site :
```bash
echo "<?php echo phpversion(); ?>" > /var/www/grav/version.php
```
Puis l'interroger (via le navigateur sur `http://localhost/version.php` ou en ligne de commande) :
```bash
curl -i http://localhost/version.php
```
*Une fois la version identifiée (ex: 8.4.X), supprimer ce fichier temporaire : `rm /var/www/grav/version.php`.*

### Étape 2 : Installer et activer le module LDAP pour PHP 8.4
1. Installez le paquet manquant :
   ```bash
   sudo apt update
   sudo apt install php8.4-ldap
   ```
2. Activez le module spécifiquement pour la version 8.4 :
   ```bash
   sudo phpenmod -v 8.4 ldap
   ```
3. Vérifiez que le module est bien chargé en ligne de commande :
   ```bash
   php8.4 -m | grep ldap
   ```
   *(Le mot `ldap` doit s'afficher en retour).*

### Étape 3 : Fixer la version PHP dans la configuration Nginx
Pour éviter que le lien générique `php-fpm.sock` ne casse à nouveau le site lors d'une future mise à jour, il faut forcer l'usage de PHP 8.4.

1. Ouvrez le fichier de configuration du site :
   ```bash
   sudo nano /etc/nginx/sites-available/grav-local
   ```
2. Modifiez le bloc PHP tout en bas pour cibler explicitement le socket 8.4 :
   ```nginx
   location ~ \.php$ {
       ...
       fastcgi_pass unix:/run/php/php8.4-fpm.sock;
   }
   ```
3. Sauvegardez et quittez (`Ctrl+O`, `Entrée`, `Ctrl+X`).

### Étape 4 : Appliquer et vider le cache
Redémarrez les services système pour prendre en compte les changements et nettoyez le cache de Grav :
```bash
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
rm -rf /var/www/grav/cache/*
```

---

## 🛡️ Audit de sécurité sur Serveur YunoHost : Qui utilise quoi ?
Avant de supprimer une version de PHP sur un serveur YunoHost en production, il est impératif de vérifier quelles applications y sont rattachées.

1. **Cartographier l'attribution des applications** :
   ```bash
   ls -l /etc/php/*/fpm/pool.d/
   ```
   *Analyse : Inspectez chaque dossier de version (8.1, 8.2, etc.). Si des fichiers au nom de vos applications y figurent (ex: `nextcloud.conf`), la version de PHP correspondante est active et indispensable.*

2. **Vérifier les processus actifs en mémoire** :
   ```bash
   ps aux | grep php
   ```

3. **Vérifier la version globale CLI par défaut** :
   ```bash
   php -v
   ```
   *Si nécessaire, modifier la version globale par défaut du système avec : `sudo update-alternatives --config php`.*

---

## 🧹 Nettoyage sécurisé des versions PHP obsolètes (YunoHost)

1. **Exécuter le nettoyeur natif YunoHost (Recommandé)** :
   ```bash
   sudo yunohost tools basic-space-cleanup
   ```
   *Cet outil détecte automatiquement les versions de PHP orphelines (qui n'ont plus aucune application assignée) et propose leur suppression sécurisée.*

2. **Désinstallation manuelle (Si résidus confirmés à l'étape d'audit)** :
   *(Exemple pour purger définitivement PHP 8.1)*
   ```bash
   sudo apt purge "^php8.1.*"
   sudo apt autoremove --purge
   sudo rm -rf /etc/php/8.1/
   ```

---

## 🔍 Bon à savoir (Alternative d'urgence)
Si vous devez un jour accéder au site en urgence sans pouvoir réparer le module LDAP immédiatement, vous pouvez désactiver le plugin à la main :
1. Ouvrir le fichier `/var/www/grav/user/config/plugins/login-ldap.yaml`.
2. Passer la première ligne à `enabled: false`.
3. Vider le cache de Grav (`rm -rf /var/www/grav/cache/*`).