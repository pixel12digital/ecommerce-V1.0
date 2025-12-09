<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Services\MediaLibraryService;
use App\Tenant\TenantContext;

class MediaLibraryController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $tenant = TenantContext::tenant();
        
        // Aplicar filtros se houver
        $folder = $_GET['folder'] ?? null;
        $query = $_GET['q'] ?? '';
        
        if (!empty($query)) {
            $imagens = MediaLibraryService::buscarImagens($tenantId, $query);
        } else {
            $imagens = MediaLibraryService::listarImagensDoTenant($tenantId, $folder);
        }
        
        // Estatísticas sempre de todas as imagens (sem filtro)
        $estatisticas = MediaLibraryService::getEstatisticas($tenantId);
        
        $this->viewWithLayout('admin/layouts/store', 'admin/media/index', [
            'tenant' => $tenant,
            'pageTitle' => 'Biblioteca de Mídia',
            'imagens' => $imagens,
            'estatisticas' => $estatisticas,
        ]);
    }
    
    public function listar(): void
    {
        // Limpar qualquer saída anterior
        if (ob_get_level() > 0) {
            ob_clean();
        }
        ob_start();
        
        // Desabilitar exibição de erros para retornar JSON limpo
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        try {
            $tenantId = TenantContext::id();
            $folder = $_GET['folder'] ?? null;
            $query = $_GET['q'] ?? '';
            
            // Logs temporários para debug (remover após identificar problema)
            error_log('[MEDIA PICKER DEBUG] tenant_id = ' . $tenantId);
            error_log('[MEDIA PICKER DEBUG] folder = ' . ($folder ?? 'null'));
            error_log('[MEDIA PICKER DEBUG] query = ' . ($query ?: 'empty'));
            
            if (!empty($query)) {
                $imagens = MediaLibraryService::buscarImagens($tenantId, $query);
            } else {
                $imagens = MediaLibraryService::listarImagensDoTenant($tenantId, $folder);
            }
            
            // Garantir que $imagens é sempre um array
            if (!is_array($imagens)) {
                $imagens = [];
            }
            
            // Logs temporários para debug (reduzidos - paths.php já é logado no service)
            error_log('[MEDIA PICKER DEBUG] quantidade de arquivos encontrados = ' . count($imagens));
            if (count($imagens) > 0) {
                error_log('[MEDIA PICKER DEBUG] primeiro arquivo = ' . json_encode($imagens[0]));
            }
            // Removido: logs de caminho físico (já logados no MediaLibraryService)
            
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'files' => $imagens,
                'count' => count($imagens),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar imagens: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'files' => [],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        } finally {
            // Restaurar configurações de erro
            if (isset($oldErrorReporting)) {
                error_reporting($oldErrorReporting);
            }
            if (isset($oldDisplayErrors)) {
                ini_set('display_errors', $oldDisplayErrors);
            }
        }
    }
    
    public function upload(): void
    {
        // Limpar qualquer saída anterior e iniciar output buffering
        if (ob_get_level() > 0) {
            ob_clean();
        }
        ob_start();
        
        // Desabilitar exibição de erros para retornar JSON limpo
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        // Definir header JSON imediatamente
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        
        // Variáveis para finally
        $errorOccurred = false;
        
        try {
            // Verificar se tenant foi resolvido
            try {
                $tenantId = TenantContext::id();
            } catch (\Exception $e) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro de autenticação. Faça login novamente.',
                ]);
                exit;
            }
            
            // Verificar se há arquivos enviados (suporta 'file' único ou 'imagens[]' múltiplos)
            $hasSingleFile = isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE;
            $hasMultipleFiles = isset($_FILES['imagens']) && is_array($_FILES['imagens']['name']) && count($_FILES['imagens']['name']) > 0;
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (!$hasSingleFile && !$hasMultipleFiles)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum arquivo foi enviado.',
                ]);
                exit;
            }
            
            $folder = $_POST['folder'] ?? 'banners';
            
            // Preparar caminho
            // __DIR__ = src/Http/Controllers/Admin
            // dirname(__DIR__, 4) = raiz do projeto (onde está config/paths.php)
            $paths = require dirname(__DIR__, 4) . '/config/paths.php';
            $uploadsBasePath = $paths['uploads_produtos_base_path'];
            $targetDir = $uploadsBasePath . '/' . $tenantId . '/' . $folder;
            
            // Criar diretório se não existir
            if (!is_dir($targetDir)) {
                if (!@mkdir($targetDir, 0755, true)) {
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao criar diretório de upload. Verifique permissões.',
                    ]);
                    exit;
                }
            }
            
            // Verificar permissões de escrita
            if (!is_writable($targetDir)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Diretório de upload não tem permissão de escrita.',
                ]);
                exit;
            }
            
            // Normalizar arquivos para array
            $filesToProcess = [];
            
            if ($hasSingleFile) {
                // Upload único (compatibilidade com código antigo)
                $filesToProcess[] = $_FILES['file'];
            } elseif ($hasMultipleFiles) {
                // Upload múltiplo - reorganizar array do PHP
                $imagens = $_FILES['imagens'];
                $fileCount = count($imagens['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($imagens['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue; // Pular arquivos vazios
                    }
                    
                    $filesToProcess[] = [
                        'name' => $imagens['name'][$i],
                        'type' => $imagens['type'][$i],
                        'tmp_name' => $imagens['tmp_name'][$i],
                        'error' => $imagens['error'][$i],
                        'size' => $imagens['size'][$i],
                    ];
                }
            }
            
            if (empty($filesToProcess)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum arquivo válido foi selecionado.',
                ]);
                exit;
            }
            
            // Processar cada arquivo
            $uploaded = [];
            $errors = [];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            foreach ($filesToProcess as $file) {
                $fileName = '';
                $errorMsg = '';
                
                // Verificar erros de upload do PHP
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'excede o tamanho máximo do servidor',
                        UPLOAD_ERR_FORM_SIZE => 'excede o tamanho máximo do formulário',
                        UPLOAD_ERR_PARTIAL => 'upload parcial',
                        UPLOAD_ERR_NO_FILE => 'nenhum arquivo',
                        UPLOAD_ERR_NO_TMP_DIR => 'falta pasta temporária',
                        UPLOAD_ERR_CANT_WRITE => 'falha ao escrever no disco',
                        UPLOAD_ERR_EXTENSION => 'bloqueado por extensão',
                    ];
                    $errors[] = $file['name'] . ': ' . ($errorMessages[$file['error']] ?? 'erro desconhecido');
                    continue;
                }
                
                // Validar tamanho
                if ($file['size'] > $maxSize) {
                    $errors[] = $file['name'] . ': arquivo muito grande (máximo 5MB)';
                    continue;
                }
                
                // Validar tipo de arquivo
                $mimeType = $file['type'] ?? '';
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Fallback: tentar usar finfo se disponível
                if (empty($mimeType) && function_exists('finfo_open') && file_exists($file['tmp_name'])) {
                    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mimeType = @finfo_file($finfo, $file['tmp_name']);
                        @finfo_close($finfo);
                    }
                }
                
                if (!in_array($mimeType, $allowedTypes) && !in_array($ext, $allowedExts)) {
                    $errors[] = $file['name'] . ': tipo de arquivo não permitido (use JPG, PNG, WEBP ou GIF)';
                    continue;
                }
                
                // Sanitizar nome do arquivo
                $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $fileName = preg_replace('/_+/', '_', $fileName);
                
                // Se arquivo já existe, adicionar timestamp
                $targetFile = $targetDir . '/' . $fileName;
                if (file_exists($targetFile)) {
                    $info = pathinfo($fileName);
                    $fileName = $info['filename'] . '_' . time() . '_' . uniqid() . '.' . $info['extension'];
                    $targetFile = $targetDir . '/' . $fileName;
                }
                
                // Mover arquivo
                if (@move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $url = "/uploads/tenants/{$tenantId}/{$folder}/{$fileName}";
                    $uploaded[] = [
                        'url' => $url,
                        'filename' => $fileName,
                        'originalName' => $file['name'],
                    ];
                } else {
                    $lastError = error_get_last();
                    $errorMsg = 'erro ao salvar arquivo';
                    if ($lastError && strpos($lastError['message'], 'Permission denied') !== false) {
                        $errorMsg = 'erro de permissão';
                    }
                    $errors[] = $file['name'] . ': ' . $errorMsg;
                }
            }
            
            // Retornar resultado
            ob_clean();
            if (!empty($uploaded)) {
                echo json_encode([
                    'success' => true,
                    'message' => count($uploaded) . ' imagem(ns) enviada(s) com sucesso.',
                    'uploaded' => $uploaded,
                    'errors' => $errors,
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum arquivo foi enviado com sucesso.',
                    'errors' => $errors,
                ]);
            }
            exit;
        } catch (\Throwable $e) {
            $errorOccurred = true;
            // Limpar qualquer saída anterior
            ob_clean();
            
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            ]);
        } finally {
            // Restaurar configurações de erro
            if (isset($oldErrorReporting)) {
                error_reporting($oldErrorReporting);
            }
            if (isset($oldDisplayErrors)) {
                ini_set('display_errors', $oldDisplayErrors);
            }
            
            // Enviar buffer e parar
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
            
            if ($errorOccurred) {
                exit;
            }
        }
    }
}

