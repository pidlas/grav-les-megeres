#!/usr/bin/env bash

set -euo pipefail

# Détermination du dossier racine de Grav (un niveau au-dessus du dossier du script)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🎨 [LOCAL] Optimisation et conversion de toutes les images en .avif..."

# On se déplace à la racine pour sécuriser les chemins relatifs
cd "$REPO_ROOT"

# On cherche toutes les images (.webp, .png, .jpg, .jpeg) de façon insensible à la casse (-iname)
find user/pages/ -type f \( -iname "*.webp" -o -iname "*.png" -o -iname "*.jpg" -o -iname "*.jpeg" \) | while read -r img; do
    
    # Extraction de l'extension actuelle pour couper proprement le nom du fichier
    ext="${img##*.}"
    # Création du nom du fichier de sortie .avif
    avif_file="${img%.$ext}.avif"
    
    # On ne convertit que si le fichier .avif n'existe pas encore
    if [ ! -f "$avif_file" ]; then
        echo "  ⚡ Conversion [${ext^^} -> AVIF] de : $img"
        # Utilisation de ImageMagick avec une excellente qualité de compression pour l'AVIF
        magick "$img" -quality 70 "$avif_file"
    fi
done

echo "✅ Analyse et conversions universelles terminées !"