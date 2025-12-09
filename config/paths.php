<?php

/**
 * Configuração de caminhos do sistema
 * 
 * IMPORTANTE - Caminhos de Uploads:
 * 
 * O caminho físico dos uploads varia conforme o ambiente:
 * 
 * - DESENVOLVIMENTO (DocumentRoot = public/):
 *   Caminho físico: {raiz}/public/uploads/tenants
 *   URL pública: /uploads/tenants/...
 * 
 * - PRODUÇÃO HOSTINGER (DocumentRoot = public_html/):
 *   Caminho físico: {raiz}/uploads/tenants (ou public_html/uploads/tenants)
 *   URL pública: /uploads/tenants/...
 * 
 * O código sempre gera URLs como /uploads/tenants/... (sem /public).
 * O Apache resolve essas URLs baseado no DocumentRoot:
 * - Dev: DocumentRoot = public/ → busca em public/uploads/tenants/
 * - Prod: DocumentRoot = public_html/ → busca em public_html/uploads/tenants/
 * 
 * Por isso, em produção na Hostinger, os arquivos devem estar em:
 * public_html/uploads/tenants/... (NÃO em public_html/public/uploads/tenants/...)
 */

$root = dirname(__DIR__);

// Detectar caminho físico dos uploads automaticamente
// Prioridade: 1) public/uploads/tenants (dev), 2) uploads/tenants (prod Hostinger)
$uploadsBasePath = null;

// Tentar caminho de desenvolvimento primeiro
$devPath = $root . '/public/uploads/tenants';
if (is_dir($devPath)) {
    $uploadsBasePath = $devPath;
} else {
    // Tentar caminho de produção (Hostinger)
    $prodPath = $root . '/uploads/tenants';
    if (is_dir($prodPath)) {
        $uploadsBasePath = $prodPath;
    } else {
        // Fallback: usar caminho de desenvolvimento (será criado automaticamente)
        $uploadsBasePath = $devPath;
    }
}

return [
    'root' => $root,
    'public' => $root . '/public',
    'src' => $root . '/src',
    'config' => $root . '/config',
    'database' => $root . '/database',
    'storage' => $root . '/storage',
    'themes' => $root . '/themes',
    'vendor' => $root . '/vendor',
    'exportacao_produtos_path' => $root . '/exportacao-produtos-2025-12-05_11-36-53',
    'uploads_produtos_base_path' => $uploadsBasePath,
];

