# Instruções para Diagnóstico de Problemas com Imagens de Produto

## 🆕 Últimas Correções (2025-12-10)

### Problema: Imagens não persistem após salvar (limite de 2 imagens)

**Correções implementadas:**

1. **JavaScript - Disparo de Evento:**
   - Corrigido o `selectMultipleImages()` para encontrar corretamente o container `#galeria_paths_container`
   - Adicionados logs detalhados para rastrear o fluxo de eventos
   - Melhorada a lógica de busca do container (suporta string, ID, ou elemento)

2. **JavaScript - Listener de Evento:**
   - Adicionados logs detalhados no listener `media-picker:multiple-selected`
   - Logs mostram quantas imagens foram adicionadas vs ignoradas
   - Logs mostram total de inputs hidden após adicionar

3. **JavaScript - Submit do Formulário:**
   - Adicionado log antes do submit mostrando quantos inputs de galeria serão enviados
   - Log mostra todos os caminhos que serão enviados
   - Log mostra quantas imagens estão marcadas para remoção

4. **Backend - Logs Detalhados:**
   - Logs mostram total de imagens ANTES e APÓS processamento
   - Logs mostram quantas imagens foram processadas, preservadas, ou tiveram erro
   - Logs mostram lista completa de todas as imagens na galeria após processamento
   - Logs alertam se total no banco é menor que total enviado

**Como testar:**

1. Abra o console do navegador (F12)
2. Adicione 4-5 imagens à galeria
3. Observe os logs no console:
   - `[Galeria] Evento media-picker:multiple-selected recebido!`
   - `[Galeria] Resumo: X adicionadas, Y ignoradas`
   - `[Form Submit] Total de inputs de galeria que serão enviados: X`
4. Salve o produto
5. Verifique os logs do servidor (via script ou painel):
   - `ProductController::processGallery - Total de caminhos recebidos no POST: X`
   - `ProductController::processGallery - Total de imagens ANTES: Y`
   - `ProductController::processGallery - Total de imagens APÓS: Z`
6. Recarregue a página e verifique se as imagens persistem

## 🔍 Scripts de Diagnóstico

### 1. Verificar Imagens no Banco de Dados (WEB)

**Acesse via navegador:**
```
https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929
```

**Parâmetros:**
- `produto` (obrigatório): ID do produto (ex: 929)
- `tenant` (opcional): ID do tenant (padrão: 1)

**Exemplo:**
```
https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929&tenant=1
```

**O que o script mostra:**
- ✅ Informações do produto
- ✅ Total de imagens no banco
- ✅ Imagem principal (se houver)
- ✅ Lista completa da galeria
- ✅ Verificação das 4 imagens do print (IMG-20251206-WA0050.jpg, etc.)
- ✅ Imagens duplicadas (se houver)

### 2. Verificar Imagens no Banco de Dados (CLI)

**Execute via SSH:**
```bash
php scripts/check_product_images.php 929
```

**Ou com tenant específico:**
```bash
php scripts/check_product_images.php 929 --tenant=1
```

### 3. Coletar Logs do ProductController

**Execute via SSH:**
```bash
php scripts/collect_product_logs.php --product=929 --last-hour
```

**Opções disponíveis:**
- `--product=ID`: Filtrar apenas logs do produto
- `--last-hour`: Última hora
- `--last-minutes=N`: Últimos N minutos
- `--tail=N`: Últimas N linhas
- `--output=arquivo.txt`: Salvar em arquivo

**Exemplos:**
```bash
# Últimas 100 linhas do produto 929
php scripts/collect_product_logs.php --product=929 --tail=100

# Últimos 30 minutos e salvar em arquivo
php scripts/collect_product_logs.php --product=929 --last-minutes=30 --output=logs_produto_929.txt
```

## 📋 Checklist de Diagnóstico

### Problema: Imagens não estão sendo salvas

1. **Verificar se as imagens estão no banco:**
   - Acesse: `https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929`
   - Verifique se as imagens aparecem na lista

2. **Verificar logs do processamento:**
   - Execute: `php scripts/collect_product_logs.php --product=929 --last-hour`
   - Procure por:
     - `ProductController::update - imagem_destaque_path recebido`
     - `ProductController::processMainImage - Campo imagem_destaque_path encontrado`
     - `ProductController::processGallery - Total de caminhos recebidos no POST`
     - Mensagens de erro

