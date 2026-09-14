#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
CONFIG_FILE="$SCRIPT_DIR/deploy.conf"

if [[ -f "$CONFIG_FILE" ]]; then
  # shellcheck disable=SC1090
  source "$CONFIG_FILE"
fi

SSH_HOST="${SSH_HOST:-192.168.0.51}"
SSH_PORT="${SSH_PORT:-1000}"
SSH_USER="${SSH_USER:-root}"
SSH_KEY_PATH="${SSH_KEY_PATH:-}"
SSH_CONTROL_MASTER="${SSH_CONTROL_MASTER:-0}"
REMOTE_BASE="${REMOTE_BASE:-/var/www/grav}"
REMOTE_WEB_ROOT="${REMOTE_WEB_ROOT:-$REMOTE_BASE}"
BASE_URL="${BASE_URL:-https://lesmegeresdelhumus.fr}"
REMOTE_OWNER_GROUP="${REMOTE_OWNER_GROUP:-grav:www-data}"
REMOTE_DIR_MODE="${REMOTE_DIR_MODE:-2750}"
REMOTE_FILE_MODE="${REMOTE_FILE_MODE:-640}"
POST_DEPLOY_SCRIPT="$SCRIPT_DIR/check-post-deploy.sh"

APPLY_CHANGES=0
DELETE_REMOTE=0
RUN_CHECKS="${RUN_CHECKS:-1}"
RUN_POST_DEPLOY_CHECK="${RUN_POST_DEPLOY_CHECK:-1}"
BACKUP_ENABLED="${BACKUP_ENABLED:-1}"
DEFAULT_MODE="DRY-RUN"
BACKUP_PATH="${BACKUP_PATH:-${REMOTE_BASE}.backup}"
BACKUP_TIMESTAMPED="${BACKUP_TIMESTAMPED:-1}"

# [2026-08-25] Ajout de DEPLOY_INCLUDE_PATHS par défaut s'il n'est pas défini dans deploy.conf
DEPLOY_INCLUDE_PATHS=("${DEPLOY_INCLUDE_PATHS[@]:-user}")

RSYNC_EXCLUDES=(
  "tmp/"
  "**/tmp/"
  "*.old"
  "*.old2"
  "*.old.*"
  "*.old2.*"
  "*.bak"
  "*.bak.*"
  "*~"
  "*.sans-twig"
  "*.a.tester"
  ".DS_Store"
  "**/.DS_Store"
  "Thumbs.db"
  "**/Thumbs.db"
  "desktop.ini"
  "**/desktop.ini"
  ".._*"
  "**/._*"
  "logs/"
  "**/logs/"
  "backup/"
  "**/backup/"
  "*.log"
  "**/*.log"
  "/data/"
  "/user/accounts/"
  "/user/plugins/"
  "/themes/quark2/"
  "system.yaml"
  "security.yaml"
  "security-private.php"
  "api-private.php"
  "/cache/"
  "**/cache/"
  "/images/"
  "**/images/"
  ".htaccess"
  "/media/"
  "config/themes/quark2.yaml"
  "config/versions.yaml"
)

usage() {
  cat <<EOF
Usage: ./scripts/deploy.sh [options]

Options:
  --apply               Execute deployment for real. Default is dry-run.
  --dry-run             Show what would be deployed (default behavior).
  --skip-check          Skip post-deploy HTTP checks.
  --skip-post-check     Skip post-deploy script execution.
  --no-backup           Skip remote backup creation before deployment.
  --allow-delete        Allow remote deletion of files that no longer exist locally.
  --help                Show this help.

Environment overrides:
  SSH_HOST, SSH_PORT, SSH_USER, REMOTE_BASE, BASE_URL,
  REMOTE_OWNER_GROUP, REMOTE_DIR_MODE, REMOTE_FILE_MODE.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply)
      APPLY_CHANGES=1
      shift
      ;;
    --dry-run)
      APPLY_CHANGES=0
      shift
      ;;
    --no-backup)
      BACKUP_ENABLED=0
      shift
      ;;
    --allow-delete)
      DELETE_REMOTE=1
      shift
      ;;
    --skip-check)
      RUN_CHECKS=0
      shift
      ;;
    --skip-post-check)
      RUN_POST_DEPLOY_CHECK=0
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

