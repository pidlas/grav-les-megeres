# 📘 Documentation d'Administration - Découpage Avancé et Gestion Médias
## Projet : Les Mégères de l'Humus (Grav 2.0 / YunoHost)

Ce document résume la méthode mise en place pour orchestrer de manière robuste et autonome l'affichage alterné de texte brut, d'images flottantes optimisées et de listes sémantiques au sein des templates sans aucune injection de code HTML dans l'espace rédactionnel.

---

## 1. Principe Fondamental du Découpage par Séparateur
Pour offrir une autonomie totale dans l'organisation des pages tout en préservant une interface de saisie 100% textuelle pour les éditrices, nous utilisons un mot de repère textuel unique qui survit à toutes les variantes de compilation Markdown/HTML : `---SEPARATEUR---`.

Le texte rédigé dans le corps du fichier `.md` est automatiquement scindé par le template Twig en plusieurs sections indépendantes (indexées à partir de 0). Cela permet d'intercaler proprement des éléments structurels (images avec habillage CSS, listes, modules) directement entre les paragraphes de lecture.

---

## 2. Configuration et Implémentation

### 📄 A. Le Fichier de Contenu Épuré (`.md`)
Les propriétaires rédigent leur texte naturellement et isolent les sections à l'aide du mot-clé sur une ligne vide.

```markdown
---
title: 'Ta grand-mère sous le figuier'
menu: 'Ta grand-mère sous le figuier'
image_lampe: 'lampe-horloge.avif'
questions_interview:
  - 'Avez-vous un surnom&nbsp;?'
  - 'Partiez-vous en vacances quand vous étiez enfant&nbsp;?'
---

[Paragraphes d'introduction - Section 1]

---SEPARATEUR---

[Paragraphes du milieu - Section 2]

---SEPARATEUR---

[Paragraphes de conclusion - Section 3]
```

### 🖥️ B. L'Architecture du Template Twig (`.html.twig`)
Le template intercepte `page.content`, le découpe via le filtre `|split` et applique rigoureusement le traitement des shortcodes (`|shortcodes|raw`) sur chaque segment.

```twig
{# Découpage sécurisé du contenu #}
{% set parties_texte = page.content|split('---SEPARATEUR---') %}

{# 1. Section d'introduction (Index 0) #}
{% if parties_texte[0] is defined %}
    {{ parties_texte[0] | shortcodes | raw }}
{% endif %}

{# 2. Section du milieu avec image flottante (Index 1) #}
{% if parties_texte[1] is defined %}
    <div class="lampe-wrapper">
        {% if page.header.image_lampe %}
            {# L'image reçoit sa classe CSS et le filtre |raw pour forcer le rendu HTML #}
            {{ ('[img name="' ~ page.header.image_lampe ~ '" alt="Description" class="forme-lampe"]')|shortcodes | raw }}
        {% endif %}
        {{ parties_texte[1] | replace({'<p></p>': '', '<p> </p>': ''}) | shortcodes | raw }}
    </div>
{% endif %}

{# 3. Section Intercalaire : La liste des questions #}
{% if page.header.questions_interview is not empty %}
    <ul class="jolie-ombres slide-list">
        {% for question in page.header.questions_interview %}
            <li>{{ question | raw }}</li>
        {% endfor %}
    </ul>
{% endif %}

{# 4. Section de conclusion (Index 2) #}
{% if parties_texte[2] is defined %}
    {{ parties_texte[2] | replace({'<p></p>': '', '<p> </p>': ''}) | shortcodes | raw }}
{% endif %}
```

---

## 3. Règle d'Or CSS pour l'Habillage Flottant (`shape-outside`)
Pour que le texte épouse harmonieusement la courbure d'une image détourée (comme la lampe) :
1. **Emplacement HTML :** La balise `<img>` doit être déclarée **au tout début** du bloc de texte à habiller (comme orchestré dans la Section 2 du Twig).
2. **Cible CSS :** L'attribut `class="forme-lampe"` doit être appliqué **sur la balise `<img>` elle-même** (et non sur le conteneur `<picture>`). Notre version personnalisée de `ImgShortcode.php` gère automatiquement cette injection.

---

## 4. Procédure de Maintenance Éclair (Rappel Cache)
À chaque modification de la structure ou du fichier Markdown, videz les caches pour reconstruire les index :

*   **En Local :** `bin/grav clearcache`
*   **Sur le Serveur Distant (YunoHost) :**
    ```bash
    sudo rm -rf /var/www/grav/cache/*
    sudo systemctl restart php8.3-fpm
    ```
