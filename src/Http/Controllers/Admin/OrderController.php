<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Shipping\CorreiosLabelService;
use App\Services\Shipping\ContentDeclarationPdfService;
use App\Services\Shipping\ShippingService;
use App\Services\Shipping\ShippingService;

class OrderController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Parâmetros de filtro
        $status = $_GET['status'] ?? '';
        $q = trim($_GET['q'] ?? '');
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($currentPage - 1) * $perPage;

        // Montar query
        $where = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($status) && $status !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if (!empty($q)) {
            $where[] = '(numero_pedido LIKE :q OR cliente_nome LIKE :q OR cliente_email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Contar total
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM pedidos 
            WHERE {$whereClause}
        ");
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // Buscar pedidos
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $pedidos = $stmt->fetchAll();

        $totalPages = ceil($total / $perPage);

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/orders/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Pedidos',
            'pedidos' => $pedidos,
            'filtros' => [
                'status' => $status,
                'q' => $q,
            ],
            'paginacao' => [
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'hasPrev' => $currentPage > 1,
                'hasNext' => $currentPage < $totalPages,
            ],
        ]);
    }

    public function show(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Pedido não encontrado']);
            return;
        }

        // Buscar itens do pedido
        $stmt = $db->prepare("
            SELECT * FROM pedido_itens 
            WHERE tenant_id = :tenant_id 
            AND pedido_id = :pedido_id
            ORDER BY id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'pedido_id' => $pedido['id']
        ]);
        $itens = $stmt->fetchAll();

        // Status disponíveis
        $statusDisponiveis = ['pending', 'paid', 'canceled', 'shipped', 'completed'];

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/orders/show-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Pedido #' . $pedido['numero_pedido'],
            'pedido' => $pedido,
            'itens' => $itens,
            'statusDisponiveis' => $statusDisponiveis,
        ]);
    }

    public function updateStatus(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $novoStatus = $_POST['status'] ?? '';
        $statusValidos = ['pending', 'paid', 'canceled', 'shipped', 'completed'];

        if (!in_array($novoStatus, $statusValidos)) {
            $this->redirect("/admin/pedidos/{$id}?error=status_invalido");
            return;
        }

        // Verificar se pedido existe e pertence ao tenant
        $stmt = $db->prepare("
            SELECT id FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        
        if (!$stmt->fetch()) {
            $this->redirect("/admin/pedidos?error=pedido_nao_encontrado");
            return;
        }

        // Atualizar status
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET status = :status, updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'status' => $novoStatus,
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);

        $this->redirect("/admin/pedidos/{$id}?success=status_atualizado");
    }

    /**
     * Gera etiqueta de frete para o pedido via Correios
     * 
     * POST /admin/pedidos/{id}/frete/gerar-etiqueta
     */
    public function gerarEtiqueta(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido com validações
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            $this->redirect("/admin/pedidos?error=pedido_nao_encontrado");
            return;
        }

        // Validar estado do pedido (não permitir cancelado ou se já tem etiqueta)
        if ($pedido['status'] === 'canceled') {
            $this->redirect("/admin/pedidos/{$id}?error=pedido_cancelado");
            return;
        }

        // Idempotência: se já tem tracking_code, não gerar novamente
        if (!empty($pedido['tracking_code']) || !empty($pedido['label_url']) || !empty($pedido['label_pdf_path'])) {
            // Etiqueta já gerada - melhorar UX
            $labelGeneratedAt = $pedido['label_generated_at'] ?? null;
            $msg = 'Etiqueta já gerada';
            if ($labelGeneratedAt) {
                $msg .= ' em ' . date('d/m/Y H:i', strtotime($labelGeneratedAt));
            }
            $_SESSION['order_message'] = $msg;
            $_SESSION['order_message_type'] = 'info';
            $this->redirect("/admin/pedidos/{$id}?info=etiqueta_ja_gerada");
            return;
        }

        // Validar endereço completo
        $cep = preg_replace('/\D/', '', $pedido['entrega_cep'] ?? '');
        if (empty($cep) || strlen($cep) !== 8) {
            $this->redirect("/admin/pedidos/{$id}?error=cep_invalido");
            return;
        }

        if (empty($pedido['entrega_logradouro']) || empty($pedido['entrega_numero']) || 
            empty($pedido['entrega_bairro']) || empty($pedido['entrega_cidade']) || 
            empty($pedido['entrega_estado'])) {
            $this->redirect("/admin/pedidos/{$id}?error=endereco_incompleto");
            return;
        }

        // Validar método de frete (deve ser PAC ou SEDEX)
        $metodoFrete = $pedido['metodo_frete'] ?? '';
        if (stripos($metodoFrete, 'pac') === false && stripos($metodoFrete, 'sedex') === false) {
            $this->redirect("/admin/pedidos/{$id}?error=metodo_frete_invalido");
            return;
        }

        // Buscar itens do pedido
        $stmt = $db->prepare("
            SELECT * FROM pedido_itens 
            WHERE tenant_id = :tenant_id 
            AND pedido_id = :pedido_id
            ORDER BY id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'pedido_id' => $pedido['id']
        ]);
        $itens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($itens)) {
            $this->redirect("/admin/pedidos/{$id}?error=pedido_sem_itens");
            return;
        }

        // Obter configuração do gateway de frete
        $config = ShippingService::getProviderConfig($tenantId, 'shipping');
        if (empty($config['token']) || empty($config['cep_origem'])) {
            $this->redirect("/admin/pedidos/{$id}?error=gateway_nao_configurado");
            return;
        }

        // Preparar dados do pedido para o serviço
        $pedidoComItens = $pedido;
        $pedidoComItens['itens'] = $itens;

        try {
            // Gerar etiqueta via Correios
            $labelData = CorreiosLabelService::createShipmentFromOrder($pedidoComItens, $config);

            // Obter formato preferido (default A4)
            $labelFormat = trim($_POST['label_format'] ?? 'A4') ?: 'A4';
            if (!in_array($labelFormat, ['A4', '10x15'])) {
                $labelFormat = 'A4';
            }

            // Salvar dados da etiqueta no pedido (com auditoria)
            $stmt = $db->prepare("
                UPDATE pedidos 
                SET shipping_provider = 'correios',
                    tracking_code = :tracking_code,
                    label_url = :label_url,
                    label_id = :label_id,
                    label_pdf_path = :label_pdf_path,
                    label_format = :label_format,
                    label_generated_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id 
                AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'tracking_code' => $labelData['tracking_code'] ?? null,
                'label_url' => $labelData['label_url'] ?? null,
                'label_id' => $labelData['postagem_id'] ?? $labelData['label_id'] ?? null,
                'label_pdf_path' => $labelData['label_pdf_path'] ?? null,
                'label_format' => $labelFormat,
                'id' => $id,
                'tenant_id' => $tenantId,
            ]);

            // Atualizar status para 'shipped' se ainda não estiver
            if (in_array($pedido['status'], ['pending', 'paid'])) {
                $stmt = $db->prepare("
                    UPDATE pedidos 
                    SET status = 'shipped',
                        updated_at = NOW()
                    WHERE id = :id 
                    AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'id' => $id,
                    'tenant_id' => $tenantId,
                ]);
            }

            $this->redirect("/admin/pedidos/{$id}?success=etiqueta_gerada");
        } catch (\Exception $e) {
            error_log("Erro ao gerar etiqueta para pedido #{$id}: " . $e->getMessage());
            $this->redirect("/admin/pedidos/{$id}?error=erro_gerar_etiqueta&msg=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Imprime etiqueta de frete do pedido
     * 
     * GET /admin/pedidos/{id}/frete/imprimir-etiqueta
     * 
     * Ordem de prioridade:
     * 1. label_pdf_path (arquivo local) → stream
     * 2. label_url interno (/admin/...) → stream via endpoint
     * 3. label_url externa → redirect
     * 4. Fallback → erro claro
     */
    public function imprimirEtiqueta(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            $this->redirect("/admin/pedidos?error=pedido_nao_encontrado");
            return;
        }

        // 1. Verificar se existe etiqueta (qualquer formato)
        if (empty($pedido['label_url']) && empty($pedido['label_pdf_path']) && empty($pedido['tracking_code'])) {
            $this->redirect("/admin/pedidos/{$id}?error=etiqueta_nao_gerada");
            return;
        }

        // 2. Prioridade 1: label_pdf_path (arquivo local) → stream
        if (!empty($pedido['label_pdf_path']) && file_exists($pedido['label_pdf_path'])) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="etiqueta-pedido-' . $pedido['numero_pedido'] . '.pdf"');
            header('Content-Length: ' . filesize($pedido['label_pdf_path']));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($pedido['label_pdf_path']);
            exit;
        }

        // 3. Prioridade 2: label_url interno (endpoint interno) → stream via endpoint
        if (!empty($pedido['label_url']) && (strpos($pedido['label_url'], '/admin/') === 0 || strpos($pedido['label_url'], '/ecommerce-v1.0/public/admin/') === 0)) {
            // Redirecionar para endpoint interno de PDF (será implementado)
            $this->redirect($pedido['label_url']);
            return;
        }

        // 4. Prioridade 3: label_url externa → redirect
        if (!empty($pedido['label_url'])) {
            header('Location: ' . $pedido['label_url']);
            exit;
        }

        // 5. Fallback: erro claro orientando o admin
        $_SESSION['order_error'] = 'Etiqueta não disponível. A API dos Correios ainda não está implementada. Configure e gere a etiqueta novamente.';
        $this->redirect("/admin/pedidos/{$id}?error=etiqueta_indisponivel_api_pendente");
    }

    /**
     * Gera e serve PDF da Declaração de Conteúdo do pedido
     * 
     * GET /admin/pedidos/{id}/envio/declaracao-conteudo
     */
    public function declaracaoConteudo(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            http_response_code(404);
            echo "Pedido não encontrado.";
            return;
        }

        // Buscar itens do pedido
        $stmt = $db->prepare("
            SELECT * FROM pedido_itens 
            WHERE tenant_id = :tenant_id 
            AND pedido_id = :pedido_id
            ORDER BY id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'pedido_id' => $pedido['id']
        ]);
        $itens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($itens)) {
            http_response_code(400);
            echo "Pedido sem itens.";
            return;
        }

        // Preparar dados do pedido para o serviço
        $pedidoComItens = $pedido;
        $pedidoComItens['itens'] = $itens;

        // Obter configuração do gateway de frete Correios
        $config = ShippingService::getProviderConfig($tenantId, 'shipping');
        
        // Validar configuração do remetente (formato correios.origem)
        $correiosConfig = $config['correios'] ?? $config;
        $origem = $correiosConfig['origem'] ?? [];
        
        if (empty($origem['cep']) || empty($origem['nome'])) {
            // Redirecionar com erro claro
            $_SESSION['order_error'] = 'Remetente não configurado. Configure o remetente em Gateways → Frete → Correios.';
            $this->redirect("/admin/pedidos/{$id}?error=remetente_nao_configurado");
            return;
        }

        try {
            // Gerar PDF
            $pdfContent = ContentDeclarationPdfService::generateForOrder($pedidoComItens, $correiosConfig);

            // Enviar PDF como resposta
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="declaracao-conteudo-pedido-' . $pedido['numero_pedido'] . '.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdfContent;
            exit;
        } catch (\Exception $e) {
            error_log("Erro ao gerar Declaração de Conteúdo para pedido #{$id}: " . $e->getMessage());
            http_response_code(500);
            echo "Erro ao gerar PDF: " . htmlspecialchars($e->getMessage());
            return;
        }
    }

    /**
     * Atualiza documento de envio do pedido
     * 
     * POST /admin/pedidos/{id}/documento-envio
     */
    public function updateDocumentoEnvio(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            $this->redirect("/admin/pedidos?error=pedido_nao_encontrado");
            return;
        }

        $documentoEnvio = trim($_POST['documento_envio'] ?? '');
        $nfReference = trim($_POST['nf_reference'] ?? '');

        // Validar tipo de documento
        if (!in_array($documentoEnvio, ['declaracao_conteudo', 'nota_fiscal'])) {
            $this->redirect("/admin/pedidos/{$id}?error=documento_invalido");
            return;
        }

        try {
            // Atualizar documento no pedido
            $stmt = $db->prepare("
                UPDATE pedidos 
                SET documento_envio = :documento_envio,
                    nf_reference = :nf_reference,
                    updated_at = NOW()
                WHERE id = :id 
                AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'documento_envio' => $documentoEnvio,
                'nf_reference' => !empty($nfReference) ? $nfReference : null,
                'id' => $id,
                'tenant_id' => $tenantId,
            ]);

            $this->redirect("/admin/pedidos/{$id}?success=documento_atualizado");
        } catch (\Exception $e) {
            error_log("Erro ao atualizar documento de envio do pedido #{$id}: " . $e->getMessage());
            $this->redirect("/admin/pedidos/{$id}?error=erro_atualizar_documento");
        }
    }

    /**
     * Serve PDF da etiqueta (endpoint interno)
     * 
     * GET /admin/pedidos/{id}/frete/etiqueta/pdf
     * 
     * Endpoint interno para servir PDF quando label_url aponta para este endpoint
     * ou quando label_pdf_path está disponível.
     */
    public function etiquetaPdf(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            http_response_code(404);
            echo "Pedido não encontrado.";
            return;
        }

        // Prioridade 1: label_pdf_path (arquivo local)
        if (!empty($pedido['label_pdf_path']) && file_exists($pedido['label_pdf_path'])) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="etiqueta-pedido-' . $pedido['numero_pedido'] . '.pdf"');
            header('Content-Length: ' . filesize($pedido['label_pdf_path']));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($pedido['label_pdf_path']);
            exit;
        }

        // Prioridade 2: Obter PDF via CorreiosLabelService (quando API estiver implementada)
        // Por enquanto, placeholder
        try {
            $config = ShippingService::getProviderConfig($tenantId, 'shipping');
            $labelId = $pedido['label_id'] ?? null;

            if (empty($labelId)) {
                throw new \Exception('ID da etiqueta não disponível.');
            }

            // ⚠️ IMPLEMENTAR: CorreiosLabelService::getLabelPdf($labelId, $config)
            // Por enquanto, retorna erro claro
            throw new \Exception('Geração de PDF via API dos Correios ainda não implementada. PDF não disponível.');
        } catch (\Exception $e) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PDF não disponível</title></head><body>';
            echo '<h1>PDF não disponível</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><small>Status: API dos Correios pendente de implementação.</small></p>';
            echo '</body></html>';
            return;
        }
    }

}


