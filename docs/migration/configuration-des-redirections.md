# 📖 Documentation : Configuration des Redirections 301 sous Nginx (Migration PHP ➡️ GRAV)

Cette documentation explique comment configurer vos redirections le jour où vous passerez votre nouveau site Grav en production sous un serveur Web Nginx. Elle permet de gérer deux opérations cruciales pour votre migration :
1. Les redirections **techniques** (supprimer définitivement le sous-dossier `/grav` de l'adresse).
2. Les redirections **SEO** (rediriger vos anciennes pages `.php` vers vos nouvelles routes propres de Grav).

---

## ⚠️ Règle d'or avant de commencer
Toutes les configurations se font dans le fichier de configuration de votre site Nginx (par exemple `/etc/nginx/sites-available/lesmegeresdelhumus`).
* **Faites toujours une copie de sauvegarde** de votre fichier de configuration avant de le modifier.
* Après chaque modification, vous devrez tester la syntaxe et recharger Nginx pour appliquer les changements.

---

## 🛠️ Le code complet à insérer dans Nginx

La configuration se divise en deux parties distinctes : le dictionnaire de correspondance (`map`) à placer **en dehors** de votre bloc `server`, et les règles d'application à placer **à l'intérieur** de votre bloc `server`.

### 1. Le dictionnaire de correspondance (À placer TOUT EN HAUT du fichier, HORS du bloc `server`)

Ce bloc crée un tableau de correspondance ultra-rapide en mémoire pour Nginx.

```nginx
map $request_uri $redirect_uri {
    default "";

    # --- Pages Principales ---
    /index.php                      /;
    /actualites.php                 /actualites;
    /compagnie.php                  /compagnie;
    /equipe.php                     /equipe;
    /partenaires.php                /partenaires;

    # --- Spectacles, Activités & Pédagogie ---
    /gresilhette.php                /gresilhette-et-la-salimonde;
    /theatre-d-ombres.php           /theatre-d-ombres;
    /sieste-contee.php              /sieste-contee;
    /projet-pedagogique.php         /projet-pedagogique;
    /tagrand-mere.php               /tagrand-mere;

    # --- Théâtre Forum ---
    /presentation.php               /presentation;
    /le-spectacle.php               /le-spectacle;
    /le-laboratoire.php             /le-laboratoire;

    # --- Formations, Ateliers & Équipe ---
    /cours.php                      /cours;
    /stages.php                     /stages;
    /ateliers.php                   /ateliers;
    /sabine.php                     /equipe/sabine;
    /suzie.php                      /equipe/suzie;

    # --- Pages Légales & Administratives ---
    /contact.php                    /contact;
    /reglement-interieur.php        /reglement-interieur;
    /credits.php                    /credits;
    /mentions-legales.php           /mentions-legales;
    /politique-de-securite.php      /politique-de-securite;
}
```

### 2. Les règles d'activation (À placer À L'INTÉRIEUR de votre bloc `server {}`)

Ouvrez votre bloc `server` (là où se trouve votre configuration globale d'écoute du port 443 pour le SSL) et insérez ces lignes au tout début :

```nginx
server {
    listen 4443 ssl; # ou 443 selon votre configuration
    server_name lesmegeresdelhumus.fr www.lesmegeresdelhumus.fr;

    # =========================================================================
    # A. SUPPRESSION TECHNIQUE DU SOUS-DOSSIER /GRAV
    # Si un utilisateur tente d'accéder à /grav/quelque-chose, on le redirige à la racine
    # =========================================================================
    if ($request_uri ~* "^/grav/(.*)$") {
        return 301 https://lesmegeresdelhumus.fr;
    }

    # =========================================================================
    # B. EXÉCUTION DU DICTIONNAIRE DE REDIRECTIONS SEO (Ancien PHP -> Grav)
    # Si l'URL demandée est dans notre tableau "map", on applique la 301
    # =========================================================================
    if ($redirect_uri != "") {
        return 301 https://lesmegeresdelhumus.fr$redirect_uri;
    }

    # ... le reste de votre configuration Grav existante (location / ...) ...
}
```

---

## ⚡ Application et Validation des modifications

Pour que Nginx prenne en compte ces modifications sans couper votre site en ligne :

1. **Testez la syntaxe** de votre fichier pour vérifier qu'il n'y a pas d'erreur d'écriture en lançant cette commande dans votre terminal SSH :
   ```bash
   sudo nginx -t
   ```
   *Vous devez obtenir le message : `nginx: configuration file test is successful`.*

2. **Rechargez Nginx** pour appliquer instantanément les redirections :
   ```bash
   sudo systemctl reload nginx
   ```

3. **Validez le fonctionnement** en ouvrant une fenêtre de **navigation privée** et en testant les anciennes adresses (ex: `https://lesmegeresdelhumus.fr` ou `https://lesmegeresdelhumus.fr`). Le navigateur doit vous rediriger instantanément vers les nouvelles URL propres de votre site Grav.