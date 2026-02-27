<?php

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class CategorySizeController extends Controller
{
    public function getSizesByCategory(): void
    {
        header('Content-Type: application/json');
        
        $tenantId = TenantContext::id();
        $db = Database::getConnection();
        
        $categoriaSlug = $_GET['categoria'] ?? '';
        
        if (empty($categoriaSlug)) {
            echo json_encode(['tamanhos' => []]);
            return;
        }
        
        // Buscar categoria por slug
        $stmt = $db->prepare("
            SELECT id FROM categorias 
            WHERE tenant_id = :tenant_id AND slug = :slug
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'slug' => $categoriaSlug]);
        $categoria = $stmt->fetch();
        
        if (!$categoria) {
            echo json_encode(['tamanhos' => []]);
            return;
        }
        
        $categoriaId = $categoria['id'];
        
        // Buscar subcategorias (filhos diretos)
        $stmt = $db->prepare("SELECT id FROM categorias WHERE tenant_id = :tenant_id AND categoria_pai_id = :categoria_pai_id");
        $stmt->execute(['tenant_id' => $tenantId, 'categoria_pai_id' => $categoriaId]);
        $subcategorias = $stmt->fetchAll();
        $subcategoriaIds = array_column($subcategorias, 'id');
        
        // Lista de IDs de categorias para buscar tamanhos (pai + filhos)
        $categoriaIds = array_merge([$categoriaId], $subcategoriaIds);
        
        // Montar IN clause com placeholders
        $placeholders = [];
        $params = [
            'tenant_id_at' => $tenantId,
            'tenant_id_pat' => $tenantId,
            'tenant_id_prod' => $tenantId,
            'tenant_id_pc' => $tenantId
        ];
        
        foreach ($categoriaIds as $idx => $catId) {
            $key = "categoria_id_{$idx}";
            $placeholders[] = ":{$key}";
            $params[$key] = $catId;
        }
        
        // Buscar tamanhos disponíveis nesta categoria (incluindo subcategorias)
        $sqlTamanhos = "
            SELECT DISTINCT at.id, at.nome, at.slug, a.nome as atributo_nome
            FROM atributo_termos at
            INNER JOIN atributos a ON a.id = at.atributo_id
            INNER JOIN produto_atributo_termos pat ON pat.atributo_termo_id = at.id AND pat.tenant_id = :tenant_id_pat
            INNER JOIN produtos p ON p.id = pat.produto_id AND p.tenant_id = :tenant_id_prod
            WHERE at.tenant_id = :tenant_id_at
            AND p.status = 'publish'
            AND p.exibir_no_catalogo = 1
            AND EXISTS (
                SELECT 1 FROM produto_categorias pc 
                WHERE pc.produto_id = p.id 
                AND pc.tenant_id = :tenant_id_pc
                AND pc.categoria_id IN (" . implode(',', $placeholders) . ")
            )
            ORDER BY a.ordem ASC, at.ordem ASC, at.nome ASC
        ";
        
        $stmtTamanhos = $db->prepare($sqlTamanhos);
        $stmtTamanhos->execute($params);
        
        $tamanhos = $stmtTamanhos->fetchAll(\PDO::FETCH_ASSOC);
        
        echo json_encode(['tamanhos' => $tamanhos]);
    }
}
