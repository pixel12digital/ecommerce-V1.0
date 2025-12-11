# Guia de Deploy: Arquivos de Categorias para Produção

## 📋 Status Atual

### ✅ O que já está em produção:
- `public/index.php` - Contém todas as rotas de categorias
- `themes/default/admin/layouts/store.php` - Menu "Categorias" aparece
- Rotas registradas e funcionando

### ❌ O que está faltando em produção:
- `src/Http/Controllers/Admin/CategoriaController.php` - **NÃO EXISTE**
- `themes/default/admin/categorias/index-content.php` - **NÃO EXISTE**
- `themes/default/admin/categorias/form-content.php` - **NÃO EXISTE**

---

## 📁 Arquivos que Precisam ser Enviados

### 1. Controller

**Caminho Local:**
```
src/Http/Controllers/Admin/CategoriaController.php
```

**Caminho em Produção:**
```
/home/u426126796/domains/pontodogolfeoutlet.com.br/public_html/src/Http/Controllers/Admin/CategoriaController.php
```

**Como verificar se já existe:**
- No painel Hostinger, navegue até: `public_html/src/Http/Controllers/Admin/`
- Verifique se existe `CategoriaController.php`
- Se não existir, faça upload do arquivo local

**Referência:** Se a pasta `Admin/` contém `ProductController.php`, você está no lugar certo.

---

### 2. Views

**Caminho Local:**
```
themes/default/admin/categorias/index-content.php
themes/default/admin/categorias/form-content.php
```

**Caminho em Produção:**
```
/home/u426126796/domains/pontodogolfeoutlet.com.br/public_html/themes/default/admin/categorias/index-content.php
/home/u426126796/domains/pontodogolfeoutlet.com.br/public_html/themes/default/admin/categorias/form-content.php
```

**Como verificar:**
- No painel Hostinger, navegue até: `public_html/themes/default/admin/`
- Verifique se existe a pasta `categorias/`
- Se não existir, **crie a pasta** `categorias/`
- Dentro dela, faça upload dos arquivos:
  - `index-content.php`
  - `form-content.php`

**Referência:** Se a pasta `admin/` contém `products/` e `orders/`, você está no lugar certo.

---

## 🚀 Passo a Passo de Deploy

### Opção 1: Via Painel Hostinger (Gerenciador de Arquivos)

1. **Acesse o Gerenciador de Arquivos no painel Hostinger**

2. **Upload do Controller:**
   - Navegue até: `public_html/src/Http/Controllers/Admin/`
   - Clique em "Upload" ou "Enviar arquivo"
   - Selecione o arquivo local: `src/Http/Controllers/Admin/CategoriaController.php`
   - Aguarde o upload concluir

3. **Criar pasta de views (se não existir):**
   - Navegue até: `public_html/themes/default/admin/`
   - Se não existir a pasta `categorias/`, crie-a:
     - Clique em "Nova pasta" ou "Criar diretório"
     - Nome: `categorias`
     - Confirme

4. **Upload das Views:**
   - Entre na pasta `categorias/` que você acabou de criar (ou que já existia)
   - Clique em "Upload" ou "Enviar arquivo"
   - Selecione os arquivos locais:
     - `themes/default/admin/categorias/index-content.php`
     - `themes/default/admin/categorias/form-content.php`
   - Aguarde o upload concluir

---

### Opção 2: Via FTP/SFTP

1. **Conecte-se ao servidor via FTP/SFTP**
   - Host: `ftp.pontodogolfeoutlet.com.br` (ou IP do servidor)
   - Usuário: Seu usuário Hostinger
   - Senha: Sua senha FTP

2. **Upload do Controller:**
   ```bash
   # Navegue até a pasta
   cd public_html/src/Http/Controllers/Admin/
   
   # Faça upload do arquivo
   put CategoriaController.php
   ```

3. **Criar pasta e upload das Views:**
   ```bash
   # Navegue até a pasta admin
   cd public_html/themes/default/admin/
   
   # Crie a pasta categorias (se não existir)
   mkdir categorias
   cd categorias
   
   # Faça upload dos arquivos
   put index-content.php
   put form-content.php
   ```

---

### Opção 3: Via Git (se o repositório estiver configurado)

Se você tem acesso SSH e o repositório Git está configurado em produção:

```bash
# Conecte-se via SSH
ssh usuario@servidor

# Navegue até a pasta do projeto
cd /home/u426126796/domains/pontodogolfeoutlet.com.br/public_html

# Faça pull do repositório
git pull origin main

# Verifique se os arquivos foram atualizados
ls -la src/Http/Controllers/Admin/CategoriaController.php
ls -la themes/default/admin/categorias/
```

---

## ✅ Verificação Pós-Deploy

### 1. Verificar Arquivos no Servidor

