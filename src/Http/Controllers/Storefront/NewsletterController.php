<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class NewsletterController extends Controller
{
    public function store(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/?newsletter=error');
            return;
        }

        // Verificar se já existe
        $stmt = $db->prepare("
            SELECT id FROM newsletter_inscricoes 
            WHERE tenant_id = :tenant_id AND email = :email
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'email' => $email]);
        
        if ($stmt->fetch()) {
            $this->redirect('/?newsletter=exists');
            return;
        }

        // Inserir
        $stmt = $db->prepare("
            INSERT INTO newsletter_inscricoes 
            (tenant_id, nome, email, origem, created_at)
            VALUES (:tenant_id, :nome, :email, 'home', NOW())
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'nome' => $nome ?: null,
            'email' => $email
        ]);

        $this->redirect('/?newsletter=ok');
    }
}