require_path() {
  if [[ ! -e "$1" ]]; then
    echo "Required path not found: $1" >&2
    exit 1
  fi
}

require_cmd ssh
require_cmd rsync
require_cmd git
require_cmd curl
require_path "$REPO_ROOT/index.php"

TARGET="$SSH_USER@$SSH_HOST"
CONTROL_SOCKET="/tmp/grav-deploy-%r@%h:%p.sock"
SSH_BASE_OPTS=(
  -p "$SSH_PORT"
  -o BatchMode=yes
  -o IdentitiesOnly=yes
  -o StrictHostKeyChecking=accept-new
)

if [[ "$SSH_CONTROL_MASTER" == "1" ]]; then
  SSH_BASE_OPTS+=(
    -o ControlMaster=auto
    -o ControlPersist=600
    -o ControlPath="$CONTROL_SOCKET"
  )
fi

if [[ -n "$SSH_KEY_PATH" ]]; then
  SSH_BASE_OPTS+=( -i "$SSH_KEY_PATH" )
fi

create_remote_backup() {
  if [[ "$BACKUP_ENABLED" -eq 0 ]]; then
    echo "==> Backup skipped (--no-backup)"
    return
  fi

  local timestamp
  timestamp="$(date +%Y%m%d-%H%M%S)"
  local backup_target
  backup_target="$BACKUP_PATH-$timestamp"

  echo "==> Creating remote backup: $REMOTE_BASE -> $backup_target"
  ssh "${SSH_BASE_OPTS[@]}" "$TARGET" "
    if [[ -d '$REMOTE_BASE' ]]; then
      mkdir -p '$(dirname "$BACKUP_PATH")'
      cp -a '$REMOTE_BASE' '$backup_target'
      echo 'Backup created at $backup_target'
    else
      echo 'No existing deployment found, skipping backup'
    fi
  "
}

run_post_deploy_checks() {
  if [[ "$RUN_POST_DEPLOY_CHECK" -eq 0 ]]; then
    echo "==> Skipping post-deploy checks"
    return
  fi

  if [[ ! -x "$POST_DEPLOY_SCRIPT" ]]; then
    echo "Post-deploy script not found or not executable: $POST_DEPLOY_SCRIPT" >&2
    return
  fi

  echo "==> Running post-deploy checks"
  BASE_URL="$BASE_URL" SSH_HOST="$SSH_HOST" SSH_PORT="$SSH_PORT" SSH_USER="$SSH_USER" REMOTE_BASE="$REMOTE_BASE" "$POST_DEPLOY_SCRIPT"
}

cleanup() {
  ssh "${SSH_BASE_OPTS[@]}" -O exit "$TARGET" >/dev/null 2>&1 || true
}

trap cleanup EXIT

RSYNC_SSH="ssh -p $SSH_PORT -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"
if [[ "$SSH_CONTROL_MASTER" == "1" ]]; then
  RSYNC_SSH="$RSYNC_SSH -o ControlMaster=auto -o ControlPersist=600 -o ControlPath=$CONTROL_SOCKET"
fi
if [[ -n "$SSH_KEY_PATH" ]]; then
  RSYNC_SSH="$RSYNC_SSH -i $SSH_KEY_PATH"
fi

# [2026-08-25] Configuration optimisée pour le stockage externe distant
RSYNC_OPTS=(
  -az
  --modify-window=2
  --size-only
  --itemize-changes
  --human-readable
  --no-perms          # Ignore les droits Linux incompatibles avec le stockage externe
  --no-owner          # Ignore la synchronisation du propriétaire original
  --no-group          # Ignore la synchronisation du groupe original
  --omit-dir-times    # Évite l'acharnement sur l'ajustement temporel des dossiers (.d..t......)
  -e "$RSYNC_SSH"
)

