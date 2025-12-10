# Scripts de Utilidade

## collect_product_logs.php

Script para coletar e analisar logs relacionados ao `ProductController`, especialmente útil para diagnosticar problemas de persistência de imagens.

### Uso Básico

```bash
php scripts/collect_product_logs.php
```

### Opções Disponíveis

#### `--tail=N`
Mostra apenas as últimas N linhas dos logs.

```bash
php scripts/collect_product_logs.php --tail 100
```

#### `--product=ID`
Filtra logs apenas do produto especificado.

```bash
php scripts/collect_product_logs.php --product 929
```

#### `--last-hour`
Mostra apenas logs da última hora.

```bash
php scripts/collect_product_logs.php --last-hour
```

#### `--last-minutes=N`
Mostra apenas logs dos últimos N minutos.

```bash
php scripts/collect_product_logs.php --last-minutes 30
```

#### `--output=arquivo`
Salva a saída em um arquivo ao invés de exibir no console.

```bash
php scripts/collect_product_logs.php --output logs_produto_929.txt
```

#### `--help`
Mostra a ajuda completa.

```bash
php scripts/collect_product_logs.php --help
```

### Exemplos Práticos

**Ver logs do produto 929 da última hora:**
```bash
php scripts/collect_product_logs.php --product 929 --last-hour
```

**Ver últimas 50 linhas e salvar em arquivo:**
```bash
php scripts/collect_product_logs.php --tail 50 --output logs_recentes.txt
```

**Ver logs dos últimos 15 minutos do produto 929:**
```bash
php scripts/collect_product_logs.php --product 929 --last-minutes 15
```

### O que o Script Faz

1. **Detecta automaticamente** o arquivo de log do PHP
2. **Filtra** apenas linhas relacionadas ao `ProductController`
3. **Organiza** os logs por categoria:
   - `update`: Logs do método `update()`
   - `processMainImage`: Logs do processamento de imagem de destaque
   - `processGallery`: Logs do processamento da galeria
   - `other`: Outros logs relacionados
4. **Exibe estatísticas** no final (total de logs, erros encontrados, etc.)

### Onde o Script Procura o Log

O script tenta encontrar o arquivo de log automaticamente nos seguintes locais:

1. Valor de `error_log` do `php.ini`
2. `logs/php_error.log` (na raiz do projeto)
3. `storage/logs/php_error.log` (na raiz do projeto)
4. `/var/log/php_error.log`
5. `/var/log/apache2/error.log`
6. `/var/log/httpd/error_log`
7. Diretório temporário do sistema

### Saída do Script

O script organiza a saída por categoria e mostra:

- **Cabeçalho**: Caminho do arquivo de log sendo lido
- **Logs organizados**: Agrupados por método (update, processMainImage, processGallery)
- **Estatísticas**: Total de logs, contagem por categoria, erros encontrados

### Exemplo de Saída

```
Lendo logs de: /var/log/php_error.log
================================================================================

Total de logs encontrados: 15

--------------------------------------------------------------------------------
UPDATE (5 logs)
--------------------------------------------------------------------------------
[2025-01-XX 14:30:15] ProductController::update - Produto ID: 929, Tenant ID: 1
[2025-01-XX 14:30:15] ProductController::update - POST keys: nome, slug, imagem_destaque_path, ...
[2025-01-XX 14:30:15] ProductController::update - imagem_destaque_path recebido: '/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg'

--------------------------------------------------------------------------------
PROCESSMAINIMAGE (8 logs)
--------------------------------------------------------------------------------
[2025-01-XX 14:30:15] ProductController::processMainImage - Iniciando para produto 929, tenant 1
[2025-XX-XX 14:30:15] ProductController::processMainImage - Campo imagem_destaque_path encontrado: '/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg'
...

================================================================================
ESTATÍSTICAS:
  - Total de logs: 15
  - Método update: 5
  - Método processMainImage: 8
  - Método processGallery: 2
  - Outros: 0
```

### Dicas

- Use `--tail` para limitar a quantidade de logs e facilitar a leitura
- Combine `--product` com `--last-hour` para focar em um produto específico recente
- Use `--output` para salvar logs e analisar depois
- Se o script não encontrar o log automaticamente, edite o script e defina o caminho manualmente na variável `$logFile`

