#!/bin/bash
set -e

echo "🔧 Post-despliegue manual - tami-back-teams"
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ok() { echo -e "${GREEN}✅ $1${NC}"; }
warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
fail() { echo -e "${RED}❌ $1${NC}"; exit 1; }

# Verificar que estamos en el directorio correcto
if [ ! -f artisan ]; then
    fail "No se encontró artisan. Ejecuta este script desde la raíz del proyecto."
fi

# 1. Actualizar dependencias
echo "📦 Actualizando dependencias..."
composer install --prefer-dist --no-interaction --no-dev
ok "Dependencias actualizadas"

# 2. Regenerar autoload
echo "🔄 Regenerando autoload..."
composer dump-autoload --optimize --no-dev
ok "Autoload regenerado"

# 3. Limpiar cachés
echo "🧹 Limpiando cachés..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
ok "Cachés limpiados"

# 4. Cachear configuración
echo "⚡ Cachando configuración..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "Configuración cacheada"

# 5. Storage link
echo "🔗 Creando storage link..."
php artisan storage:link --force
ok "Storage link creado"

# 6. Permisos
echo "🔒 Estableciendo permisos..."
chmod -R 775 storage/ bootstrap/cache/
ok "Permisos establecidos"

echo ""
echo "🎉 Post-despliegue completado."
echo ""
echo "💡 Para ejecutar migraciones manualmente:"
echo "   php artisan migrate --force"
echo ""
echo "💡 Para ejecutar seeders manualmente:"
echo "   php artisan db:seed --force"