if [[ $DELETE_REMOTE -eq 1 ]]; then
  RSYNC_OPTS+=(--delete)
fi

show_delete_summary() {
  if [[ $DELETE_REMOTE -eq 0 ]]; then
    return
  fi

  echo "==> Preparing remote deletion summary"
  local tmp_file
  tmp_file="$(mktemp)"
  trap 'rm -f "$tmp_file"' EXIT

  # [2026-08-25] Réparation de la simulation de suppression :
  # En mode --list-only, rsync simule une lecture depuis la cible. Le flux est considéré entrant (<).
  # On extrait proprement le chemin à partir de la colonne 5 tout en filtrant les fichiers supprimés.
  rsync "${RSYNC_OPTS[@]}" --list-only "${DEPLOY_INCLUDE_PATHS[@]/#/$REPO_ROOT/}" "$TARGET:$REMOTE_BASE/" 2>/dev/null \
    | grep -E '^[<+][a-z]' \
    | awk '$1 ~ /^d/ {next} {print $5}' \
    | sed '/^$/d' > "$tmp_file" || true

  if [[ ! -s "$tmp_file" ]]; then
    echo "==> No remote files would be deleted"
    return
  fi

  local count
  count="$(wc -l < "$tmp_file" | tr -d ' ')"
  echo "==> Remote files that would be deleted: $count"
  while IFS= read -r rel_path; do
    if [[ -n "$rel_path" ]]; then
      printf '    %s/%s\n' "$REMOTE_BASE" "$rel_path"
    fi
  done < "$tmp_file"
}

confirm_remote_delete() {
  if [[ $DELETE_REMOTE -eq 0 ]]; then
    return 0
  fi

  if [[ $APPLY_CHANGES -eq 0 ]]; then
    return 0
  fi

  echo ""
  echo "==> This deployment will delete files on the remote target."
  read -r -p "Type DELETE to continue: " confirmation
  if [[ "$confirmation" != "DELETE" ]]; then
    echo "==> Aborted. Remote deletions were not performed."
    exit 0
  fi
}

