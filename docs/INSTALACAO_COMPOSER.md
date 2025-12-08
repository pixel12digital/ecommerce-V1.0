# 📦 Instalação do Composer

O projeto precisa do Composer para gerenciar dependências PHP. Este guia mostra como instalar no Windows.

## 🚀 Instalação no Windows

### Opção 1: Instalador Oficial (Recomendado)

1. **Baixe o instalador:**
   - Acesse: https://getcomposer.org/download/
   - Clique em "Composer-Setup.exe" para Windows

2. **Execute o instalador:**
   - Siga as instruções do instalador
   - Ele detectará automaticamente o PHP do XAMPP
   - Marque a opção para adicionar ao PATH do sistema

3. **Verifique a instalação:**
   ```bash
   composer --version
   ```

4. **Instale as dependências:**
   ```bash
   cd C:\xampp\htdocs\ecommerce-v1.0
   composer install
   ```

### Opção 2: Download Manual (composer.phar)

1. **Baixe o composer.phar:**
   - Acesse: https://getcomposer.org/download/
   - Baixe o arquivo `composer.phar`

2. **Coloque na pasta do projeto:**
   ```
   C:\xampp\htdocs\ecommerce-v1.0\composer.phar
   ```

3. **Instale as dependências:**
   ```bash
   cd C:\xampp\htdocs\ecommerce-v1.0
   php composer.phar install
   ```

## ✅ Verificação

Após instalar, verifique se a pasta `vendor/` foi criada:

```bash
Test-Path vendor\autoload.php
```

Deve retornar `True`.

## 🔧 Problemas Comuns

### "composer não é reconhecido"

**Solução:** O Composer não está no PATH do sistema.

1. Reinstale o Composer usando o instalador oficial
2. Ou adicione manualmente ao PATH:
   - Geralmente em: `C:\ProgramData\ComposerSetup\bin`
   - Adicione ao PATH do Windows

### "PHP não encontrado"

**Solução:** O Composer precisa encontrar o PHP.

1. Verifique se o PHP do XAMPP está funcionando:
   ```bash
   C:\xampp\php\php.exe -v
   ```

2. Adicione o PHP ao PATH:
   - Adicione `C:\xampp\php` ao PATH do Windows

### Erro ao executar composer install

**Solução:** Verifique se:
- PHP está funcionando
- Extensões necessárias estão habilitadas (pdo, mbstring)
- Conexão com internet está funcionando (para baixar pacotes)

## 📝 Após Instalação

Depois de rodar `composer install` com sucesso:

1. A pasta `vendor/` será criada
2. O arquivo `vendor/autoload.php` estará disponível
3. O sistema poderá carregar as classes automaticamente

Teste acessando:
- `http://localhost/ecommerce-v1.0/public/test.php`
- `http://localhost/ecommerce-v1.0/public/admin/platform/login`
- `http://localhost/ecommerce-v1.0/public/admin/login`

