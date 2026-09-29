# Guide d'Optimisation SEO & Données Structurées pour Grav CMS
> À appliquer lors de la bascule sur le nom de domaine de production.

Ce guide détaille les améliorations à apporter à votre balise `<head>` dans `main-layout.html.twig` pour automatiser le SEO, corriger les URLs absolues et rendre les données structurées Schema.org totalement dynamiques.

---

## 1. Automatisation des Métadonnées SEO

### Le Problème Actuel
Votre code vérifie la présence d'une description avec `page.header.metadata.description`. Dans Grav, les métadonnées de page sont généralement stockées directement dans `page.header.metadata` sous forme de tableau, ou gérées globalement par le système. De plus, il manque la balise cruciale pour la langue du site.

### Version Optimisée Twig
Remplacez votre bloc `head_meta` actuel par celui-ci :

```twig
{% block head_meta %}
    {# 1. Gestion dynamique et propre de la description #}
    {% set meta_description = page.header.description ?: (page.header.metadata.description ?: site.metadata.description) %}
    <meta name="description" content="{{ meta_description|e }}" />
    
    {# 2. Gestion des robots #}
    <meta name="robots" content="{% if page.header.robots %}{{ page.header.robots|e }}{% else %}index, follow{% endif %}" />
    
    {# 3. URL Canonique absolue automatique #}
    <link rel="canonical" href="{{ page.url(true, true)|e }}" />
{% endblock %}
```

---

## 2. Optimisation du Protocole OpenGraph (Réseaux Sociaux)

### Le Problème Actuel
Votre balise `og:image` pointe vers une image fixe du thème (`logohumous-notext-blue.png`). Si vous partagez un article de blog ou une page de spectacle spécifique (comme *Grésilhette*), les réseaux sociaux afficheront toujours le logo de la compagnie au lieu de l'affiche du spectacle.

### Version Optimisée Twig
Ce code va chercher en priorité la première image présente dans le dossier de la page web. Si la page n'a pas d'image, il bascule automatiquement sur le logo global du thème en appliquant le bon protocole d'URL absolue.

```twig
{% block head_opengraph %}
    <meta property="og:site_name" content="{{ site.title|e }}" />
    <meta property="og:type" content="{% if page.header.og_type %}{{ page.header.og_type|e }}{% else %}website{% endif %}" />
    <meta property="og:title" content="{% if page.title %}{{ page.title|e }}{% else %}{{ site.title|e }}{% endif %}" />
    
    {% set og_description = page.header.description ?: (page.header.metadata.description ?: site.metadata.description) %}
    <meta property="og:description" content="{{ og_description|e }}" />
    <meta property="og:url" content="{{ page.url(true, true)|e }}" />
    
    {# Choix dynamique de l'image de partage #}
    {% if page.media.images|length > 0 %}
        {# Si la page contient une image (ex: affiche de spectacle), on prend la première #}
        <meta property="og:image" content="{{ page.media.images|first.absoluteUrl|e }}" />
    {% else %}
        {# Sinon, fallback sur le logo de la compagnie #}
        <meta property="og:image" content="{{ uri.rootUrl(true) ~ url('theme://images/logohumous-notext-blue.png', false) }}" />
    {% endif %}
    <meta property="og:image:alt" content="Illustration pour {{ page.title|e }}" />
{% endblock %}
```

---

## 3. Dynamisation du JSON-LD (Schema.org)

### Le Problème Actuel
Vos données structurées contiennent des informations codées en dur (ex: les liens vers Facebook et Instagram qui pointent vers les racines des réseaux sociaux). Lors de la bascule, il est préférable de centraliser ces variables dans le fichier de configuration globale de Grav (`site.yaml`) pour ne plus jamais avoir à modifier le code Twig du layout.

### Étape A : Préparation dans `user/config/site.yaml`
Ajoutez vos informations réelles à la racine de votre fichier de configuration :

```yaml
title: "Les Mégères de l'Humus"
email: "compagnie@lesmegeresdelhumus.fr"
organization:
  street: "7 rue du 19 mars 1962"
  postal_code: "11170"
  locality: "Moussoulens"
  country: "FR"
  facebook: "https://facebook.com"
  instagram: "https://instagram.com"
```

### Étape B : Version Optimisée Twig dans `main-layout.html.twig`
Le script va puiser ses sources directement dans votre configuration et s'adaptera instantanément au protocole HTTP/HTTPS de votre futur nom de domaine.

```twig
{% block head_jsonld %}
    {% set absolute_root = uri.rootUrl(true) %}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "@id": "{{ absolute_root }}/#organization",
          "name": "{{ site.title|e }}",
          "url": "{{ absolute_root }}",
          "logo": "{{ absolute_root }}{{ url('theme://images/logohumous-notext-blue.png', false) }}",
          "email": "{{ site.email|e }}",
          "sameAs": [
            "{{ site.organization.facebook }}",
            "{{ site.organization.instagram }}"
          ],
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "{{ site.organization.street|e }}",
            "postalCode": "{{ site.organization.postal_code|e }}",
            "addressLocality": "{{ site.organization.locality|e }}",
            "addressCountry": "{{ site.organization.country|e }}"
          }
        },
        {
          "@type": "WebSite",
          "@id": "{{ absolute_root }}/#website",
          "url": "{{ absolute_root }}",
          "name": "{{ site.title|e }}",
          "publisher": {
            "@id": "{{ absolute_root }}/#organization"
          },
          "inLanguage": "{{ grav.language.getActive ?: 'fr' }}"
        }
      ]
    }
    </script>
{% endblock %}
```

---

## 4. Checklist du Jour de la Bascule (Production)

Une fois les fichiers copiés sur le serveur de production définitif avec le bon nom de domaine, exécutez ces actions dans l'ordre :

1. **Vérifier le fichier `site.yaml` :** Assurez-vous que les comptes de réseaux sociaux et l'adresse e-mail de la compagnie sont corrects.
2. **Nettoyer les caches en profondeur :**
   ```bash
   cd /var/www/grav
   bin/grav clear-cache --all
   ```
3. **Tester sur les outils de validation officiels :**
   * Soumettez une URL de spectacle sur le [Validateur de Schéma Google](https://schema.org) pour vérifier le JSON-LD.
   * Soumettez une URL sur le *Facebook Sharing Debugger* pour forcer Facebook à indexer votre nouveau favicon et vos nouvelles images OpenGraph.