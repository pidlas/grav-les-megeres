#!/bin/bash

# Chemin absolu de votre installation Grav
GRAV_ROOT="/var/www/grav"
THEME_DIR="$GRAV_ROOT/user/themes"
PAGES_DIR="$GRAV_ROOT/user/pages"

echo "======================================================================"
echo -e "\033[1;34m[1/3] ANALYSE DES TEMPLATES TWIG (Fichiers .twig)\033[0m"
echo "======================================================================"

# Recherche des méthodes Twig dynamiques
echo -e "\n--> Méthode 4 & 6 : Utilisation des collections dynamiques (images | files)"
find "$THEME_DIR" -type f -name "*.twig" -exec grep -Hn "page.media.images" {} \; 2>/dev/null
find "$THEME_DIR" -type f -name "*.twig" -exec grep -Hn "page.media.files" {} \; 2>/dev/null

# Recherche des appels Twig par nom de clé ou variables
echo -e "\n--> Méthode 5 : Appels par clé explicite page.media[...] ou méthodes d'objets"
find "$THEME_DIR" -type f -name "*.twig" -exec grep -E -Hn "page\.media\[|media\..*\.(html|url)" {} \; 2>/dev/null

# Recherche des balises HTML brutes codées en dur dans les thèmes
echo -e "\n--> Méthode 1 : Balises HTML <img> codées en dur avec de l'avif dans le thème"
find "$THEME_DIR" -type f -name "*.twig" -exec grep -i -Hn "<img.*\.avif" {} \; 2>/dev/null

echo "======================================================================"
echo -e "\033[1;32m[2/3] ANALYSE DU CONTENU (Fichiers .md)\033[0m"
echo "======================================================================"

# Recherche des Shortcodes d'images
echo -e "\n--> Méthode 3 : Utilisation de Shortcodes d'images [image]"
find "$PAGES_DIR" -type f -name "*.md" -exec grep -E -Hn "\[image.*src=" {} \; 2>/dev/null

# Recherche du Markdown Standard
echo -e "\n--> Méthode 2 : Utilisation du Markdown Standard ![Alt](...)"
find "$PAGES_DIR" -type f -name "*.md" -exec grep -E -Hn "!\[.*\]\(.*\)" {} \; 2>/dev/null

# Recherche des balises HTML injectées directement dans le contenu des pages
echo -e "\n--> Méthode 1 : Balises HTML <img> injectées dans le texte Markdown"
find "$PAGES_DIR" -type f -name "*.md" -exec grep -i -Hn "<img" {} \; 2>/dev/null

echo "======================================================================"
echo -e "\033[1;35m[3/3] INVENTAIRE DES IMAGES .AVIF ORPHELINES OU PRÉSENTES\033[0m"
echo "======================================================================"
echo "Liste de tous les fichiers .avif physiques actuellement stockés :"
find "$PAGES_DIR" -type f -name "*.avif" | sed 's/^/  - /'

echo -e "\n\033[1;32mAnalyse terminée.\033[0m"