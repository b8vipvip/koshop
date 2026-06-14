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

if grep -q '^apps/koshop-seller-console/' <<<"$changed"; then
  echo '[koshop] building seller console and preserving seller BFF configuration'
  cd "$REPO/apps/koshop-seller-console"; [[ -d node_modules ]] || npm ci; npm run build
  install -d -m 750 /etc/koshop; touch /etc/koshop/seller-bff.env; chmod 640 /etc/koshop/seller-bff.env
  ensure_env(){ grep -q "^$1=" /etc/koshop/seller-bff.env || printf '%s=%s\n' "$1" "$2" >> /etc/koshop/seller-bff.env; }
  ensure_env DUJIAO_API_BASE http://127.0.0.1:8080
  for key in DUJIAO_ADMIN_TOKEN DUJIAO_ADMIN_USERNAME DUJIAO_ADMIN_PASSWORD DUJIAO_DASHBOARD_PATH DUJIAO_PRODUCTS_PATH DUJIAO_PRODUCT_DETAIL_PATH DUJIAO_ORDERS_PATH DUJIAO_ORDER_DETAIL_PATH DUJIAO_FINANCE_PATH DUJIAO_SETTINGS_PATH; do ensure_env "$key" ''; done
  echo '[koshop] configure empty Dujiao Admin credentials in /etc/koshop/seller-bff.env when required'; systemctl restart koshop-seller-bff
  for path in health dashboard 'orders?page=1&pageSize=5' 'products?page=1&pageSize=5' 'finance?page=1&pageSize=5'; do curl -sS "http://127.0.0.1:18100/api/koshop-seller/$path" | head -c 500; echo; done
fi
