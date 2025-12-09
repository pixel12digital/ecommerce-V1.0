<?php

namespace App\Services;

class EmailService
{
    /**
     * Envia um e-mail simples
     */
    public static function send(string $to, string $subject, string $body, string $fromEmail = null, string $fromName = null): bool
    {
        if (empty($to)) {
            return false;
        }
        
        // Headers do e-mail
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        
        if ($fromEmail) {
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;
            $headers[] = "From: {$from}";
            $headers[] = "Reply-To: {$fromEmail}";
        }
        
        $headersString = implode("\r\n", $headers);
        
        // Tentar enviar usando mail() do PHP
        return @mail($to, $subject, $body, $headersString);
    }
    
    /**
     * Envia e-mail de contato para o lojista
     */
    public static function sendContactMessage(array $data, string $storeEmail, string $storeName = null): bool
    {
        if (empty($storeEmail)) {
            return false;
        }
        
        // Mapear tipo de assunto para texto legível
        $tiposAssunto = [
            'duvidas_produtos' => 'Dúvidas sobre produtos',
            'pedido_andamento' => 'Pedido em andamento',
            'trocas_devolucoes' => 'Trocas e devoluções',
            'pagamento' => 'Problemas com pagamento',
            'problema_site' => 'Problemas no site',
            'outros' => 'Outros assuntos',
        ];
        
        $tipoAssuntoLegivel = $tiposAssunto[$data['tipo_assunto']] ?? 'Outros assuntos';
        
        // Montar assunto
        $subject = "[Contato] {$data['nome']} - {$tipoAssuntoLegivel}";
        
        // Montar corpo do e-mail
        $body = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>";
        $body .= "<h2 style='color: #2E7D32;'>Nova mensagem de contato</h2>";
        $body .= "<div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        $body .= "<p><strong>Nome:</strong> " . htmlspecialchars($data['nome']) . "</p>";
        $body .= "<p><strong>E-mail:</strong> " . htmlspecialchars($data['email']) . "</p>";
        
        if (!empty($data['telefone'])) {
            $body .= "<p><strong>Telefone:</strong> " . htmlspecialchars($data['telefone']) . "</p>";
        }
        
        $body .= "<p><strong>Tipo de assunto:</strong> {$tipoAssuntoLegivel}</p>";
        
        if (!empty($data['numero_pedido'])) {
            $body .= "<p><strong>Número do pedido:</strong> " . htmlspecialchars($data['numero_pedido']) . "</p>";
        }
        
        $body .= "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
        
        if (!empty($data['loja_nome'])) {
            $body .= "<p><strong>Loja:</strong> " . htmlspecialchars($data['loja_nome']) . "</p>";
        }
        
        $body .= "</div>";
        $body .= "<div style='margin: 20px 0;'>";
        $body .= "<h3 style='color: #333;'>Mensagem:</h3>";
        $body .= "<div style='background: white; padding: 15px; border-left: 4px solid #F7931E; margin-top: 10px;'>";
        $body .= "<p style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($data['mensagem'])) . "</p>";
        $body .= "</div>";
        $body .= "</div>";
        $body .= "<hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>";
        $body .= "<p style='color: #666; font-size: 12px;'>Esta mensagem foi enviada através do formulário de contato do site.</p>";
        $body .= "</body></html>";
        
        // Enviar e-mail
        return self::send($storeEmail, $subject, $body, $data['email'], $data['nome']);
    }
}

