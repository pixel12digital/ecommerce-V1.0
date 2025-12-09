<?php

namespace App\Services;

class MediaLibraryService
{
    /**
     * Helper para verificar se está em modo debug
     * Logs só são escritos se APP_DEBUG=true ou APP_ENV=development
     */
    private static function isDebugMode(): bool
    {
        $config = require __DIR__ . '/../../config/app.php';
        return ($config['debug'] ?? false) === true || ($config['env'] ?? 'production') === 'development';
    }
    
    /**
     * Helper para log condicional (apenas em modo debug)
     */
    private static function debugLog(string $message): void
    {
        if (self::isDebugMode()) {
            error_log($message);
        }
    }
    /**
     * Lista todas as imagens disponíveis para um tenant
     * 
     * @param int $tenantId ID do tenant
     * @param string|null $folder Filtro opcional por pasta (ex: 'produtos', 'category-pills')
     * @return array Array de imagens com url, filename, folder, folderLabel
     */
    public static function listarImagensDoTenant(int $tenantId, ?string $folder = null): array
    {
        $paths = require __DIR__ . '/../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        
        // Logs condicionais (apenas em modo debug)
        self::debugLog('[MEDIA SERVICE DEBUG] ===== INÍCIO listarImagensDoTenant =====');
        self::debugLog('[MEDIA SERVICE DEBUG] tenant_id = ' . $tenantId);
        self::debugLog('[MEDIA SERVICE DEBUG] folder = ' . ($folder ?? 'null'));
        self::debugLog('[MEDIA SERVICE DEBUG] uploads_produtos_base_path = ' . $uploadsBasePath);
        self::debugLog('[MEDIA SERVICE DEBUG] caminho completo base = ' . $uploadsBasePath . '/' . $tenantId);
        
        $arquivos = [];
        
        // Definir pastas a escanear
        $pastas = [
            'category-pills' => 'Categorias em Destaque',
            'produtos' => 'Produtos',
            'logo' => 'Logos',
            'banners' => 'Banners',
        ];
        
        // Se folder foi especificado, filtrar apenas essa pasta
        // MAS: se a pasta filtrada estiver vazia, fazer fallback para todas as pastas
        $folderEspecifico = null;
        if ($folder !== null && isset($pastas[$folder])) {
            $folderEspecifico = $folder;
            $pastas = [$folder => $pastas[$folder]];
            self::debugLog('[MEDIA SERVICE DEBUG] Filtrando apenas pasta: ' . $folder);
        } else {
            self::debugLog('[MEDIA SERVICE DEBUG] Sem filtro de pasta - escaneando todas as pastas');
        }
        
        foreach ($pastas as $pasta => $label) {
            $baseDir = $uploadsBasePath . '/' . $tenantId . '/' . $pasta;
            $baseUrl = "/uploads/tenants/{$tenantId}/{$pasta}";
            
            // Logs condicionais (apenas em modo debug)
            self::debugLog('[MEDIA SERVICE DEBUG] Verificando pasta: ' . $pasta);
            self::debugLog('[MEDIA SERVICE DEBUG] baseDir = ' . $baseDir);
            self::debugLog('[MEDIA SERVICE DEBUG] baseDir existe? ' . (is_dir($baseDir) ? 'SIM' : 'NÃO'));
            
            if (is_dir($baseDir)) {
                $handle = opendir($baseDir);
                if ($handle) {
                    $filesInDir = 0;
                    while (($file = readdir($handle)) !== false) {
                        if ($file === '.' || $file === '..') {
                            continue;
                        }

                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        // Log apenas se houver muitos arquivos ou em caso de erro (reduzir verbosidade)
                        
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                            continue; // Removido log de arquivo ignorado para reduzir verbosidade
                        }

                        $arquivos[] = [
                            'url' => $baseUrl . '/' . $file,
                            'filename' => $file,
                            'folder' => $pasta,
                            'folderLabel' => $label,
                            'size' => file_exists($baseDir . '/' . $file) ? filesize($baseDir . '/' . $file) : 0,
                        ];
                        $filesInDir++;
                    }
                    closedir($handle);
                    self::debugLog('[MEDIA SERVICE DEBUG] Arquivos válidos encontrados na pasta ' . $pasta . ': ' . $filesInDir);
                } else {
                    self::debugLog('[MEDIA SERVICE DEBUG] Erro ao abrir diretório: ' . $baseDir);
                }
            } else {
                self::debugLog('[MEDIA SERVICE DEBUG] Diretório não existe: ' . $baseDir);
            }
        }

