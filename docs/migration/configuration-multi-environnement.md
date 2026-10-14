# Configuration Multi-Environnement pour Grav CMS (Nginx & YunoHost)

Ce guide permet de configurer proprement deux environnements distincts (**Développement Local** et **Production Distante**) sans avoir à modifier manuellement vos fichiers à chaque déploiement. 

Cette méthode utilise les variables Nginx (`GRAV_ENVIRONMENT`), ce qui évite les caprices de détection automatique de Grav.

---

## Étape 1 : Structurer le dossier `user/env/` en local

Sur votre machine locale, créez l'arborescence suivante dans votre projet Grav. Ces dossiers contiendront les réglages spécifiques à chaque machine.

### 1. Structure des fichiers
Créez les dossiers et les fichiers comme suit :
```text
user/env/
├── local/
│   └── plugins/
│       └── login.yaml
└── production/
    └── plugins/
        └── login.yaml
```

### 2. Contenu du fichier Local (`user/env/local/plugins/login.yaml`)
Fige l'hôte pour votre serveur de test local :
```yaml
site_host: http://grav.localhost
```

### 3. Contenu du fichier Production (`user/env/production/plugins/login.yaml`)
Fige l'hôte pour votre serveur de production YunoHost (avec le sous-dossier `/grav`) :
```yaml
site_host: https://lesmegeresdelhumus.fr
```

> **Note :** Vous pouvez ajouter d'autres fichiers dans ces dossiers plus tard (par exemple un fichier `system.yaml` pour activer le débugger uniquement dans le dossier `local`).

---

## Étape 2 : Configurer le Nginx Local (Machine de développement)

Il faut indiquer à votre Nginx local qu'il doit forcer Grav à lire le dossier `local`.

1. Ouvrez le fichier de configuration de votre hôte virtuel Nginx local (celui qui gère `grav.localhost`).
2. Repérez le bloc PHP (généralement `location ~ \.php$`).
3. Ajoutez la ligne `fastcgi_param GRAV_ENVIRONMENT local;` à l'intérieur.

**Exemple de bloc PHP configuré :**
```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    
    # Ligne à ajouter pour Grav :
    fastcgi_param GRAV_ENVIRONMENT local;
    
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # (Ajustez selon votre version de PHP)
}
```
4. Redémarrez votre Nginx local pour appliquer la modification :
   ```bash
   sudo systemctl restart nginx
   ```

---

## Étape 3 : Configurer le Nginx Distant (Serveur YunoHost)

YunoHost permet d'ajouter des configurations Nginx personnalisées par application de manière persistante, sans toucher aux fichiers système globaux.

1. Connectez-vous en **SSH** sur votre serveur YunoHost distant.
2. Créez un fichier de configuration personnalisé pour votre application Grav à l'aide de cette commande :
   ```bash
   sudo nano /etc/nginx/conf.d/grav.lesmegeresdelhumus.fr.d/custom_env.conf
   ```
   *(Si le dossier de votre application porte un autre nom sous `/etc/nginx/conf.d/`, ajustez le chemin).*
3. Collez la ligne suivante à l'intérieur du fichier :
   ```nginx
   fastcgi_param GRAV_ENVIRONMENT production;
   ```
4. Enregistrez le fichier (`Ctrl+O` puis `Entrée`) et quittez l'éditeur (`Ctrl+X`).
5. Rechargez la configuration de Nginx sur YunoHost pour activer le changement :
   ```bash
   sudo systemctl reload nginx
   ```

---

## Étape 4 : Déploiement et Nettoyage

Une fois les configurations Nginx en place, vous n'aurez plus jamais à y toucher.

1. Exécutez votre script de déploiement habituel pour envoyer tout le dossier `user/` (qui inclut désormais le nouveau répertoire `user/env/`) vers le serveur distant.
2. Assurez-vous que votre script vide bien le cache de Grav à la fin (ou faites-le manuellement en ligne de commande) :
   ```bash
   bin/grav clear-cache
   ```

### Résultat :
* En local, Grav lit instantanément `user/env/local/` → L'alerte d'e-mail disparaît.
* En production, Grav lit instantanément `user/env/production/` → L'alerte disparaît et les liens d'e-mails pointent vers la bonne URL sécurisée.