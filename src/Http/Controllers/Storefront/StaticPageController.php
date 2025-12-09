<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Services\ThemeConfig;
use App\Tenant\TenantContext;
use App\Services\CartService;
use App\Repositories\ContactMessageRepository;
use App\Services\EmailService;
use App\Core\Database;

class StaticPageController extends Controller
{
    /**
     * Renderiza página Sobre
     */
    public function sobre(): void
    {
        $page = ThemeConfig::getPage('sobre');
        $this->renderPage('sobre', $page);
    }

    /**
     * Renderiza página Contato
     */
    public function contato(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $page = ThemeConfig::getPage('contato');
        
        // Obter dados da loja
        $tenant = TenantContext::tenant();
        $storeContact = [
            'nome' => $tenant->name,
            'phone' => ThemeConfig::get('footer_phone', ''),
            'whatsapp' => ThemeConfig::get('footer_whatsapp', ''),
            'email' => ThemeConfig::get('footer_email', ''),
            'address' => ThemeConfig::get('footer_address', ''),
        ];
        
        // Mensagens flash
        $message = $_SESSION['contact_message'] ?? null;
        $messageType = $_SESSION['contact_message_type'] ?? null;
        $errors = $_SESSION['contact_errors'] ?? [];
        $oldData = $_SESSION['contact_old_data'] ?? [];
        
        // Limpar mensagens flash
        unset($_SESSION['contact_message'], $_SESSION['contact_message_type'], $_SESSION['contact_errors'], $_SESSION['contact_old_data']);
        
        // Pré-preencher dados se cliente estiver logado
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            $db = Database::getConnection();
            $tenantId = TenantContext::id();
            $stmt = $db->prepare("
                SELECT nome, email, telefone 
                FROM customers 
                WHERE id = :customer_id AND tenant_id = :tenant_id
                LIMIT 1
            ");
            $stmt->execute([
                'customer_id' => $_SESSION['customer_id'],
                'tenant_id' => $tenantId,
            ]);
            $customer = $stmt->fetch();
            
            if ($customer && empty($oldData)) {
                $oldData = [
                    'nome' => $customer['nome'] ?? '',
                    'email' => $customer['email'] ?? '',
                    'telefone' => $customer['telefone'] ?? '',
                ];
            }
        }
        
        // Extrair variáveis para serem usadas na view
        extract([
            'page' => $page,
            'storeContact' => $storeContact,
            'message' => $message,
            'messageType' => $messageType,
            'errors' => $errors,
            'oldData' => $oldData,
        ]);
        
        // Incluir a view de contato específica
        // __DIR__ = src/Http/Controllers/Storefront/
        // Precisamos subir 4 níveis para chegar na raiz: ../../../../themes/...
        require __DIR__ . '/../../../../themes/default/storefront/pages/contato.php';
    }
    
