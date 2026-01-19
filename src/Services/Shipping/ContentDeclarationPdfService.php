<?php

namespace App\Services\Shipping;

/**
 * Serviço para geração de PDF da Declaração de Conteúdo
 * 
 * Gera PDF A4 simples e legível com dados do remetente (gateway Correios),
 * destinatário (pedido), itens e totais.
 * 
 * ⚠️ NOTA: Usa biblioteca Dompdf (instalar via composer: composer require dompdf/dompdf)
 * Se não tiver instalada, retorna HTML formatado que pode ser convertido.
 */
class ContentDeclarationPdfService
{
    /**
     * Gera PDF da Declaração de Conteúdo para um pedido
     * 
     * @param array $pedido Dados do pedido (com itens)
     * @param array $senderConfig Configuração do remetente (formato correios.origem)
     * @return string Conteúdo binário do PDF
     * @throws \Exception Em caso de erro
     */
    public static function generateForOrder(array $pedido, array $senderConfig): string
    {
        // Validar dados mínimos
        $erros = self::validarDados($pedido, $senderConfig);
        if (!empty($erros)) {
            throw new \Exception(implode(' ', $erros));
        }

        // Preparar dados formatados
        $dados = self::prepararDados($pedido, $senderConfig);

        // Gerar HTML do PDF
        $html = self::gerarHtml($dados);

        // Converter HTML para PDF
        return self::htmlParaPdf($html);
    }

    /**
     * Valida dados mínimos para gerar declaração
     */
    private static function validarDados(array $pedido, array $senderConfig): array
    {
        $erros = [];

        // Validar remetente
        $origem = $senderConfig['origem'] ?? $senderConfig;
        if (empty($origem['cep'])) {
            $erros[] = 'CEP de origem não configurado no gateway de frete. Configure em Gateways → Frete → Correios.';
        }
        if (empty($origem['nome'])) {
            $erros[] = 'Nome do remetente não configurado no gateway de frete. Configure em Gateways → Frete → Correios.';
        }

        // Validar destinatário
        $cepDestino = preg_replace('/\D/', '', $pedido['entrega_cep'] ?? '');
        if (strlen($cepDestino) !== 8) {
            $erros[] = 'CEP do destinatário inválido.';
        }

        $ufDestino = strtoupper(trim($pedido['entrega_estado'] ?? ''));
        if (empty($ufDestino) || strlen($ufDestino) !== 2) {
            $erros[] = 'UF do destinatário inválida (deve ter 2 letras).';
        }

        if (empty($pedido['cliente_nome'])) {
            $erros[] = 'Nome do destinatário não informado no pedido.';
        }

        if (empty($pedido['entrega_logradouro'])) {
            $erros[] = 'Logradouro do destinatário não informado no pedido.';
        }

        // Validar itens
        $itens = $pedido['itens'] ?? [];
        if (empty($itens)) {
            $erros[] = 'Pedido sem itens.';
        }

        return $erros;
    }

    /**
     * Prepara dados formatados para o PDF
     */
    private static function prepararDados(array $pedido, array $senderConfig): array
    {
        $origem = $senderConfig['origem'] ?? $senderConfig;
        $enderecoOrigem = $origem['endereco'] ?? [];

        // Calcular totais
        $totalItens = 0;
        $valorTotal = 0.0;
        $pesoTotal = 0.0;

        foreach ($pedido['itens'] as $item) {
            $quantidade = (int)($item['quantidade'] ?? 1);
            $precoUnitario = (float)($item['preco_unitario'] ?? 0);
            
            $totalItens += $quantidade;
            $valorTotal += $precoUnitario * $quantidade;
            
            // Peso (se disponível no item ou buscar do produto)
            // Por enquanto, não temos peso nos itens do pedido
            // Isso seria calculado depois se necessário
        }

        return [
            'data_emissao' => date('d/m/Y'),
            'remetente' => [
                'nome' => $origem['nome'] ?? '',
                'documento' => $origem['documento'] ?? '',
                'telefone' => $origem['telefone'] ?? '',
                'endereco' => [
                    'logradouro' => $enderecoOrigem['logradouro'] ?? '',
                    'numero' => $enderecoOrigem['numero'] ?? '',
                    'complemento' => $enderecoOrigem['complemento'] ?? '',
                    'bairro' => $enderecoOrigem['bairro'] ?? '',
                    'cidade' => $enderecoOrigem['cidade'] ?? '',
                    'uf' => $enderecoOrigem['uf'] ?? '',
                    'cep' => $origem['cep'] ?? '',
                ],
            ],
            'destinatario' => [
                'nome' => $pedido['cliente_nome'] ?? '',
                'telefone' => $pedido['cliente_telefone'] ?? '',
                'endereco' => [
                    'logradouro' => $pedido['entrega_logradouro'] ?? '',
                    'numero' => $pedido['entrega_numero'] ?? 's/n',
                    'complemento' => $pedido['entrega_complemento'] ?? '',
                    'bairro' => $pedido['entrega_bairro'] ?? '',
                    'cidade' => $pedido['entrega_cidade'] ?? '',
                    'uf' => strtoupper($pedido['entrega_estado'] ?? ''),
                    'cep' => $pedido['entrega_cep'] ?? '',
                ],
            ],
            'itens' => array_map(function($item) {
                return [
                    'nome' => htmlspecialchars($item['nome_produto'] ?? 'Produto', ENT_QUOTES, 'UTF-8'),
                    'quantidade' => (int)($item['quantidade'] ?? 1),
                    'preco_unitario' => (float)($item['preco_unitario'] ?? 0),
                    'total' => ((float)($item['preco_unitario'] ?? 0)) * ((int)($item['quantidade'] ?? 1)),
                ];
            }, $pedido['itens'] ?? []),
            'totais' => [
                'total_itens' => $totalItens,
                'valor_total' => $valorTotal,
                'peso_total' => $pesoTotal > 0 ? $pesoTotal : null,
            ],
        ];
    }