clear_remote_cache() {
  if [[ $APPLY_CHANGES -eq 0 ]]; then
    echo "==> Skipping cache clear (DRY-RUN mode)"
    return
  fi

  echo "==> Clearing Grav cache via targeted folder removal"
  
  local sudo_cmd=""
  if [[ "$SSH_USER" != "root" ]]; then sudo_cmd="sudo "; fi

  ssh "${SSH_BASE_OPTS[@]}" "$TARGET" "
    $sudo_cmd""rm -rf '$REMOTE_BASE'/cache/twig/*
    $sudo_cmd""rm -rf '$REMOTE_BASE'/cache/compiled/*
    $sudo_cmd""rm -rf '$REMOTE_BASE'/cache/grav/*
  "
}

for pattern in "${RSYNC_EXCLUDES[@]}"; do
  RSYNC_OPTS+=(--exclude="$pattern")
done

if [[ $APPLY_CHANGES -eq 0 ]]; then
  RSYNC_OPTS+=(--dry-run)
fi

push_to_github() {
  if [[ $APPLY_CHANGES -eq 0 ]]; then
    echo "==> Skipping GitHub push (DRY-RUN mode)"
    return
  fi

  echo "==> Checking Git repository status..."
  
  # [2026-08-25] Vérifie si le dossier local est un dépôt Git valide
  if ! git -C "$REPO_ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Warning: $REPO_ROOT is not a Git repository. Skipping GitHub push." >&2
    return
  fi

  # [2026-08-25] Vérifie s'il y a des changements non commités en local
  if ! git -C "$REPO_ROOT" diff-index --quiet HEAD --; then
    echo "==> Local changes detected. Creating an automatic deployment commit..."
    git -C "$REPO_ROOT" add .
    git -C "$REPO_ROOT" commit -m "chore(deploy): automatic backup before deployment - $(date +'%Y-%m-%d %H:%M:%S')"
  fi

  # [2026-08-25] Récupère le nom de la branche courante (ex: main ou master)
  local current_branch
  current_branch="$(git -C "$REPO_ROOT" branch --show-current)"

  echo "==> Pushing local history to GitHub (branch: $current_branch)..."
  if git -C "$REPO_ROOT" push origin "$current_branch"; then
    echo "==> GitHub repository successfully updated!"
  else
    echo "Error: Failed to push to GitHub. Please check your SSH keys or remote URL." >&2
    exit 1
  fi
}

echo "==> Target: $TARGET"
echo "==> Remote base: $REMOTE_BASE"
echo "==> Mode: $( [[ $APPLY_CHANGES -eq 1 ]] && echo APPLY || echo DRY-RUN )"
if [[ $APPLY_CHANGES -eq 0 ]]; then
  echo "==> No changes will be pushed remotely unless you add --apply"
fi
if [[ $DELETE_REMOTE -eq 1 ]]; then
  echo "==> Remote deletions are enabled"
else
  echo "==> Remote deletions are disabled; files on the target server will be preserved"
fi

echo "==> Opening SSH control connection"
ssh "${SSH_BASE_OPTS[@]}" "$TARGET" true

if [[ $APPLY_CHANGES -eq 1 ]]; then
  create_remote_backup
fi

echo "==> Ensuring remote directories exist"
ssh "${SSH_BASE_OPTS[@]}" "$TARGET" "mkdir -p '$REMOTE_BASE'"

if [[ $DELETE_REMOTE -eq 1 ]]; then
  show_delete_summary
  confirm_remote_delete
fi

echo "==> Syncing selected content"
# [2026-08-26] Filtre de sécurité : accepte l'affichage des modifications dans les deux sens (< ou >) 
# mais ignore rigoureusement toutes les lignes de vérifications techniques commençant par un point (.)
if [[ ${#DEPLOY_INCLUDE_PATHS[@]} -gt 0 ]]; then
  for path in "${DEPLOY_INCLUDE_PATHS[@]}"; do
    rsync "${RSYNC_OPTS[@]}" "$REPO_ROOT/${path%/}/" "$TARGET:$REMOTE_BASE/${path%/}/" | grep -E '^[^.]' || true
  done
else
  rsync "${RSYNC_OPTS[@]}" "$REPO_ROOT/" "$TARGET:$REMOTE_BASE/" | grep -E '^[^.]' || echo "No files to transfer."
fi

if [[ $APPLY_CHANGES -eq 1 ]]; then
  echo "==> Normalizing ownership and permissions"
  ssh "${SSH_BASE_OPTS[@]}" "$TARGET" "
    find '$REMOTE_BASE' -type d -exec chmod '$REMOTE_DIR_MODE' {} +
    find '$REMOTE_BASE' -type d -exec chmod g+s {} +
    find '$REMOTE_BASE' -type f -exec chmod '$REMOTE_FILE_MODE' {} +
    chown -R '$REMOTE_OWNER_GROUP' '$REMOTE_BASE'
  "
fi

clear_remote_cache

if [[ $RUN_CHECKS -eq 1 ]]; then
  echo "==> Running HTTP smoke tests"
  URLS=(
    "$BASE_URL/"
    "$BASE_URL/infos/compagnie"
    "$BASE_URL/contact"
  )

  for url in "${URLS[@]}"; do
    status="$(curl -ksS -o /dev/null -w '%{http_code}' "$url")"
    echo "$status $url"
    if [[ "$status" != "200" ]]; then
      echo "Smoke test failed for $url" >&2
      exit 1
    fi
  done
fi

if [[ $RUN_POST_DEPLOY_CHECK -eq 1 ]]; then
  run_post_deploy_checks
fi

# [2026-08-25] Sauvegarde de l'historique sur GitHub après le succès du déploiement
push_to_github

echo "==> Deployment complete"
