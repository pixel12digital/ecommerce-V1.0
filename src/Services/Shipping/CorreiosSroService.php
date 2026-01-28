<?php

namespace App\Services\Shipping;

/**
 * Serviço de Rastreamento de Objetos (SRO) dos Correios
 * 
 * ⚠️ NOTA: Este serviço está em modo STUB (não ativado por padrão).
 * Para ativar, configure o toggle "Ativar rastreio automático" no gateway Correios.
 * 
 * Funcionalidades (quando implementado):
 * - Buscar status de rastreamento via API dos Correios
 * - Atualizar status do pedido automaticamente
 * - Executar via cron/queue quando habilitado
 */
class CorreiosSroService
{
    /**
     * Busca status de rastreamento de um código
     * 
     * @param string $codigoRastreio Código de rastreamento (ex: BR123456789BR)
     * @param array $config Configurações do gateway Correios
     * @return array Status do rastreamento
     * @throws \Exception Em caso de erro
     * 
     * ⚠️ STUB: Retorna estrutura vazia. Implementar quando API SRO estiver disponível.
     */
    public static function buscarStatus(string $codigoRastreio, array $config = []): array
    {
        // ⚠️ STUB: Implementação pendente
        // Quando implementado, deve:
        // 1. Validar código de rastreamento
        // 2. Chamar API SRO dos Correios (se disponível)
        // 3. Retornar array com:
        //    - 'status' => string (ex: 'em_transito', 'entregue', 'aguardando_retirada')
        //    - 'ultima_atualizacao' => datetime
        //    - 'eventos' => array (histórico de eventos)
        //    - 'localizacao' => string (última localização conhecida)
        
        return [
            'status' => 'nao_implementado',
            'mensagem' => 'Rastreamento automático ainda não implementado. Use o site dos Correios para rastrear.',
            'ultima_atualizacao' => null,
            'eventos' => [],
            'localizacao' => null,
        ];
    }

    /**
     * Verifica se o rastreio automático está habilitado
     * 
     * @param array $config Configurações do gateway Correios
     * @return bool
     */
    public static function isRastreioAutomaticoHabilitado(array $config = []): bool
    {
        $correiosConfig = $config['correios'] ?? $config;
        return (bool)($correiosConfig['rastreio_automatico'] ?? false);
    }

    /**
     * Processa rastreamento para um pedido
     * 
     * @param int $pedidoId ID do pedido
     * @param string $codigoRastreio Código de rastreamento
     * @param array $config Configurações do gateway Correios
     * @return array Resultado do processamento
     * 
     * ⚠️ STUB: Não implementado. Deve ser chamado via cron/queue quando habilitado.
     */
    public static function processarRastreamento(int $pedidoId, string $codigoRastreio, array $config = []): array
    {
        // ⚠️ STUB: Implementação pendente
        // Quando implementado, deve:
        // 1. Chamar buscarStatus()
        // 2. Atualizar status do pedido se necessário
        // 3. Registrar eventos de rastreamento
        // 4. Notificar cliente se configurado
        
        return [
            'processado' => false,
            'mensagem' => 'Rastreamento automático não implementado.',
        ];
    }
}
