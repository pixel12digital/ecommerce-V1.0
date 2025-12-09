<?php

namespace App\Repositories;

use App\Core\Database;

class ContactMessageRepository
{
    /**
     * Cria uma nova mensagem de contato
     */
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO contact_messages 
            (tenant_id, nome, email, telefone, tipo_assunto, numero_pedido, mensagem, status, origin_url, created_at)
            VALUES (:tenant_id, :nome, :email, :telefone, :tipo_assunto, :numero_pedido, :mensagem, :status, :origin_url, NOW())
        ");
        
        $stmt->execute([
            'tenant_id' => $data['tenant_id'],
            'nome' => $data['nome'],
            'email' => $data['email'],
            'telefone' => $data['telefone'] ?? null,
            'tipo_assunto' => $data['tipo_assunto'],
            'numero_pedido' => $data['numero_pedido'] ?? null,
            'mensagem' => $data['mensagem'],
            'status' => $data['status'] ?? 'novo',
            'origin_url' => $data['origin_url'] ?? null,
        ]);
        
        return (int)$db->lastInsertId();
    }
}