3. **Verificar se o campo está sendo enviado:**
   - Abra o DevTools do navegador (F12)
   - Vá na aba "Network"
   - Envie o formulário
   - Verifique a requisição POST para `/admin/produtos/929`
   - Veja se `imagem_destaque_path` e `galeria_paths[]` estão no payload

### Problema: Limite de 2 imagens na galeria

1. **Verificar logs:**
   ```bash
   php scripts/collect_product_logs.php --product=929 --last-hour
   ```
   - Procure por: `Total de caminhos recebidos no POST`
   - Se mostrar apenas 2, o problema está no frontend (JavaScript)
   - Se mostrar mais de 2, o problema está no backend (processamento)

2. **Verificar JavaScript:**
   - Abra o DevTools (F12)
   - Vá na aba "Console"
   - Adicione imagens à galeria
   - Verifique se há erros no console
   - Verifique se os inputs hidden estão sendo criados corretamente

3. **Verificar banco de dados:**
   - Acesse: `https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929`
   - Veja quantas imagens estão realmente salvas

### Problema: Não consigo excluir imagens

1. **Verificar se o checkbox está sendo enviado:**
   - Abra o DevTools (F12)
   - Vá na aba "Network"
   - Marque o checkbox de remoção
   - Envie o formulário
   - Verifique se `remove_imagens[]` está no payload

2. **Verificar logs:**
   ```bash
   php scripts/collect_product_logs.php --product=929 --last-hour
   ```
   - Procure por: `ProductController::processGallery - Removendo X imagens`
   - Se não aparecer, o checkbox não está sendo enviado

3. **Verificar se a imagem é principal:**
   - Imagens principais não podem ser removidas via checkbox
   - Use o botão "Definir como imagem de destaque" de outra imagem primeiro

## 🔧 Correções Implementadas

### 1. Bug na função `removeGalleryPreview`
- **Problema:** Variável `previewItem` não estava definida
- **Correção:** Adicionado `var previewItem = btn.closest('div');`

### 2. Caminho de remoção de arquivo físico
- **Problema:** Usava caminho fixo que não funciona em produção
- **Correção:** Usa mesma lógica de `config/paths.php` para detectar caminho correto

### 3. Logs detalhados
- Adicionados logs em todos os pontos críticos
- Logs mostram quantos caminhos foram recebidos e quantos foram processados
- Logs mostram total de imagens no banco após processamento

## 📊 Onde Ver os Logs

### Em Produção (Hostinger)

Os logs do PHP geralmente estão em:
- `/var/log/php_error.log`
- `/var/log/apache2/error.log`
- `/var/log/httpd/error_log`
- Ou no painel da Hostinger: **Logs > Error Log**

### Verificar via SSH

```bash
# Ver últimos logs do produto 929
tail -f /var/log/php_error.log | grep "ProductController.*929"

# Ou usar o script
php scripts/collect_product_logs.php --product=929 --tail=50
```

## 🎯 Próximos Passos

1. **Acesse o script web:**
   ```
   https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929
   ```
   - Verifique se as 4 imagens do print estão no banco
   - Veja quantas imagens estão na galeria

2. **Execute o script de logs:**
   ```bash
   php scripts/collect_product_logs.php --product=929 --last-hour
   ```
   - Verifique se há erros
   - Veja quantos caminhos foram recebidos vs processados

3. **Teste adicionar mais imagens:**
   - Adicione 4-5 imagens à galeria
   - Salve o produto
   - Verifique os logs para ver quantas foram processadas
   - Verifique o banco para ver quantas foram salvas

4. **Teste remover imagens:**
   - Marque o checkbox de remoção
   - Salve o produto
   - Verifique os logs para ver se a remoção foi processada
   - Verifique o banco para confirmar que foi removida

## 📝 Notas Importantes

- **Imagens existentes são preservadas:** As imagens que já estão no banco são enviadas no POST via inputs hidden com `data-imagem-id`
- **Novas imagens são adicionadas:** Apenas imagens que não existem no banco são inseridas
- **Limite de imagens:** Não há limite no código - se houver limite, é problema de validação ou JavaScript
- **Remoção de imagens:** Apenas imagens de galeria podem ser removidas (não a principal)

