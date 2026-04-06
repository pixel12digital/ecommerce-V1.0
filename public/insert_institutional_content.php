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

echo "=== Inserindo Conteúdo Institucional ===\n\n";

// Preparar o conteúdo das páginas conforme fornecido
$pagesContent = [
    'sobre' => [
        'title' => 'Sobre a Loja',
        'content' => '<p>Na Ponto do Golfe, acreditamos que o golfe é mais do que um esporte; é uma busca constante pela perfeição. Especializados em artigos de golfe de alto padrão, nossa missão é conectar jogadores brasileiros ao que há de melhor no mercado mundial.</p>

<p>Trabalhamos com uma curadoria rigorosa de produtos, em sua maioria importados, garantindo que cada taco, bola ou acessório em nosso catálogo entregue a máxima performance e durabilidade.</p>

<p>Com presença física em Foz do Iguaçu (IGUASSU FALLS GOLF CLUB - WIHS RESORT) e Florianópolis (Costão Golf Ville Club), aliamos a conveniência do e-commerce com a solidez de nossas lojas físicas, onde a qualidade do produto e a satisfação do cliente são os pilares de tudo o que fazemos.</p>',
    ],
    
    'contato' => [
        'title' => 'Fale Conosco',
        'intro' => '<p>Na Ponto do Golfe, entendemos que cada tacada conta. Por isso, oferecemos um atendimento personalizado para garantir que você encontre os melhores artigos importados para o seu jogo.</p>

<p>Seja para tirar dúvidas sobre especificações técnicas, acompanhar seu pedido ou buscar um item exclusivo, nossa equipe está pronta para ajudar.</p>

<h3>Contato</h3>
<p><strong>WhatsApp:</strong> (41) 99184-2320 (Atendimento rápido e humano)<br>
<strong>E-mail:</strong> contato@pontodogolfeoutlet.com.br<br>
<strong>Instagram:</strong> @pontodogolfe</p>

<h3>Horário de Atendimento</h3>
<p>Terça a sábado, das 09h às 18h.</p>',
    ],
    
    'politica_privacidade' => [
        'title' => 'Política de Privacidade',
        'content' => '<p>A Ponto do Golfe valoriza a sua privacidade e o compromisso com a proteção dos seus dados pessoais. Esta política descreve como coletamos, usamos e protegemos suas informações em conformidade com a Lei nº 13.709/2018 (LGPD).</p>

<h3>Quais dados coletamos?</h3>

<p>Para processar seus pedidos de artigos de golfe importados, coletamos dados estritamente necessários:</p>

<p><strong>Dados Cadastrais:</strong> Nome completo, CPF, e-mail e telefone.</p>

<p><strong>Dados de Entrega:</strong> Endereço completo para o envio dos produtos.</p>

<p><strong>Dados de Pagamento:</strong> Processados de forma segura por parceiros certificados (não armazenamos dados de cartão em nossos servidores).</p>

<p><strong>Dados de Navegação:</strong> Cookies e endereço IP para melhorar sua experiência no site.</p>

<h3>Para que usamos seus dados?</h3>

<p>A finalidade principal é a execução do contrato de compra e venda. Seus dados são utilizados para:</p>

<ul>
<li>Processar e entregar pedidos</li>
<li>Emitir notas fiscais</li>
<li>Comunicar sobre o status do pedido</li>
<li>Oferecer suporte ao cliente</li>
<li>Melhorar a experiência de navegação</li>
<li>Enviar ofertas personalizadas (mediante consentimento)</li>
</ul>

<h3>Compartilhamento de Dados</h3>

<p>Seus dados podem ser compartilhados apenas com:</p>

<ul>
<li>Transportadoras para entrega dos produtos</li>
<li>Intermediadores de pagamento (PagSeguro, Mercado Pago, etc.)</li>
<li>Autoridades fiscais e judiciais quando exigido por lei</li>
</ul>

<h3>Segurança</h3>

<p>Adotamos medidas técnicas e administrativas para proteger seus dados contra acessos não autorizados, perda ou destruição.</p>

<h3>Seus Direitos</h3>

<p>Você tem direito a:</p>

<ul>
<li>Confirmar a existência de tratamento de dados</li>
<li>Acessar seus dados</li>
<li>Corrigir dados incompletos ou desatualizados</li>
<li>Solicitar a exclusão de dados (exceto quando houver obrigação legal de retenção)</li>
<li>Revogar consentimento</li>
</ul>

<p>Para exercer seus direitos, entre em contato pelo e-mail: contato@pontodogolfeoutlet.com.br</p>

<h3>Alterações nesta Política</h3>

<p>Podemos atualizar esta política periodicamente. Recomendamos a leitura regular.</p>

<p><strong>Última atualização:</strong> Março de 2025</p>',
    ],
    
    'termos_uso' => [
        'title' => 'Termos de Uso',
        'content' => '<p>Bem-vindo ao site da Ponto do Golfe. Ao navegar ou realizar compras, você concorda com os termos abaixo. Por favor, leia com atenção.</p>

<h3>Objeto e Cadastro</h3>

<p>Este site comercializa artigos de golfe (novos e seminovos), majoritariamente importados. Para comprar, o usuário deve ser maior de 18 anos e fornecer dados verídicos. Reservamo-nos o direito de suspender cadastros com informações falsas.</p>

<h3>Produtos Importados e Preços</h3>

<h4>Disponibilidade:</h4>

<p>Por lidarmos com itens exclusivos e importados, o estoque é limitado. Caso um item esgote após a compra, entraremos em contato para oferecer a troca ou o estorno imediato.</p>

<h4>Preços:</h4>

<p>Os valores podem sofrer alterações sem aviso prévio devido a flutuações cambiais (dólar). O preço válido é o confirmado no checkout.</p>

<h3>Prazos e Entrega</h3>

<p><strong>Produtos em Estoque Nacional:</strong> 3 a 10 dias úteis após aprovação do pagamento.</p>

<p><strong>Produtos Importados sob Encomenda:</strong> 20 a 45 dias úteis (sujeito a desembaraço aduaneiro).</p>

<p>O prazo será informado na página do produto. Atrasos excepcionais serão comunicados por e-mail.</p>

<h3>Pagamento</h3>

<p>Aceitamos:</p>

<ul>
<li>Cartão de crédito (parcelamento em até 12x)</li>
<li>PIX (aprovação instantânea)</li>
<li>Boleto bancário (compensação em até 2 dias úteis)</li>
</ul>

<p>O pedido só será processado após confirmação do pagamento.</p>

<h3>Trocas e Devoluções</h3>

<p>Você tem 7 dias corridos (a partir do recebimento) para desistir da compra, conforme o Código de Defesa do Consumidor.</p>

<p><strong>Condições:</strong></p>

<ul>
<li>Produto sem uso, com embalagem original</li>
<li>Etiquetas e lacres intactos</li>
<li>Nota fiscal incluída</li>
</ul>

<p>Para defeitos de fabricação, a garantia segue as normas do fabricante (geralmente 90 dias a 1 ano).</p>

<h3>Propriedade Intelectual</h3>

<p>Todo o conteúdo do site (textos, imagens, logos) é de propriedade da Ponto do Golfe ou de seus parceiros. É proibida a reprodução sem autorização.</p>

<h3>Limitação de Responsabilidade</h3>

<p>Não nos responsabilizamos por:</p>

<ul>
<li>Erros de digitação em descrições (sempre confirme antes de comprar)</li>
<li>Atrasos causados por transportadoras ou alfândega</li>
<li>Incompatibilidade de produtos com expectativas subjetivas do cliente</li>
</ul>

<h3>Alterações nos Termos</h3>

<p>Reservamo-nos o direito de modificar estes termos a qualquer momento. O uso contínuo do site implica aceitação das alterações.</p>

<h3>Lei Aplicável</h3>

<p>Estes termos são regidos pelas leis brasileiras. Foro: Comarca de Foz do Iguaçu/PR.</p>

<p><strong>Dúvidas?</strong> Entre em contato: contato@pontodogolfeoutlet.com.br</p>',
    ],
    
    'politica_cookies' => [
        'title' => 'Política de Cookies',
        'content' => '<p>Na Ponto do Golfe, utilizamos cookies para garantir que sua experiência de navegação seja tão precisa quanto um putt bem calculado. Esta política explica o que são cookies e como os utilizamos.</p>

<h3>O que são cookies?</h3>

<p>Cookies são pequenos arquivos de texto enviados ao seu navegador para coletar informações sobre sua visita. Eles ajudam o site a lembrar de você e das suas preferências de compra.</p>

<h3>Tipos de Cookies que utilizamos</h3>

<h4>Cookies Essenciais:</h4>

<p>Necessários para o funcionamento do site (ex: manter os produtos no carrinho enquanto você navega).</p>

<h4>Cookies de Desempenho:</h4>

<p>Ajudam-nos a entender quais equipamentos de golfe são mais visitados para melhorarmos nosso catálogo.</p>

<h4>Cookies de Marketing:</h4>

<p>Utilizados para mostrar anúncios da Ponto do Golfe em outras plataformas.</p>

<h3>Como gerenciar cookies?</h3>

<p>Você pode configurar seu navegador para:</p>

<ul>
<li>Bloquear todos os cookies</li>
<li>Aceitar apenas cookies essenciais</li>
<li>Receber notificações antes de aceitar cookies</li>
</ul>

<p><strong>Atenção:</strong> Bloquear cookies pode afetar a funcionalidade do site (ex: não conseguir adicionar produtos ao carrinho).</p>

<h3>Cookies de Terceiros</h3>

<p>Utilizamos ferramentas de terceiros que podem instalar cookies:</p>

<ul>
<li><strong>Google Analytics:</strong> Para análise de tráfego</li>
<li><strong>Facebook Pixel:</strong> Para anúncios direcionados</li>
<li><strong>Gateways de Pagamento:</strong> Para processar transações com segurança</li>
</ul>

<h3>Tempo de Armazenamento</h3>

<p><strong>Cookies de Sessão:</strong> Expiram quando você fecha o navegador.</p>

<p><strong>Cookies Persistentes:</strong> Permanecem por até 12 meses para lembrar suas preferências.</p>

<h3>Seus Direitos</h3>

<p>Você pode solicitar a exclusão dos seus dados de navegação a qualquer momento através do e-mail: contato@pontodogolfeoutlet.com.br</p>

<h3>Atualizações</h3>

<p>Esta política pode ser atualizada periodicamente. Recomendamos a leitura regular.</p>

<p><strong>Última atualização:</strong> Março de 2025</p>',
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
    echo "  - {$slug}: {$page['title']} (content: {$contentLength} chars, intro: {$introLength} chars)\n";
}

echo "\n=== Processo Concluído ===\n";
echo "\nAs seguintes páginas foram atualizadas:\n";
echo "  1. Sobre a Loja (/sobre)\n";
echo "  2. Fale Conosco (/contato)\n";
echo "  3. Política de Privacidade (/politica-de-privacidade)\n";
echo "  4. Termos de Uso (/termos-de-uso)\n";
echo "  5. Política de Cookies (/politica-de-cookies)\n";
echo "\nO conteúdo está agora disponível no painel administrativo para edições futuras.\n";