        // Se foi filtrado por uma pasta específica e não encontrou nada, fazer fallback para todas as pastas
        if ($folderEspecifico !== null && count($arquivos) === 0) {
            self::debugLog('[MEDIA SERVICE DEBUG] Pasta ' . $folderEspecifico . ' está vazia - fazendo fallback para todas as pastas');
            
            // Reconstruir array de todas as pastas
            $todasPastas = [
                'category-pills' => 'Categorias em Destaque',
                'produtos' => 'Produtos',
                'logo' => 'Logos',
                'banners' => 'Banners',
            ];
            
            // Escanear todas as pastas novamente
            foreach ($todasPastas as $pasta => $label) {
                $baseDir = $uploadsBasePath . '/' . $tenantId . '/' . $pasta;
                $baseUrl = "/uploads/tenants/{$tenantId}/{$pasta}";
                
                self::debugLog('[MEDIA SERVICE DEBUG] [FALLBACK] Verificando pasta: ' . $pasta);
                self::debugLog('[MEDIA SERVICE DEBUG] [FALLBACK] baseDir = ' . $baseDir);
                
                if (is_dir($baseDir)) {
                    $handle = opendir($baseDir);
                    if ($handle) {
                        while (($file = readdir($handle)) !== false) {
                            if ($file === '.' || $file === '..') {
                                continue;
                            }

                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                                continue;
                            }

                            $arquivos[] = [
                                'url' => $baseUrl . '/' . $file,
                                'filename' => $file,
                                'folder' => $pasta,
                                'folderLabel' => $label,
                                'size' => file_exists($baseDir . '/' . $file) ? filesize($baseDir . '/' . $file) : 0,
                            ];
                        }
                        closedir($handle);
                    }
                }
            }
            
            self::debugLog('[MEDIA SERVICE DEBUG] [FALLBACK] Total após fallback: ' . count($arquivos));
        }

        // Ordenar por nome
        usort($arquivos, function($a, $b) {
            return strcmp($a['filename'], $b['filename']);
        });

        // Log final (apenas em modo debug)
        self::debugLog('[MEDIA SERVICE DEBUG] Total de arquivos encontrados: ' . count($arquivos));
        self::debugLog('[MEDIA SERVICE DEBUG] ===== FIM listarImagensDoTenant =====');

        return $arquivos;
    }
    
    /**
     * Busca imagens por nome de arquivo
     * 
     * @param int $tenantId ID do tenant
     * @param string $query Termo de busca
     * @return array Array de imagens filtradas
     */
    public static function buscarImagens(int $tenantId, string $query): array
    {
        $imagens = self::listarImagensDoTenant($tenantId);
        $query = strtolower(trim($query));
        
        if (empty($query)) {
            return $imagens;
        }
        
        return array_filter($imagens, function($img) use ($query) {
            return strpos(strtolower($img['filename']), $query) !== false;
        });
    }
    
    /**
     * Retorna estatísticas de mídia do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Estatísticas por pasta
     */
    public static function getEstatisticas(int $tenantId): array
    {
        $imagens = self::listarImagensDoTenant($tenantId);
        $stats = [];
        
        foreach ($imagens as $img) {
            $folder = $img['folder'];
            if (!isset($stats[$folder])) {
                $stats[$folder] = [
                    'label' => $img['folderLabel'],
                    'count' => 0,
                    'totalSize' => 0,
                ];
            }
            $stats[$folder]['count']++;
            $stats[$folder]['totalSize'] += $img['size'];
        }
        
        return $stats;
    }
}

