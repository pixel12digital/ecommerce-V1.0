# Sistema de Atualizações e Versões

## Visão Geral

O sistema possui um mecanismo de migrations para gerenciar atualizações do banco de dados e rastreamento de versões do sistema.

## Sistema de Migrations

### Onde ficam os arquivos

As migrations ficam em `database/migrations/` e seguem o padrão de nomenclatura:
- `001_create_tenants_table.php`
- `002_create_tenant_domains_table.php`
- `003_create_platform_users_table.php`
- etc.

### Estrutura de uma Migration

Cada arquivo de migration é um script PHP que executa SQL diretamente:

```php
<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS example_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        -- campos...
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
```

**Importante:** As migrations devem ser **idempotentes**, ou seja, podem ser executadas múltiplas vezes sem causar erros. Use `CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ... IF EXISTS`, etc.

### Tabela de Controle: `migrations`

O sistema mantém uma tabela `migrations` que registra quais migrations já foram aplicadas:

```sql
CREATE TABLE migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)
```

Cada migration aplicada com sucesso é registrada nesta tabela com:
- `migration`: Nome do arquivo (sem extensão .php)
- `applied_at`: Data/hora de aplicação

### Como são registradas

O `MigrationRunner` (`App\Services\MigrationRunner`) é responsável por:

1. Verificar quais migrations já foram aplicadas (consultando `migrations`)
2. Comparar com os arquivos em `database/migrations/`
3. Executar apenas as pendentes
4. Registrar cada migration aplicada na tabela `migrations`

### Executando Migrations

#### Via linha de comando:

```bash
php database/run_migrations.php
```

#### Via interface web:

Acesse `/admin/system/updates` (requer autenticação de store admin) e clique em "Rodar Migrations Pendentes".

## Sistema de Versões

### Tabela `system_versions`

Armazena as versões do sistema que foram aplicadas:

```sql
CREATE TABLE system_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)
```

### Versão Atual

A versão atual do sistema é determinada pela última entrada em `system_versions` (ordenada por `applied_at DESC`).

Se não houver nenhuma entrada, o sistema assume a versão `0.1.0` ou `dev`.

### Registrando uma Nova Versão

Quando uma nova versão é lançada, você pode criar uma migration que insere a versão:

```php
// database/migrations/020_version_1_0_0.php
<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    INSERT INTO system_versions (version) 
    VALUES ('1.0.0')
    ON DUPLICATE KEY UPDATE version = VALUES(version)
");
```

## Tela "Atualizações do Sistema"

### Acesso

- **URL:** `/admin/system/updates`
- **Requer:** Autenticação de store admin
- **Modo:** Funciona tanto em multi quanto single

### Funcionalidades

1. **Exibir Versão Atual**
   - Mostra a última versão registrada em `system_versions`
   - Se não houver, mostra `0.1.0` ou `dev`

2. **Listar Migrations Pendentes**
   - Compara arquivos em `database/migrations/` com registros em `migrations`
   - Lista apenas as que ainda não foram aplicadas

3. **Executar Migrations**
   - Botão "Rodar Migrations Pendentes"
   - Executa todas as pendentes em ordem
   - Exibe resultado (sucesso/erro) para cada uma

### Fluxo de Uso

1. Acesse `/admin/system/updates`
2. Veja a versão atual e migrations pendentes
3. Clique em "Rodar Migrations Pendentes"
4. Veja o resultado de cada migration
5. Volte para a tela de atualizações para verificar

## Boas Práticas

### Nomenclatura de Migrations

Use números sequenciais e nomes descritivos:
- `001_create_tenants_table.php`
- `002_create_tenant_domains_table.php`
- `020_add_index_to_products.php`
- `021_version_1_0_0.php`

### Idempotência

Sempre use comandos SQL que podem ser executados múltiplas vezes:
- `CREATE TABLE IF NOT EXISTS`
- `ALTER TABLE ... ADD COLUMN ... IF NOT EXISTS` (MySQL 8.0+)
- Ou verifique se a coluna/tabela existe antes de criar

### Transações

O `MigrationRunner` executa cada migration em uma transação:
- Se houver erro, faz rollback
- Se sucesso, faz commit e registra na tabela `migrations`

### Ordem de Execução

As migrations são executadas em ordem alfabética (por nome do arquivo). Por isso, use números sequenciais no início do nome.

## Futuro: Integração com Endpoint Remoto

**Nota para futuras versões:**

O sistema está preparado para, no futuro, integrar com um endpoint remoto que informe a "última versão disponível". Isso permitirá:

1. Verificar se há atualizações disponíveis
2. Baixar migrations/arquivos de atualização
3. Aplicar automaticamente (ou com confirmação do admin)

A estrutura atual já suporta essa evolução sem grandes mudanças.

## Exemplo de Uso Completo

### 1. Criar nova migration

Crie `database/migrations/020_add_description_to_products.php`:

```php
<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    ALTER TABLE products 
    ADD COLUMN IF NOT EXISTS short_description VARCHAR(500) NULL 
    AFTER description
");
```

### 2. Executar migration

Via web: Acesse `/admin/system/updates` e clique em "Rodar Migrations"

Via CLI: `php database/run_migrations.php`

### 3. Verificar

A migration `020_add_description_to_products` será registrada em `migrations` e não aparecerá mais como pendente.



