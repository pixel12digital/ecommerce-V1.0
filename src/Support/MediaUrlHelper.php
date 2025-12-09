<?php

namespace App\Support;

/**
 * Helper centralizado para gerar URLs de mídia
 * 
 * Garante que todas as URLs de mídia sejam geradas de forma consistente
 * em todo o sistema (admin e storefront), evitando problemas de dupla barra
 * ou caminhos incorretos.
 * 
 * Uso:
 *   MediaUrlHelper::url('/uploads/tenants/1/banners/golfe04.webp')
 *   MediaUrlHelper::url('uploads/tenants/1/banners/golfe04.webp')  // Remove barra inicial se existir
 * 
 * Retorna sempre: {basePath}/uploads/tenants/...
 * 
 * Em dev: /ecommerce-v1.0/public/uploads/tenants/...
 * Em produção: /uploads/tenants/...
 */
class MediaUrlHelper
{
    /**
     * Gera URL completa de mídia normalizada
     * 
     * @param string $relativePath Caminho relativo (ex: '/uploads/tenants/1/banners/arquivo.webp' ou 'uploads/tenants/1/banners/arquivo.webp')
     * @return string URL completa com basePath aplicado
     */
    public static function url(string $relativePath): string
    {
        // Detectar basePath automaticamente
        $basePath = self::getBasePath();
        
        // Normalizar caminho: remover barras duplicadas e garantir que comece com /
        $normalizedPath = '/' . ltrim($relativePath, '/');
        
        // Se basePath estiver vazio, retornar apenas o caminho normalizado
        if (empty($basePath)) {
            return $normalizedPath;
        }
        
        // Combinar basePath + caminho normalizado
        return rtrim($basePath, '/') . $normalizedPath;
    }
    
    /**
     * Detecta o basePath automaticamente baseado no ambiente
     * 
     * @return string BasePath (ex: '/ecommerce-v1.0/public' em dev, '' em produção)
     */
    private static function getBasePath(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Em desenvolvimento local, geralmente há /ecommerce-v1.0/public na URI
        if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
            return '/ecommerce-v1.0/public';
        }
        
        // Se SCRIPT_NAME contém o caminho do projeto, usar isso
        if (strpos($scriptName, '/ecommerce-v1.0/public') !== false) {
            return '/ecommerce-v1.0/public';
        }
        
        // Em produção (Hostinger), basePath é vazio
        return '';
    }
    
    /**
     * Verifica se uma URL de mídia é válida (não vazia e começa com /uploads)
     * 
     * @param string|null $url URL a verificar
     * @return bool True se válida, false caso contrário
     */
    public static function isValid(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        
        // Deve começar com /uploads/tenants
        return strpos($url, '/uploads/tenants/') === 0;
    }
}

