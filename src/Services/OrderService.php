<?php

namespace App\Services;

use App\Core\Database;
use App\Tenant\TenantContext;

class OrderService
{
    /**
     * Gera número de pedido único
     * Formato: PG-{ANO}-{SEQUENCIAL}
     * Exemplo: PG-2025-000001
     */
    public static function gerarNumeroPedido(int $tenantId): string
    {
        $db = Database::getConnection();
        $ano = date('Y');
        
        // Buscar último número do ano atual
        $stmt = $db->prepare("
            SELECT numero_pedido 
            FROM pedidos 
            WHERE tenant_id = :tenant_id 
            AND numero_pedido LIKE :pattern
            ORDER BY numero_pedido DESC 
            LIMIT 1
        ");
        $pattern = "PG-{$ano}-%";
        $stmt->execute(['tenant_id' => $tenantId, 'pattern' => $pattern]);
        $ultimo = $stmt->fetchColumn();
        
        if ($ultimo) {
            // Extrair sequencial e incrementar
            $partes = explode('-', $ultimo);
            $sequencial = (int)end($partes) + 1;
        } else {
            // Primeiro pedido do ano
            $sequencial = 1;
        }
        
        return sprintf('PG-%s-%06d', $ano, $sequencial);
    }
}


