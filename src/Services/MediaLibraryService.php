<?php

namespace App\Services;

class MediaLibraryService
{
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
        
        $arquivos = [];
        
        // Definir pastas a escanear
        $pastas = [
            'category-pills' => 'Categorias em Destaque',
            'produtos' => 'Produtos',
            'logo' => 'Logos',
            'banners' => 'Banners',
        ];
        
        // Se folder foi especificado, filtrar apenas essa pasta
        if ($folder !== null && isset($pastas[$folder])) {
            $pastas = [$folder => $pastas[$folder]];
        }
        
        foreach ($pastas as $pasta => $label) {
            $baseDir = $uploadsBasePath . '/' . $tenantId . '/' . $pasta;
            $baseUrl = "/uploads/tenants/{$tenantId}/{$pasta}";
            
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

        // Ordenar por nome
        usort($arquivos, function($a, $b) {
            return strcmp($a['filename'], $b['filename']);
        });

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

