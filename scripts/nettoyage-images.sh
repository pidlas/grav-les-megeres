#!/usr/bin/env bash

set -euo pipefail

# Détermination du dossier racine de Grav
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🧹 [LOCAL] Suppression des anciens formats d'images devenus inutiles..."

# On se déplace à la racine pour sécuriser les chemins relatifs
cd "$REPO_ROOT"

# Compteurs pour le résumé final
count=0

# On cherche tous les anciens formats (.webp, .png, .jpg, .jpeg)
find user/pages/ -type f \( -iname "*.webp" -o -iname "*.png" -o -iname "*.jpg" -o -iname "*.jpeg" \) | while read -r img; do
    
    ext="${img##*.}"
    avif_file="${img%.$ext}.avif"
    
    # ⚠️ SÉCURITÉ ABSOLUE : On ne supprime l'ancien fichier QUE si son équivalent .avif existe bien à côté
    if [ -f "$avif_file" ]; then
        echo "  🗑️  Suppression de l'ancien format : $img"
        rm -f "$img"
        count=$((count + 1))
    else
        echo "  ⚠️  Conservation de : $img (Pas de fichier .avif trouvé pour cette image)"
    fi
done

echo "✅ Grand nettoyage terminé !"