**Via Painel Hostinger:**
- Confirme que `CategoriaController.php` existe em `public_html/src/Http/Controllers/Admin/`
- Confirme que a pasta `categorias/` existe em `public_html/themes/default/admin/`
- Confirme que `index-content.php` e `form-content.php` existem dentro de `categorias/`

**Via FTP:**
```bash
ls -la public_html/src/Http/Controllers/Admin/CategoriaController.php
ls -la public_html/themes/default/admin/categorias/
```

### 2. Executar Script de Diagnóstico

Acesse:
```
https://pontodogolfeoutlet.com.br/public/debug_rota_categorias.php
```

**Verificar se agora mostra:**
- ✅ **Seção 2:** "Controller encontrado"
- ✅ **Seção 3:** "View encontrada"
- ✅ **Seção 4:** "Classe CategoriaController pode ser carregada via autoload"

### 3. Testar Rota

Acesse:
```
https://pontodogolfeoutlet.com.br/admin/categorias
```

**Comportamento esperado:**
- ✅ Página de categorias carrega normalmente
- ✅ Lista de categorias é exibida (mesmo que vazia)

**Se aparecer erro:**
- Copie a mensagem de erro completa
- Verifique os logs do PHP
- Envie o erro para análise

---

## 📝 Checklist de Deploy

- [ ] Controller `CategoriaController.php` enviado para `public_html/src/Http/Controllers/Admin/`
- [ ] Pasta `categorias/` criada em `public_html/themes/default/admin/`
- [ ] View `index-content.php` enviada para `public_html/themes/default/admin/categorias/`
- [ ] View `form-content.php` enviada para `public_html/themes/default/admin/categorias/`
- [ ] Script de diagnóstico mostra todos os itens como ✅
- [ ] Rota `/admin/categorias` funciona sem 404

---

## 🔍 Estrutura de Arquivos Esperada em Produção

```
public_html/
├── src/
│   └── Http/
│       └── Controllers/
│           └── Admin/
│               ├── CategoriaController.php  ← NOVO (fazer upload)
│               ├── ProductController.php
│               └── ...
├── themes/
│   └── default/
│       └── admin/
│           ├── categorias/  ← NOVO (criar pasta)
│           │   ├── index-content.php  ← NOVO (fazer upload)
│           │   └── form-content.php  ← NOVO (fazer upload)
│           ├── products/
│           └── ...
└── public/
    └── index.php  ← Já atualizado ✅
```

---

## ⚠️ Observações Importantes

1. **Não mexa em `public/index.php`** - Ele já está correto
2. **Não mexa em `.htaccess`** - Já está configurado
3. **A pasta `categorias/` deve ser criada** se não existir
4. **Permissões de arquivo:** Os arquivos devem ter permissão de leitura (normalmente 644)
5. **Erro de banco no debug:** O erro "user 'root'@'localhost'" no script de debug é normal - o script tenta simular sem config de produção. A aplicação real conecta corretamente.

---

## 🐛 Troubleshooting

### Erro: "Class CategoriaController not found"

**Causa:** Controller não foi enviado ou está no caminho errado

**Solução:**
- Verifique se o arquivo existe em `public_html/src/Http/Controllers/Admin/CategoriaController.php`
- Verifique se o namespace está correto: `namespace App\Http\Controllers\Admin;`
- Limpe cache do PHP (OPcache) se houver

### Erro: "View not found"

**Causa:** View não foi enviada ou está no caminho errado

**Solução:**
- Verifique se a pasta `categorias/` existe em `public_html/themes/default/admin/`
- Verifique se `index-content.php` existe dentro da pasta
- Verifique se o caminho no controller está correto

### Erro: "404 - Página não encontrada"

**Causa:** Arquivos enviados, mas ainda retorna 404

**Solução:**
- Verifique se todos os arquivos foram enviados corretamente
- Limpe cache do PHP (OPcache)
- Verifique logs do PHP para ver se há erros de autoload
- Execute o script de diagnóstico novamente

---

## 📌 Resumo Rápido

**Arquivos para enviar:**
1. `src/Http/Controllers/Admin/CategoriaController.php` → `public_html/src/Http/Controllers/Admin/`
2. `themes/default/admin/categorias/index-content.php` → `public_html/themes/default/admin/categorias/`
3. `themes/default/admin/categorias/form-content.php` → `public_html/themes/default/admin/categorias/`

**Após upload:**
- Testar: `https://pontodogolfeoutlet.com.br/public/debug_rota_categorias.php`
- Testar: `https://pontodogolfeoutlet.com.br/admin/categorias`

---

## 🔗 Arquivos Relacionados

- `docs/RESUMO_PRATICO_INVESTIGACAO_404.md` - Resumo da investigação
- `docs/INSTRUCOES_INVESTIGACAO_404_FINAL.md` - Instruções completas
- `public/debug_rota_categorias.php` - Script de diagnóstico

