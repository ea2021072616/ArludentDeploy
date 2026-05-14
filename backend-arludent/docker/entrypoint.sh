#!/bin/bash
set -e

echo "============================================"
echo "  Arludent Backend - Inicialización Docker"
echo "============================================"

# Esperar a que MySQL esté disponible
echo "[1/6] Esperando a que MySQL esté disponible..."
MAX_RETRIES=30
RETRY_COUNT=0

while ! mysqladmin ping -h"${DB_HOST:-db}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" --ssl=false --silent 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "ERROR: MySQL no está disponible después de $MAX_RETRIES intentos."
        exit 1
    fi
    echo "  MySQL no disponible aún (intento $RETRY_COUNT/$MAX_RETRIES). Esperando 2s..."
    sleep 2
done
echo "  ✓ MySQL está disponible."

# Generar key si no existe
echo "[2/6] Verificando APP_KEY..."
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "  Generando APP_KEY..."
    php artisan key:generate --force
    echo "  ✓ APP_KEY generada."
else
    echo "  ✓ APP_KEY ya configurada."
fi

# Ejecutar migraciones
echo "[3/6] Ejecutando migraciones..."
php artisan migrate --force
echo "  ✓ Migraciones completadas."

# Ejecutar seeders (solo si la tabla usuarios está vacía)
echo "[4/6] Verificando seeders..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "  Base de datos vacía, ejecutando seeders..."
    php artisan db:seed --force
    echo "  ✓ Seeders completados."
else
    echo "  ✓ Base de datos ya tiene $USER_COUNT usuarios, omitiendo seeders."
fi

# Limpiar y optimizar caché
echo "[5/6] Optimizando caché..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "  ✓ Caché optimizada."

# Permisos finales
echo "[6/6] Configurando permisos..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "  ✓ Permisos configurados."

echo ""
echo "============================================"
echo "  ✓ Backend listo. Iniciando servicios..."
echo "============================================"
echo ""

# Ejecutar comando principal (supervisord)
exec "$@"