    /**
     * Processa o envio do formulário de contato
     */
    public function enviarContato(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $tenant = TenantContext::tenant();
        $tenantId = $tenant->id;
        
        // Ler dados do POST
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $tipoAssunto = $_POST['tipo_assunto'] ?? '';
        $numeroPedido = trim($_POST['numero_pedido'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');
        
        // Validar dados
        $errors = [];
        $tiposAssuntoPermitidos = [
            'duvidas_produtos',
            'pedido_andamento',
            'trocas_devolucoes',
            'pagamento',
            'problema_site',
            'outros',
        ];
        
        // Nome
        if (empty($nome)) {
            $errors[] = 'Nome é obrigatório';
        } elseif (strlen($nome) < 3) {
            $errors[] = 'Nome deve ter pelo menos 3 caracteres';
        } elseif (strlen($nome) > 255) {
            $errors[] = 'Nome deve ter no máximo 255 caracteres';
        }
        
        // E-mail
        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }
        
        // Telefone (opcional)
        if (!empty($telefone) && strlen($telefone) > 50) {
            $errors[] = 'Telefone deve ter no máximo 50 caracteres';
        }
        
        // Tipo de assunto
        if (empty($tipoAssunto)) {
            $errors[] = 'Tipo de assunto é obrigatório';
        } elseif (!in_array($tipoAssunto, $tiposAssuntoPermitidos)) {
            $errors[] = 'Tipo de assunto inválido';
        }
        
        // Número do pedido (obrigatório para alguns tipos)
        $tiposQueExigemPedido = ['pedido_andamento', 'trocas_devolucoes', 'pagamento'];
        if (in_array($tipoAssunto, $tiposQueExigemPedido) && empty($numeroPedido)) {
            $errors[] = 'Número do pedido é obrigatório para este tipo de assunto';
        }
        
        // Mensagem
        if (empty($mensagem)) {
            $errors[] = 'Mensagem é obrigatória';
        } elseif (strlen($mensagem) < 10) {
            $errors[] = 'Mensagem deve ter pelo menos 10 caracteres';
        }
        
        // Se houver erros, redirecionar de volta
        if (!empty($errors)) {
            $_SESSION['contact_errors'] = $errors;
            $_SESSION['contact_old_data'] = [
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'tipo_assunto' => $tipoAssunto,
                'numero_pedido' => $numeroPedido,
                'mensagem' => $mensagem,
            ];
            $this->redirect('/contato');
            return;
        }
        
        // Preparar dados para salvar
        $data = [
            'tenant_id' => $tenantId,
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone ?: null,
            'tipo_assunto' => $tipoAssunto,
            'numero_pedido' => $numeroPedido ?: null,
            'mensagem' => $mensagem,
            'status' => 'novo',
            'origin_url' => $_SERVER['HTTP_REFERER'] ?? null,
        ];
        
        try {
            // Salvar mensagem
            ContactMessageRepository::create($data);
            
            // Enviar e-mail para o lojista
            $storeEmail = ThemeConfig::get('contact_email', '');
            if (empty($storeEmail)) {
                $storeEmail = ThemeConfig::get('footer_email', '');
            }
            
            if (!empty($storeEmail)) {
                $emailData = array_merge($data, [
                    'loja_nome' => $tenant->name,
                ]);
                EmailService::sendContactMessage($emailData, $storeEmail, $tenant->name);
            }
            
            // Mensagem de sucesso
            $_SESSION['contact_message'] = 'Sua mensagem foi enviada com sucesso. Em breve entraremos em contato.';
            $_SESSION['contact_message_type'] = 'success';
            
        } catch (\Exception $e) {
            // Em caso de erro, redirecionar com mensagem de erro
            $_SESSION['contact_message'] = 'Erro ao enviar mensagem. Por favor, tente novamente.';
            $_SESSION['contact_message_type'] = 'error';
            $_SESSION['contact_old_data'] = [
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'tipo_assunto' => $tipoAssunto,
                'numero_pedido' => $numeroPedido,
                'mensagem' => $mensagem,
            ];
        }
        
        $this->redirect('/contato');
    }

    /**
     * Renderiza página Trocas e Devoluções
     */
    public function trocasDevolucoes(): void
    {
        $page = ThemeConfig::getPage('trocas_devolucoes');
        $this->renderPage('trocas-devolucoes', $page);
    }

    /**
     * Renderiza página Frete e Prazos
     */
    public function fretePrazos(): void
    {
        $page = ThemeConfig::getPage('frete_prazos');
        $this->renderPage('frete-prazos', $page);
    }

    /**
     * Renderiza página Formas de Pagamento
     */
    public function formasPagamento(): void
    {
        $page = ThemeConfig::getPage('formas_pagamento');
        $this->renderPage('formas-pagamento', $page);
    }

    /**
     * Renderiza página FAQ
     */
    public function faq(): void
    {
        $page = ThemeConfig::getPage('faq');
        $faqItems = $page['items'] ?? [];
        
        // Extrair variáveis para serem usadas na view
        extract([
            'page' => $page,
            'faqItems' => $faqItems,
        ]);
        
        // Incluir a view específica da FAQ (com accordion)
        require __DIR__ . '/../../../../themes/default/storefront/pages/faq.php';
    }

    /**
     * Renderiza página Política de Privacidade
     */
    public function politicaPrivacidade(): void
    {
        $page = ThemeConfig::getPage('politica_privacidade');
        $this->renderPage('politica-privacidade', $page);
    }

    /**
     * Renderiza página Termos de Uso
     */
    public function termosUso(): void
    {
        $page = ThemeConfig::getPage('termos_uso');
        $this->renderPage('termos-uso', $page);
    }

    /**
     * Renderiza página Política de Cookies
     */
    public function politicaCookies(): void
    {
        $page = ThemeConfig::getPage('politica_cookies');
        $this->renderPage('politica-cookies', $page);
    }

    /**
     * Renderiza página Seja Parceiro
     */
    public function sejaParceiro(): void
    {
        $page = ThemeConfig::getPage('seja_parceiro');
        $this->renderPage('seja-parceiro', $page);
    }

    /**
     * Renderiza uma página usando a view base
     * 
     * Garante que a página sempre tenha estrutura válida antes de renderizar.
     * Se o conteúdo estiver vazio ou nulo, usa valores padrão para evitar erros 500.
     * 
     * @param string $viewName Nome da view (usado apenas para referência, não é incluído)
     * @param array $page Dados da página retornados por ThemeConfig::getPage()
     */
    private function renderPage(string $viewName, array $page): void
    {
        // Garantir que $page sempre tenha estrutura válida
        // ThemeConfig::getPage() já garante isso, mas adicionamos uma camada extra de segurança
        if (empty($page) || !is_array($page)) {
            $page = [
                'title' => 'Página',
                'content' => '<p>Conteúdo em breve.</p>',
            ];
        }
        
        // Garantir que campos obrigatórios existam
        $page['title'] = $page['title'] ?? 'Página';
        $page['content'] = $page['content'] ?? '';
        
        // Extrair variáveis para serem usadas na view
        extract([
            'page' => $page,
        ]);
        
        // Incluir a view base que já tem toda a estrutura
        // __DIR__ = src/Http/Controllers/Storefront/
        // Precisamos subir 4 níveis para chegar na raiz: ../../../../themes/...
        require __DIR__ . '/../../../../themes/default/storefront/pages/base.php';
    }
}

