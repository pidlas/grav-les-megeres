# Documentation Administrateur – Projet Grav « Les Mégères de l'Humus »

Ce document centralise la configuration système, l'architecture des templates et la gestion des permissions du site sous Grav, hébergé sur un serveur YunoHost.

---

## 1. Gestion des Accès et de l'Environnement Système

Le serveur utilise une configuration mixte avec un utilisateur système local, un utilisateur d'application dédié et le serveur web standard.

### Profils Utilisateurs et Groupes
*   **`grav`** : Utilisateur propriétaire des fichiers du site.
*   **`www-data`** : Groupe du serveur web, utilisé par l'administration de Grav pour modifier les contenus.
*   **`phlip`** : Utilisateur système local (UID 1000, administrateur YunoHost). C'est le compte à utiliser pour toute intervention en terminal.

### Configuration SSH (Accès par Clé)
Pour lever les restrictions d'annuaire virtuel (LDAP) imposées par YunoHost sur les comptes locaux et autoriser la connexion par clé sans mot de passe :

1. La directive `AllowGroups` dans `/etc/ssh/sshd_config` inclut le groupe local `_ssh` :
   ```text
   AllowGroups ssh.main sftp.main ssh.app sftp.app admins root _ssh
   ```
2. Propriétés et permissions drastiques exigées par OpenSSH :
   *   Dossier racine : `/home/phlip` `(chmod 750)`
   *   Dossier des clés : `/home/phlip/.ssh` `(chmod 700 - chown phlip:phlip)`
   *   Fichier de clés : `/home/phlip/.ssh/authorized_keys` `(chmod 600 - chown phlip:phlip)`

### Alignement des Droits en Terminal (Umask)
Pour que les fichiers créés par `phlip` en ligne de commande restent modifiables par l'interface web de Grav, la règle suivante est inscrite dans `/home/phlip/.profile` :
```bash
umask 002
```
*Note : Cette règle applique par défaut les permissions 664 (fichiers) et 775 (dossiers) à chaque ouverture de session.*

---

## 2. Politique de Sécurité des Fichiers et Permissions

Le site applique le principe du moindre privilège pour s'isoler des autres applications du serveur YunoHost, tout en garantissant le travail collaboratif entre le terminal et le web.

### Règle des permissions
*   **Dossiers** : Configurés en `2750`. Le chiffre **2 (SGID bit)** force tout nouveau sous-dossier ou fichier à hériter automatiquement du groupe parent `www-data`. Le **0** final interdit l'accès au reste du monde.
*   **Fichiers** : Configurés en `0640`.

### Commandes de maintenance des droits (À lancer en root)
Pour réinitialiser proprement l'arborescence en cas de conflit :
```bash
# Propriété de l'application
chown -R grav:www-data /var/www/grav

# Application du bit SGID et droits restrictifs sur les dossiers
find /var/www/grav/user/pages/ -type d -exec chmod 2750 {} \;

# Droits restrictifs sur les fichiers
find /var/www/grav/user/pages/ -type f -exec chmod 0640 {} \;
```

### Automatisation dans Grav
Pour que l'administration web applique nativement ces restrictions lors de la création de futurs contenus, le fichier `/var/www/grav/user/config/system.yaml` contient :
```yaml
media:
  folder_chmod: 0750
  file_chmod: 0640
```

---

## 3. Architecture des Templates et Logique d'Affichage

Le thème enfant utilisé est `quark2-child`. Les templates ont été rendus étanches pour dissocier la structure globale du contenu pur.

### Fichier Racine : `templates/main-layout.html.twig`
Ce fichier pilote l'ordre d'affichage global en gérant une exception structurelle pour la page d'accueil (détectée via la présence de `page.header.slides`) :

