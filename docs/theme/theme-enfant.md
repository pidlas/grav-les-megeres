# Documentation Technique : Thème Enfant "Quark2-Child"

Cette documentation détaille l'architecture, la configuration et le fonctionnement du thème personnalisé développé pour le site **Les Mégères de l'Humus** sous le CMS Grav. Ce thème intègre la structure statique d'origine (HTML/CSS/JS) tout en la rendant dynamique.

---

## 1. Architecture Générale du Thème

Le thème est un thème enfant basé sur `quark2`. Il court-circuite le rendu graphique du parent pour imposer le design d'origine via une structure étanche et indépendante.

### Arborescence des fichiers du thème
Tous les fichiers se situent dans le répertoire `user/themes/quark2-child/` :

```text
quark2-child/
├── blueprints.yaml             # Configuration de base du thème pour Grav
├── quark2-child.yaml           # Configuration active et streams d'héritage
├── css/
│   ├── style.css               # Feuille de style principale d'origine
│   └── custom.css              # Correctifs d'intégration et ajustements structurels
├── fonts/
│   ├── Coco Gothic/            # Polices d'écriture d'origine
│   ├── TAN mon chéri/
│   └── The Seasons/
├── images/
│   └── logo/
│       └── logo.png            # Logo officiel du site
├── js/
│   ├── app.js                  # Scripts d'origine (Menu, burger, etc.)
│   ├── audio.js
│   ├── video.js
│   └── don-ha.js               # Script d'intégration du widget de don
└── templates/
    ├── default.html.twig       # Template par défaut des pages intérieures
    ├── main-layout.html.twig   # Squelette HTML global et indépendant du site
    └── partials/
        ├── diaporama.html.twig # Structure dynamique du diaporama à cartes CSS
        ├── header.html.twig    # Code HTML visible du haut de page (sans <head>)
        └── footer.html.twig    # Code HTML visible du bas de page (sans </body>)
```

---

## 2. Configuration Système (`quark2-child.yaml`)

Ce fichier assure la liaison avec le thème parent et déclare les variables indispensables. Les `streams` permettent à Grav de chercher un fichier dans le thème enfant en priorité, puis de basculer sur le parent si celui-ci est absent.

```yaml
enabled: true
header-fixed: true
header-animated: true
header-transparent: false
sticky-footer: true
theme-mode: auto
accent-color: '#8428DF'
blog-page: '/blog'
fontawesome:
  enabled: true
  local: false
custom_logo:
  - name: 'logo.png'
streams:
  schemes:
    theme:
      type: ReadonlyStream
      prefixes:
        '':
          - user/themes/quark2-child
          - user/themes/quark2
```

---

## 3. Structure Globale (`main-layout.html.twig`)

Pour éviter que le framework du thème parent Quark2 n'interfère avec le CSS d'origine, le fichier `main-layout.html.twig` reconstruit un flux HTML 100 % indépendant. C'est lui qui orchestre l'appel des fragments graphiques.

```twig
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ page.title }}</title>
    <link rel="stylesheet" href="{{ url('theme://css/style.css') }}" type="text/css" />
    <link rel="stylesheet" href="{{ url('theme://css/custom.css') }}" type="text/css" />
    {% block meta %}{% endblock %}
</head>
<body>
    <div class="wrapper">

        {# 1. Haut de page #}
        {% include 'partials/header.html.twig' %}

        {# 2. Affichage du diaporama (Uniquement sur la page d'accueil) #}
        {% if page.header.home %}
            {% include 'partials/diaporama.html.twig' %}
        {% endif %}

        {# 3. Zone de contenu éditable #}
        <main id="main-content-custom">
            {% block content %}
                {{ page.content | raw }}
            {% endblock %}
        </main>

        {# 4. Bas de page #}
        {% include 'partials/footer.html.twig' %}

    </div>

    {# Scripts JavaScript globaux #}
    <script src="{{ url('theme://js/app.js') }}" defer></script>
    <script src="{{ url('theme://js/audio.js') }}" defer></script>
    <script src="{{ url('theme://js/video.js') }}" defer></script>
    <script src="{{ url('theme://js/don-ha.js') }}" defer></script>
</body>
</html>
```

---

## 4. Gestion et Administration du Diaporama

Le diaporama de la page d'accueil fonctionne sur une structure stricte en CSS pur animée par des boutons radio (`<input type="radio">`). 

### Injection des données (Fichier de contenu)
Les diapositives sont administrées directement depuis le fichier de configuration de la page d'accueil situé à la racine du site : `user/pages/01.home/default.fr.md`.

Pour modifier, ajouter ou réordonner les cartes du diaporama, il convient d'éditer le bloc de métadonnées (Frontmatter) situé en haut du fichier :

```markdown
---
title: Accueil
menu: Accueil
home: true
slides:
    - titre: "À propos"
      lien: "/compagnie"
      image: "logohumous-notext-blue.png"
    - titre: "Grésilhette & la Salimonde"
      lien: "/gresilhette"
      image: "photo-gresilhette.jpg"
    - titre: "Ta grand-mère sous le figuier"
      lien: "/tagrand-mere"
      image: "photo-figuier.jpg"
    - titre: "Théâtre Forum"
      lien: "/presentation"
      image: "photo-forum.jpg"
    - titre: "Les cours"
      lien: "/cours"
      image: "photo-cours.jpg"
    - titre: "Les stages"
      lien: "/stages"
      image: "photo-stages.jpg"
    - titre: "Les ateliers"
      lien: "/ateliers"
      image: "photo-ateliers.jpg"
---
```
*Note administrative : Les images renseignées ici doivent être physiquement stockées dans le dossier `user/themes/quark2-child/images/` pour s'afficher en arrière-plan.*

### Rendu technique (`diaporama.html.twig`)
Le fichier génère automatiquement les identifiants requis (`c1`, `c2`, `c3`...) via l'index de la boucle Twig et applique l'image de fond en style *inline* :

```twig
{% if page.header.slides %}
<fieldset class="visually-hidden">
    <legend>Diaporama</legend>
    <div class="button-container">
        
        {% for slide in page.header.slides %}
            {% set slide_id = 'c' ~ loop.index %}
            
            <input type="radio" name="slide" id="{{ slide_id }}" {{ loop.first ? 'checked' : '' }}>
            
            {% set style_bg = '' %}
            {% if slide.image %}
                {% set style_bg = ' style="background-image: url(' ~ url('theme://images/' ~ slide.image) ~ ');"' %}
            {% endif %}
            
            <label for="{{ slide_id }}" class="card"{{ style_bg | raw }}>
                <div class="row">
                    <div class="description">
                        <a href="{{ base_url }}{{ slide.lien }}">
                            <h3 class="radio-title">{{ slide.titre }}</h3>
                        </a>
                    </div>
                </div>
            </label>
        {% endfor %}

    </div>
</fieldset>
{% endif %}
```

---

## 5. Maintenance et Maintenance du Cache

À chaque modification apportée sur l'arborescence, les fichiers de templates (`.html.twig`), ou les configurations des fichiers de pages (`.md`), le cache de Grav doit être impérativement purgé pour compiler la nouvelle structure.

Exécuter la commande suivante à la racine de l'installation :
```bash
bin/grav clear-cache
```