#!/usr/bin/env php
<?php
/**
 * Script de verificação de variáveis de ambiente
 * Execute: php check-env.php
 */

$requiredVars = [
    'APP_NAME',
    'APP_ENV',
    'APP_KEY',
    'APP_DEBUG',
    'APP_URL',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
];

$optionalVars = [
    'ASAAS_ACCESS_TOKEN',
    'ASAAS_BASE_URI',
    'FIREBASE_PROJECT_ID',
    'FIREBASE_API_KEY',
];

echo "🔍 Verificando variáveis de ambiente...\n\n";

$envFile = __DIR__ . '/.env';
$missing = [];
$empty = [];
$allOk = true;

// Verificar se o arquivo .env existe
if (!file_exists($envFile)) {
    echo "❌ ERRO: Arquivo .env não encontrado em: {$envFile}\n";
    echo "💡 Solução: Copie o .env.prod para .env ou crie um arquivo .env\n";
    exit(1);
}

echo "✅ Arquivo .env encontrado\n\n";

// Carregar variáveis do .env
$envContent = file_get_contents($envFile);
$lines = explode("\n", $envContent);
$envVars = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) {
        continue;
    }
    
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $envVars[$key] = $value;
    }
}

// Verificar variáveis obrigatórias
echo "📋 Variáveis obrigatórias:\n";
foreach ($requiredVars as $var) {
    if (!isset($envVars[$var])) {
        echo "  ❌ {$var}: NÃO ENCONTRADA\n";
        $missing[] = $var;
        $allOk = false;
    } elseif (empty($envVars[$var])) {
        echo "  ⚠️  {$var}: VAZIA\n";
        $empty[] = $var;
        $allOk = false;
    } else {
        // Mascarar valores sensíveis
        $displayValue = $var === 'DB_PASSWORD' || $var === 'APP_KEY' || $var === 'ASAAS_ACCESS_TOKEN'
            ? str_repeat('*', min(20, strlen($envVars[$var])))
            : $envVars[$var];
        echo "  ✅ {$var}: {$displayValue}\n";
    }
}

echo "\n📋 Variáveis opcionais:\n";
foreach ($optionalVars as $var) {
    if (!isset($envVars[$var]) || empty($envVars[$var])) {
        echo "  ⚠️  {$var}: Não configurada (opcional)\n";
    } else {
        $displayValue = str_contains($var, 'TOKEN') || str_contains($var, 'KEY')
            ? str_repeat('*', min(20, strlen($envVars[$var])))
            : $envVars[$var];
        echo "  ✅ {$var}: {$displayValue}\n";
    }
}

// Verificações especiais
echo "\n🔧 Verificações especiais:\n";

// Verificar APP_KEY
if (isset($envVars['APP_KEY']) && !empty($envVars['APP_KEY'])) {
    if (!str_starts_with($envVars['APP_KEY'], 'base64:')) {
        echo "  ⚠️  APP_KEY não está no formato correto (deve começar com 'base64:')\n";
        echo "     Execute: php artisan key:generate\n";
        $allOk = false;
    } else {
        echo "  ✅ APP_KEY está no formato correto\n";
    }
} else {
    echo "  ❌ APP_KEY não configurada\n";
    echo "     Execute: php artisan key:generate\n";
    $allOk = false;
}

// Verificar APP_DEBUG em produção
if (isset($envVars['APP_ENV']) && $envVars['APP_ENV'] === 'production') {
    if (isset($envVars['APP_DEBUG']) && $envVars['APP_DEBUG'] === 'true') {
        echo "  ⚠️  APP_DEBUG está como 'true' em produção (deve ser 'false')\n";
        $allOk = false;
    } else {
        echo "  ✅ APP_DEBUG está desabilitado em produção\n";
    }
}

// Verificar conexão com banco de dados
if (isset($envVars['DB_CONNECTION']) && $envVars['DB_CONNECTION'] === 'mysql') {
    echo "  ℹ️  Testando conexão com banco de dados...\n";
    try {
        $host = $envVars['DB_HOST'] ?? '127.0.0.1';
        $port = $envVars['DB_PORT'] ?? '3306';
        $database = $envVars['DB_DATABASE'] ?? '';
        $username = $envVars['DB_USERNAME'] ?? '';
        $password = $envVars['DB_PASSWORD'] ?? '';
        
        if (empty($database) || empty($username)) {
            echo "  ⚠️  Credenciais do banco incompletas\n";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            echo "  ✅ Conexão com banco de dados OK\n";
        }
    } catch (PDOException $e) {
        echo "  ❌ Erro ao conectar no banco: " . $e->getMessage() . "\n";
        $allOk = false;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($allOk) {
    echo "✅ Todas as verificações passaram!\n";
    exit(0);
} else {
    echo "❌ Algumas verificações falharam\n";
    if (!empty($missing)) {
        echo "\nVariáveis faltando: " . implode(', ', $missing) . "\n";
    }
    if (!empty($empty)) {
        echo "Variáveis vazias: " . implode(', ', $empty) . "\n";
    }
    exit(1);
}