```twig
{# 1. En-tête global #}
{% include 'partials/header.html.twig' %}

{# 2. Cas spécifique de l'Accueil (Condition Slides) #}
{% if page.header.slides %}
    <main>
        <section class="section-haut">
            <div class="conteneur-champignons">
                <img src="{{ url('image://i_champignons.svg') }}" class="champignons" alt="" loading="lazy">
                <h1>{{ page.title }}</h1>
            </div>
        </section>
    </main>

    {# Le Diaporama est isolé pour occuper 100% de la largeur de l'écran #}
    {% include 'partials/diaporama.html.twig' %}

    <main>
        <section class="section-bas">
            <div class="page-content">
                {# Le texte HTML dur de l'accueil est injecté ici #}
                {{ page.content | raw }}
            </div>
        </section>
    </main>

{# 3. Cas général des pages internes #}
{% else %}
    {% block content %}{% endblock %}
{% endif %}

{# 4. Éléments de fermeture constants (Feuilles + Footer) #}
<div class="conteneur-feuilles">
    <img src="{{ url('image://i_feuilles.svg') }}" class="feuilles" alt="" loading="lazy">
</div>
{% include 'partials/footer.html.twig' %}
```

### Template Standard : `templates/default.html.twig`
Utilisé automatiquement par toutes les sous-pages du site (fichiers `default.fr.md`). Il applique de manière uniforme le design de l'association :
```twig
{% block content %}
<main>
    <section>
        <div class="conteneur-champignons">
            <img src="{{ url('image://i_champignons.svg') }}" class="champignons" alt="" loading="lazy">
            <h1>{{ page.title }}</h1>
        </div>

        <div class="page-content">
            {{ page.content | raw }}
        </div>
    </section>
</main>
{% endblock %}
```

---

## 4. Maillage Interne et Gestion des Médias

### Navigation Dynamique (`partials/header.html.twig`)
Le menu supérieur génère automatiquement une structure à deux niveaux à partir de l'arborescence des dossiers de `user/pages/`.
*   Les dossiers parents visibles (ex: `02.infos`, `05.prestations`) génèrent des balises `<button>` ou `<span>` non routables (`routable: false` dans leur Frontmatter).
*   Les sous-dossiers génèrent les liens `<a>` cliquables.
*   Le titre cliquable de retour à l'accueil est sécurisé avec la variable `{{ base_url_relative }}`.

### Gestion Locale des Actifs (Assets)
*   **Logo global** : Stocké dans `user/images/logo.png` et appelé via le flux racine `url('image://logo.png')`.
*   **Images décoratives** : (`i_champignons.svg` et `i_feuilles.svg`) stockées dans `user/images/` et appelées via `url('image://...')`.
*   **Images du Diaporama** : Stockées exclusivement dans le dossier de la page d'accueil (`user/pages/01.home/`). Elles sont lues dynamiquement par le template via `page.media[slide.image].url`.
*   **Polices et Icônes (FontAwesome)** : Hébergées localement dans les répertoires `css/all.min.css` et `webfonts/` du thème enfant pour garantir l'autonomie du serveur YunoHost vis-à-vis des CDN externes.

### Liens internes dans le contenu
Pour assurer la portabilité du site (local vers production), tous les liens internes codés en dur dans le texte de l'accueil utilisent la variable de racine relative et intègrent l'arborescence des catégories :
```html
<a href="{{ base_url_relative }}/prestations/cours">Cours de théâtre</a>
```

---

## 5. Aide-Mémoire des Commandes d'Urgence

### Vider manuellement les caches (Forcer PHP 8.3 CLI)
Si l'administration ou les modifications Twig restent figées, forcer le vidage via la version de production du serveur :
```bash
/usr/bin/php8.3 /var/www/grav/bin/grav clear-cache --all
```
Ou méthode de purge radicale des fichiers compilés :
```bash
rm -rf /var/www/grav/cache/compiled/*
rm -rf /var/www/grav/cache/twig/*
rm -rf /var/www/grav/cache/grav/*
```

### Diagnostiquer l'identité et les droits
```bash
# Connaître l'UID et les groupes de la session courante
id phlip

# Analyser les permissions d'un répertoire spécifique
ls -la /var/www/grav/user/pages/
```