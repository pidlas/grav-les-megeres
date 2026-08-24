#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${BASE_URL:-https://grav.lesmegeresdelhumus.fr/grav}"
CURL_TIMEOUT="${CURL_TIMEOUT:-20}"
EXPECTED_STATUS="${EXPECTED_STATUS:-200}"
MEDIA_CHECK_URLS="${MEDIA_CHECK_URLS:-}"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

require_cmd curl

URLS=(
  "$BASE_URL/"
  "$BASE_URL/infos/compagnie"
  "$BASE_URL/contact"
)

if [[ -n "$MEDIA_CHECK_URLS" ]]; then
  IFS=',' read -r -a media_urls <<< "$MEDIA_CHECK_URLS"
  for media_url in "${media_urls[@]}"; do
    trimmed="$(echo "$media_url" | xargs)"
    if [[ -n "$trimmed" ]]; then
      URLS+=("$BASE_URL/$trimmed")
    fi
  done
fi

echo "==> Checking deployed site"
for url in "${URLS[@]}"; do
  status="$(curl -L -ksS --max-time "$CURL_TIMEOUT" -o /dev/null -w '%{http_code}' "$url")"
  echo "$status $url"
  if [[ "$status" != "$EXPECTED_STATUS" ]]; then
    echo "Post-deploy check failed for $url" >&2
    exit 1
  fi
done
