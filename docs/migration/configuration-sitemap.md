# 🗺️ Documentation : Configuration du Plugin Sitemap sous Grav

Cette documentation explique comment installer, configurer et valider le sitemap automatique de votre site Grav le jour du transfert en production. Le Sitemap est un plan de site invisible pour vos visiteurs, mais indispensable pour que les robots de Google découvrent et indexent instantanément toutes vos nouvelles pages propres.

---

## 🛠️ Étape 1 : Installation du plugin officiel Sitemap

Sous Grav, la gestion du sitemap se fait via l'extension officielle `Sitemap`. Vous pouvez l'installer de deux manières différentes.

### Option A : Via votre terminal (Recommandé & Ultra-rapide)
Connectez-vous à votre serveur en SSH, placez-vous à la racine de votre dossier Grav et lancez la commande suivante :
```bash
bin/gpm install sitemap
```

### Option B : Via l'interface d'administration de Grav
1. Connectez-vous à votre panneau d'administration Grav.
2. Dans le menu de gauche, cliquez sur **Plugins**.
3. Cliquez sur le bouton **Ajouter** (Add) en haut à droite.
4. Recherchez `Sitemap` et cliquez sur **Installer**.

---

## ⚙️ Étape 2 : Configuration du fichier `sitemap.yaml`

Une fois le plugin installé, nous allons configurer ses paramètres pour qu'il génère des URL absolues adaptées à votre nom de domaine de production.

1. Allez dans le dossier : `user/config/plugins/` (si le fichier `sitemap.yaml` n'y est pas, créez-le).
2. Ouvrez ou créez le fichier **`sitemap.yaml`** et collez-y cette configuration optimisée pour votre site :

```yaml
enabled: true
route: /sitemap
changefreq: monthly
priority: 0.5
ignores:
  - /reglement-interieur
  - /credits
  - /mentions-legales
  - /politique-de-securite
include_changefreq: true
include_priority: true
```

### 💡 Explications des réglages :
* `route: /sitemap` : Indique à Grav de rendre le fichier sitemap accessible à l'adresse `https://lesmegeresdelhumus.fr`.
* `changefreq: monthly` : Indique à Google que vos contenus changent généralement tous les mois (idéal pour un site de compagnie théâtrale).
* `ignores` : **Trés important pour votre SEO.** Ce bloc dit explicitement à Google d'ignorer vos pages administratives et légales. Cela permet de concentrer la puissance d'indexation de Google uniquement sur vos pages cruciales (spectacles, actualités, équipe, théâtre forum, cours/stages).

---

## 🔒 Étape 3 : Indiquer le Sitemap dans votre fichier `robots.txt`

Pour que les robots de Google trouvent votre sitemap sans même le chercher, il faut déclarer son adresse tout en bas de votre fichier `robots.txt`.

1. Ouvrez le fichier **`robots.txt`** situé à la racine absolue de votre hébergement Web.
2. Ajoutez cette ligne tout à la fin :

```text
Sitemap: https://lesmegeresdelhumus.fr
```

---

## 🧪 Étape 4 : Validation finale

Une fois le site en production, le plugin installé et configuré :

1. Ouvrez votre navigateur et saisissez l'adresse : **`https://lesmegeresdelhumus.fr`**
2. Vous devez voir apparaître une structure XML propre contenant la liste de toutes vos pages Grav (Accueil, Actualités, Grésilhette, etc.) précédées de votre vrai nom de domaine.
3. Allez sur la **Google Search Console** de la compagnie, section *Sitemaps*, et soumettez l'adresse `sitemap.xml` pour forcer Google à scanner votre nouveau site.