    /**
     * Gera HTML formatado para o PDF
     */
    private static function gerarHtml(array $dados): string
    {
        $remetente = $dados['remetente'];
        $destinatario = $dados['destinatario'];
        $itens = $dados['itens'];
        $totais = $dados['totais'];

        // Formatar endereço completo
        $enderecoRemetente = self::formatarEndereco($remetente['endereco']);
        $enderecoDestinatario = self::formatarEndereco($destinatario['endereco']);

        // Formatar telefone
        $telefoneRemetente = self::formatarTelefone($remetente['telefone']);
        $telefoneDestinatario = self::formatarTelefone($destinatario['telefone']);

        // Gerar linhas da tabela de itens
        $linhasItens = '';
        foreach ($itens as $item) {
            $nome = self::truncarTexto($item['nome'], 50); // Limitar tamanho da descrição
            $qtd = $item['quantidade'];
            $precoUnit = number_format($item['preco_unitario'], 2, ',', '.');
            $totalItem = number_format($item['total'], 2, ',', '.');
            
            $linhasItens .= "
                <tr>
                    <td style=\"padding: 8px; border: 1px solid #ddd; text-align: left;\">{$nome}</td>
                    <td style=\"padding: 8px; border: 1px solid #ddd; text-align: center;\">{$qtd}</td>
                    <td style=\"padding: 8px; border: 1px solid #ddd; text-align: right;\">R$ {$precoUnit}</td>
                    <td style=\"padding: 8px; border: 1px solid #ddd; text-align: right;\">R$ {$totalItem}</td>
                </tr>
            ";
        }

        $valorTotalFormatado = number_format($totais['valor_total'], 2, ',', '.');
        $pesoTotalTexto = $totais['peso_total'] ? number_format($totais['peso_total'], 2, ',', '.') . ' kg' : '—';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de Conteúdo</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header .data {
            margin-top: 10px;
            font-size: 10pt;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .two-columns {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .column:last-child {
            padding-right: 0;
        }
        .info-line {
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            min-width: 100px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
            padding: 10px 8px;
            border: 1px solid #000;
            font-size: 10pt;
        }
        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .items-table .text-left { text-align: left; }
        .items-table .text-center { text-align: center; }
        .items-table .text-right { text-align: right; }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals-line {
            margin: 8px 0;
            font-size: 11pt;
        }
        .totals-line.total {
            font-weight: bold;
            font-size: 12pt;
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 12px;
        }
        .declaration {
            margin-top: 40px;
            padding: 20px;
            border: 1px solid #000;
            background: #f9f9f9;
        }
        .declaration-text {
            margin-bottom: 40px;
            line-height: 1.6;
            text-align: justify;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
            text-align: center;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DECLARAÇÃO DE CONTEÚDO</h1>
        <div class="data">Data de Emissão: {$dados['data_emissao']}</div>
    </div>

    <div class="section">
        <div class="two-columns">
            <div class="column">
                <div class="section-title">REMETENTE</div>
                <div class="info-line"><span class="info-label">Nome:</span> {$remetente['nome']}</div>
                ' . self::formatarLinha('CPF/CNPJ:', $remetente['documento']) . '
                ' . self::formatarLinha('Telefone:', $telefoneRemetente) . '
                ' . self::formatarLinha('Endereço:', $enderecoRemetente) . '
                ' . self::formatarLinha('CEP:', self::formatarCep($remetente['endereco']['cep'])) . '
            </div>
            <div class="column">
                <div class="section-title">DESTINATÁRIO</div>
                <div class="info-line"><span class="info-label">Nome:</span> {$destinatario['nome']}</div>
                ' . self::formatarLinha('Telefone:', $telefoneDestinatario) . '
                ' . self::formatarLinha('Endereço:', $enderecoDestinatario) . '
                ' . self::formatarLinha('CEP:', self::formatarCep($destinatario['endereco']['cep'])) . '
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">ITENS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">Descrição</th>
                    <th class="text-center" style="width: 80px;">Qtd</th>
                    <th class="text-right" style="width: 100px;">Valor Unit.</th>
                    <th class="text-right" style="width: 100px;">Total Item</th>
                </tr>
            </thead>
            <tbody>
                {$linhasItens}
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="totals">
            <div class="totals-line">Total de itens: <strong>{$totais['total_itens']}</strong></div>
            <div class="totals-line">Valor total dos produtos: <strong>R$ {$valorTotalFormatado}</strong></div>
            <div class="totals-line">Peso total: <strong>{$pesoTotalTexto}</strong></div>
            <div class="totals-line total">Valor total do pedido: <strong>R$ {$valorTotalFormatado}</strong></div>
        </div>
    </div>

    <div class="declaration">
        <div class="declaration-text">
            <strong>DECLARAÇÃO:</strong><br>
            Declaro que os itens descritos nesta declaração de conteúdo são verdadeiros e correspondem exatamente 
            ao conteúdo do objeto enviado. O valor declarado corresponde ao valor real dos produtos. 
            Não inclui produtos proibidos ou perigosos.
        </div>
        <div class="signature-line">
            <div>Assinatura do Remetente</div>
            <div class="signature-name">{$remetente['nome']}</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Formata endereço completo
     */
    private static function formatarEndereco(array $endereco): string
    {
        $partes = [];
        
        $logradouro = trim($endereco['logradouro'] ?? '');
        $numero = trim($endereco['numero'] ?? '');
        
        if (!empty($logradouro)) {
            $partes[] = $logradouro . (!empty($numero) ? ', ' . $numero : ', s/n');
        }
        
        $complemento = trim($endereco['complemento'] ?? '');
        if (!empty($complemento)) {
            $partes[] = $complemento;
        }
        
        $bairro = trim($endereco['bairro'] ?? '');
        if (!empty($bairro)) {
            $partes[] = $bairro;
        }
        
        $cidade = trim($endereco['cidade'] ?? '');
        $uf = trim($endereco['uf'] ?? '');
        if (!empty($cidade)) {
            $partes[] = $cidade . (!empty($uf) ? '/' . $uf : '');
        }

        return implode(' - ', array_filter($partes)) ?: 'Endereço não informado';
    }

    /**
     * Formata telefone
     */
    private static function formatarTelefone(string $telefone): string
    {
        $telefone = preg_replace('/\D/', '', $telefone);
        
        if (empty($telefone)) {
            return '';
        }
        
        if (strlen($telefone) === 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
        }
        
        if (strlen($telefone) === 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
        }
        
        return $telefone;
    }

    /**
     * Formata CEP
     */
    private static function formatarCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5);
        }
        return $cep;
    }

    /**
     * Formata linha (só mostra se tiver valor)
     */
    private static function formatarLinha(string $label, string $valor): string
    {
        if (empty($valor)) {
            return '';
        }
        return "<div class=\"info-line\"><span class=\"info-label\">{$label}</span> {$valor}</div>";
    }

    /**
     * Trunca texto para evitar quebra de layout
     */
    private static function truncarTexto(string $texto, int $limite): string
    {
        if (strlen($texto) <= $limite) {
            return $texto;
        }
        return substr($texto, 0, $limite - 3) . '...';
    }

    /**
     * Converte HTML para PDF usando biblioteca disponível
     * 
     * @param string $html HTML formatado
     * @return string Conteúdo binário do PDF
     * @throws \Exception
     */
    private static function htmlParaPdf(string $html): string
    {
        // Tentar usar Dompdf se disponível
        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        }

        // Fallback: retornar HTML (pode ser convertido depois ou usar print to PDF do navegador)
        // Em produção, isso deve ser tratado como erro se não tiver biblioteca
        throw new \Exception(
            'Biblioteca de PDF não encontrada. ' .
            'Instale Dompdf: composer require dompdf/dompdf'
        );
    }
}
