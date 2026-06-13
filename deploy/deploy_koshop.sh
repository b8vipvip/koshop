#!/usr/bin/env bash
set -euo pipefail

REPO=${REPO:-/opt/koshop}
BASE_REF=${BASE_REF:-HEAD~1}
TARGET_REF=${TARGET_REF:-HEAD}
changed=$(git -C "$REPO" diff --name-only "$BASE_REF" "$TARGET_REF")

if grep -q '^third_party/dujiao-next/user/' <<<"$changed"; then
  echo '[koshop] building Dujiao-Next buyer frontend'
  cd "$REPO/third_party/dujiao-next/user"
  [[ -d node_modules ]] || npm ci
  npm run build
  rsync -a --delete dist/ /var/www/dujiao-next-user/
fi

if grep -q '^third_party/live-helper-chat/' <<<"$changed"; then
  echo '[koshop] syncing Live Helper Chat customizations'
  rsync -a --delete "$REPO/third_party/live-helper-chat/lhc_web/" /var/www/live-helper-chat/lhc_web/
  rm -rf /var/www/live-helper-chat/lhc_web/cache/cacheconfig/* /var/www/live-helper-chat/lhc_web/cache/compiledtemplates/*
fi
