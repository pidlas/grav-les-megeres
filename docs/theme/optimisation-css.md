# Optimisation CSS : Intégration locale vs Fichier Global

Ce document résume les bonnes pratiques d'architecture et de performance CSS basées sur le cas concret de la page `reglement-interieur.php` (style spécifique de quelques lignes face à un fichier global de 3 000 lignes).

## 1. Le Cas d'Usage : Style Unique et Court

Pour appliquer un style spécifique (ex: une marge colorée sur les paragraphes d'une section) uniquement sur une page précise, l'utilisation d'une balise `<style>` directement dans l'en-tête HTML (`<head>`) de la page PHP est **la solution la plus performante**.

### Exemple d'implémentation propre (Scoping Local)
Afin d'éviter de surcharger le sélecteur global `:root` et de limiter les recalculs du DOM par le navigateur, on utilise des variables CSS confinées au sélecteur de la section :

```html
<head>
    <!-- Styles globaux existants -->
    <link rel="stylesheet" href="style.css"> 

    <!-- Style local et spécifique à la page -->
    <style>
        .reglement-interieur {
            /* Variables locales isolées pour cette zone */
            --reglement-border-color: #f7ae56; /* Couleur du thème */
            --reglement-padding: 1.875rem;
        }

        .reglement-interieur p {
            border-left: 0.75rem solid var(--reglement-border-color);
            border-radius: 0.5rem; /* Arrondit subtilement les extrémités de la bordure */
            margin-bottom: 2rem;
            padding-left: var(--reglement-padding);
        }

        /* Variante pour mettre en valeur un article critique (ex: Sécurité) */
        .reglement-interieur p.important {
            --reglement-border-color: #e74c3c; /* Rouge alerte */
            background-color: rgba(231, 76, 60, 0.05); /* Léger fond transparent */
        }
    </style>
</head>
```

### Optimisation CSS : Cas pratique sous Grav CMS & Twig

Ce document résume l'approche optimale pour injecter un style CSS court et spécifique à une page unique en utilisant le moteur de rendu Twig du CMS Grav.

#### Problématique des templates génériques

Écrire du CSS "en dur" dans un fichier comme `default.html.twig` pose un problème de performance : ce template est partagé par de nombreuses pages. Le CSS s'appliquerait alors partout inutilement.

#### Solution : Injection ciblée via l'Asset Manager de Grav

Pour conserver la performance de l'intégration locale (0 requête HTTP), garantir l'application des styles face au fichier CSS principal (3 000 lignes) et contourner le cache de Grav, la meilleure méthode consiste à injecter une balise `<style>` classique protégée par une condition de ciblage (`page.slug`).

### Code final intégré dans `templates/default.html.twig` :

```twig
{% extends 'main-layout.html.twig' %}

{% block content %}
<main>
    {# Sécurité : On force la classe exacte si on est sur la bonne page #}
    <section class="{% if page.slug == 'reglement-interieur' %}reglement-interieur{% else %}{{ page.slug }}{% endif %}">

        {# Injection locale infaillible (Contourne le pipeline CSS de Grav) #}
        {% if page.slug == 'reglement-interieur' %}
            <style>
                .reglement-interieur p {
                    border-left: 0.75rem solid #f7ae56 !important;
                    border-radius: 0.5rem;
                    margin-bottom: 2rem;
                    padding-left: 1.875rem;
                }
                .reglement-interieur p.important {
                    border-left-color: #e74c3c !important;
                    background-color: rgba(231, 76, 60, 0.05) !important;
                }
            </style>
        {% endif %}

    {# 1. Récupération du contenu HTML brut produit par Grav #}
        {% set html = page.content | raw %}
        .etc
        .etc
        .etc

    </section>
{% endblock %}
```

#### Avantages techniques de cette approche

* **Performance réseau**
- Le CSS est directement injecté dans le bloc `<head>` généré par Grav sous forme de balise `<style>`, évitant une requête HTTP sur un fichier externe.
* **Isolation du code**
- Grâce à la condition `{% if page.slug == '...' %}`, le fichier global (`style.css` de 3 000 lignes) n'est pas impacté et les autres pages utilisant le template `default` restent légères.

---

## 2. Analyse Comparative des Approches

| Critère            | Balise `<style>` locale                   | Fichier `.css` externe dédié                    |
| ------------------ | ----------------------------------------- | ----------------------------------------------- |
| *Idéal pour*       | Styles < 100 lignes et uniques à une page | Styles longs ou partagés entre plusieurs pages. |
| *Requêtes HTTP*    | 0 requête supplémentaire                  | 1 requête réseau supplémentaire par fichier.    |
| *Mise en cache*    | Rechargé à chaque appel de la page PHP.   | Mis en cache après le 1er chargement.           |
| *Fichier global*   | Évite de surcharger le fichier principal. | Alourdit le fichier principal si inclus dedans. |

---

## 3. Gestion de la Performance à Grande Échelle

Lorsque le fichier principal (`style.css`) devient volumineux (ex: proche de 3 000 lignes), y ajouter des styles spécifiques à une seule page est une mauvaise pratique. Cependant, scinder le fichier n'est pas sans risques.

### Les pièges à éviter
* **Le `@import url()` en CSS pur**
- À proscrire pour la production. Il crée des chaînes de requêtes en cascade qui bloquent l'affichage de la page pendant que le navigateur découvre et télécharge les sous-fichiers.
* **L'inflation de `:root`**
- Déclarer des centaines de variables dans `:root` force le navigateur à recalculer les styles de tout le DOM en cascade au moindre changement de classe (ex: bascule mode sombre). Privilégier le *scoping local* (voir section 1).

### Les solutions industrielles pour les gros fichiers
1. **Les outils de Build (Approche moderne)**
- Utiliser des préprocesseurs ou compilateurs (**Sass/SCSS**, **PostCSS**, ou **Lightning CSS**). Le code est découpé en dizaines de petits fichiers clairs en développement (`_header.scss`, `_layout.scss`), mais l'outil les fusionne et les minifie en **un seul fichier CSS ultra-optimisé** pour la production.
2. **Le découpage par attribut `media` (Approche native)**
- Séparer le CSS lourd dans des balises `<link>` dotées d'attributs de ciblage. Le navigateur télécharge tout mais ne bloque le rendu de l'écran que pour le CSS nécessaire (ex: `media="print"` ou `media="screen and (min-width: 1024px)"`).

---
*Documentation générée le 13 octobre 2026.*