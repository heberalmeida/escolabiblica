# Configuração do Ambiente - Backend

## Arquivo .env.prod

Este arquivo contém as configurações de produção que serão copiadas para `.env` no servidor durante o deploy.

## Configurações do Banco de Dados

As seguintes variáveis de ambiente devem estar configuradas no arquivo `.env.prod`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=escolabiblica
DB_USERNAME=escolabiblica
DB_PASSWORD=A08LhBLUq2fq045jiQaC
```

## Variáveis Importantes

### Aplicação
- `APP_NAME`: Nome da aplicação
- `APP_ENV`: Ambiente (production)
- `APP_DEBUG`: false em produção
- `APP_URL`: URL da API em produção
- `APP_KEY`: Chave de criptografia (gerar com `php artisan key:generate`)

### Banco de Dados
- `DB_CONNECTION`: Tipo de conexão (mysql)
- `DB_HOST`: Host do banco de dados (127.0.0.1)
- `DB_PORT`: Porta do MySQL (3306)
- `DB_DATABASE`: Nome do banco de dados (escolabiblica)
- `DB_USERNAME`: Usuário do banco (escolabiblica)
- `DB_PASSWORD`: Senha do banco (A08LhBLUq2fq045jiQaC)

### Asaas (Gateway de Pagamento)
- `ASAAS_ACCESS_TOKEN`: Token de acesso da API Asaas
- `ASAAS_BASE_URI`: URL base da API Asaas (sandbox ou produção)

## Deploy

O workflow do GitHub Actions (`deploy-backend.yml`) automaticamente:
1. Faz upload do arquivo `.env.prod` para o servidor
2. Copia `.env.prod` para `.env` no servidor
3. Executa os comandos do Laravel com as configurações corretas

## Gerar APP_KEY

Se necessário gerar uma nova `APP_KEY`, execute no servidor:

```bash
cd /home/admcg-api-escolabiblica/htdocs/api.escolabiblica.admcg.com.br
php8.3 artisan key:generate
```

Depois atualize o arquivo `.env.prod` localmente e faça commit.

**Nota:** O workflow de deploy agora gera automaticamente a `APP_KEY` se ela não existir ou estiver vazia.

## Verificação de Variáveis de Ambiente

Para verificar se todas as variáveis de ambiente estão configuradas corretamente, execute no servidor:

```bash
cd /home/admcg-api-escolabiblica/htdocs/api.escolabiblica.admcg.com.br
php8.3 check-env.php
```

Este script verifica:
- ✅ Se todas as variáveis obrigatórias estão presentes
- ✅ Se as variáveis não estão vazias
- ✅ Se a `APP_KEY` está no formato correto
- ✅ Se `APP_DEBUG` está desabilitado em produção
- ✅ Se a conexão com o banco de dados funciona

## Solução de Problemas

### Erro: "Erro interno no servidor"

Se você receber este erro após alterar o `.env`, verifique:

1. **APP_KEY não configurada:**
   ```bash
   php8.3 artisan key:generate
   ```

2. **Cache desatualizado:**
   ```bash
   php8.3 artisan config:clear
   php8.3 artisan cache:clear
   php8.3 artisan route:clear
   php8.3 artisan view:clear
   php8.3 artisan config:cache
   php8.3 artisan route:cache
   php8.3 artisan view:cache
   ```

3. **Permissões de arquivo:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R admcg-api-escolabiblica:admcg-api-escolabiblica storage bootstrap/cache
   ```

4. **Verificar logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Habilitar Debug Temporariamente

Para ver detalhes do erro, altere temporariamente no `.env`:
```env
APP_DEBUG=true
```

**⚠️ IMPORTANTE:** Sempre volte para `APP_DEBUG=false` em produção após diagnosticar o problema.
