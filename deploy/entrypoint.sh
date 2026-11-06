#!/bin/sh
set -e
cd /app

# Link público do storage (idempotente)
php artisan storage:link --force >/dev/null 2>&1 || true

# Aguarda o banco e aplica migrations + dados-base (planos e taxonomias; idempotentes)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  i=0
  until php artisan migrate --force; do
    i=$((i+1)); [ "$i" -ge 20 ] && { echo "Banco indisponível"; exit 1; }
    echo "Aguardando banco ($i)..."; sleep 3
  done
  php artisan db:seed --class=PlanoSeeder --force
  php artisan db:seed --class=TaxonomiaSeeder --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

exec "$@"
