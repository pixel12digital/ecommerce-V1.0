<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente do .env (banco remoto)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (!empty($name)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

use App\Core\Database;
use App\Services\ThemeConfig;
use App\Tenant\TenantContext;

// Inicializar tenant
$config = require __DIR__ . '/../config/app.php';
$mode = $config['mode'] ?? 'single';

if ($mode === 'single') {
    $defaultTenantId = $config['default_tenant_id'] ?? 1;
    TenantContext::setFixedTenant($defaultTenantId);
} else {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    TenantContext::resolveFromHost($host);
}

echo "=== Inserindo Conteúdo Adicional das Páginas ===\n\n";

// Preparar o conteúdo das páginas conforme fornecido
$pagesContent = [
    'formas_pagamento' => [
        'title' => 'Formas de Pagamento',
        'content' => '<p>Bem-vindo ao seu ponto de golfe.</p>

<h2>Formas de Pagamento</h2>

<h3>Cartão de crédito (Visa, Mastercard, Elo, Hipercard, American Express)</h3>

<ul>
<li>À vista ou parcelado</li>
<li>Pedidos ≥ R$ 400 → até 3× sem juros</li>
<li>Pedidos ≥ R$ 800 → até 6× sem juros</li>
</ul>

<h3>Cartão de débito</h3>

<h3>PIX → 5% de desconto</h3>
<p>(desconto aplicado automaticamente)</p>',
    ],
    
    'faq' => [
        'title' => 'Perguntas frequentes (FAQ)',
        'intro' => '<p>Veja abaixo as respostas para as dúvidas mais comuns.</p>',
        'items' => [
            [
                'question' => '1. Vocês enviam para todo o Brasil?',
                'answer' => '<p>Sim, enviamos para todo o território nacional via transportadoras parceiras.</p>',
            ],
            [
                'question' => '2. Qual o prazo de entrega?',
                'answer' => '<p><strong>Capitais e regiões metropolitanas:</strong> 2–6 dias úteis<br>
<strong>Demais localidades:</strong> 5–12 dias úteis</p>',
            ],
            [
                'question' => '3. Posso parcelar no cartão?',
                'answer' => '<p>Sim: parcela mínima de R$ 199,00</p>',
            ],
            [
                'question' => '4. Tem desconto no PIX?',
                'answer' => '<p>Sim, 5% de desconto automático em pagamentos via PIX.</p>',
            ],
            [
                'question' => '5. Qual a política de troca e devolução?',
                'answer' => '<p>Prazo de 7 dias corridos para arrependimento (produto sem uso).</p>
<p>Defeito ou erro nosso: troca ou reembolso em até 30 dias. Frete de retorno por nossa conta nesses casos.</p>',
            ],
            [
                'question' => '6. Posso retirar na loja física?',
                'answer' => '<p>Entre em contato com nossas lojas para saber se o produto de seu interesse está na loja mais próxima. Com isso confirmado a retirada em loja pode ser feita logo após o pagamento com comprovante em mãos.</p>',
            ],
            [
                'question' => '8. Como acompanho meu pedido?',
                'answer' => '<p>Você recebe o código de rastreio por WhatsApp assim que o pedido for enviado.</p>',
            ],
            [
                'question' => '9. Vocês aceitam cartão internacional?',
                'answer' => '<p>Sim, desde que o cartão seja emitido por bandeiras aceitas (Visa, Mastercard, Elo).</p>',
            ],
            [
                'question' => '10. Posso alterar ou cancelar o pedido após a compra?',
                'answer' => '<p>Sim, enquanto o pedido não tiver sido enviado. Entre em contato imediatamente via WhatsApp ou Instagram.</p>',
            ],
            [
                'question' => 'Dúvidas adicionais?',
                'answer' => '<p>Fale direto no WhatsApp</p>',
            ],
        ],
    ],
    
    'frete_prazos' => [
        'title' => 'Frete e prazos de entrega',
        'content' => '<h2>Fretes e Prazos</h2>

<p>Enviamos para todo o Brasil via correios.</p>

<h3>Prazos de Entrega</h3>

<p><strong>Capitais e regiões metropolitanas:</strong> 2–6 dias úteis</p>

<p><strong>Demais localidades:</strong> 5–12 dias úteis</p>

<p>Os prazos são contados a partir da confirmação do pagamento e podem variar conforme a região de entrega.</p>',
    ],
    
    'trocas_devolucoes' => [
        'title' => 'Trocas e Devoluções',
        'content' => '<h2>Arrependimento</h2>

<p>7 dias corridos a partir do recebimento (produto sem uso, na embalagem original com todos os acessórios e etiquetas).</p>

<h2>Defeito ou erro nosso</h2>

<p>30 dias corridos. Troca ou reembolso total (incluindo frete de ida e volta por nossa conta).</p>

<h2>Como solicitar</h2>

<p>Entre em contato via WhatsApp ou e-mail informando o número do pedido e o motivo. Enviaremos instruções e código de postagem gratuito (se aplicável).</p>

<h2>Reembolso</h2>

<p>Crédito na forma de pagamento em Pix, com valor de taxas descontados e em até 5 dias úteis após recebimento do produto devolvido.</p>',
    ],
];

// Obter páginas atuais
$currentPages = ThemeConfig::getPages();

echo "Páginas atuais no banco:\n";
foreach ($currentPages as $slug => $page) {
    echo "  - {$slug}: {$page['title']}\n";
}
echo "\n";

// Fazer merge: manter páginas existentes e adicionar/atualizar as novas
$updatedPages = array_merge($currentPages, $pagesContent);

// Salvar no banco de dados
echo "Salvando conteúdo no banco de dados...\n";
ThemeConfig::setPages($updatedPages);

echo "\nConteúdo salvo com sucesso!\n\n";

// Verificar o que foi salvo
echo "Páginas após atualização:\n";
$savedPages = ThemeConfig::getPages();
foreach ($savedPages as $slug => $page) {
    $contentLength = isset($page['content']) ? strlen($page['content']) : 0;
    $introLength = isset($page['intro']) ? strlen($page['intro']) : 0;
    $itemsCount = isset($page['items']) ? count($page['items']) : 0;
    
    $info = "content: {$contentLength} chars";
    if ($introLength > 0) {
        $info .= ", intro: {$introLength} chars";
    }
    if ($itemsCount > 0) {
        $info .= ", items: {$itemsCount}";
    }
    
    echo "  - {$slug}: {$page['title']} ({$info})\n";
}

echo "\n=== Processo Concluído ===\n";
echo "\nAs seguintes páginas foram atualizadas:\n";
echo "  1. Formas de Pagamento (/formas-de-pagamento)\n";
echo "  2. Perguntas Frequentes - FAQ (/faq)\n";
echo "  3. Frete e Prazos de Entrega (/frete-prazos)\n";
echo "  4. Trocas e Devoluções (/trocas-e-devolucoes)\n";
echo "\nO conteúdo está agora disponível no painel administrativo para edições futuras.\n";
