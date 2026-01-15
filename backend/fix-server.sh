#!/bin/bash
# Script de correção rápida para problemas no servidor
# Execute: bash fix-server.sh

echo "🔧 Corrigindo problemas comuns no servidor..."
echo ""

cd /home/admcg-api-escolabiblica/htdocs/api.escolabiblica.admcg.com.br || exit 1

# Verificar se .env existe
if [ ! -f .env ]; then
    echo "❌ Arquivo .env não encontrado!"
    if [ -f .env.prod ]; then
        echo "📋 Copiando .env.prod para .env..."
        cp .env.prod .env
    else
        echo "❌ Arquivo .env.prod também não encontrado!"
        exit 1
    fi
fi

# Verificar e gerar APP_KEY se necessário
echo "🔑 Verificando APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null || grep -q "^APP_KEY=\s*$" .env 2>/dev/null; then
    echo "⚠️ APP_KEY não encontrada. Gerando..."
    if command -v php8.3 &> /dev/null; then
        php8.3 artisan key:generate --force
    else
        php artisan key:generate --force
    fi
else
    echo "✅ APP_KEY já configurada"
fi

# Limpar cache
echo "🧹 Limpando cache..."
if command -v php8.3 &> /dev/null; then
    php8.3 artisan config:clear
    php8.3 artisan cache:clear
    php8.3 artisan route:clear
    php8.3 artisan view:clear
    echo "🔄 Recriando cache..."
    php8.3 artisan config:cache
    php8.3 artisan route:cache
    php8.3 artisan view:cache
else
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Verificar permissões
echo "🔐 Verificando permissões..."
mkdir -p storage/framework/{sessions,views,cache/data,testing}
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R admcg-api-escolabiblica:admcg-api-escolabiblica storage bootstrap/cache 2>/dev/null || true

echo ""
echo "✅ Correções aplicadas!"
echo ""
echo "📋 Para verificar se tudo está OK, execute:"
echo "   php8.3 check-env.php"
