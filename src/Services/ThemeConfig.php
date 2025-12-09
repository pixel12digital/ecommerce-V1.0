<?php

namespace App\Services;

use App\Core\Database;
use App\Tenant\TenantContext;

class ThemeConfig
{
    private static array $cache = [];

    /**
     * Obtém uma configuração do tema
     */
    public static function get(string $key, $default = null)
    {
        $tenantId = TenantContext::id();
        $cacheKey = "{$tenantId}:{$key}";

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT value FROM tenant_settings 
            WHERE tenant_id = :tenant_id AND `key` = :key 
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'key' => $key
        ]);
        $result = $stmt->fetch();

        $value = $result ? $result['value'] : $default;
        self::$cache[$cacheKey] = $value;

        return $value;
    }

    /**
     * Obtém uma cor do tema (garante formato hex)
     */
    public static function getColor(string $key, string $default = '#000000'): string
    {
        $value = self::get($key, $default);
        
        // Se já é um hex válido, retornar
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return $value;
        }
        
        // Se não começa com #, adicionar
        if (!empty($value) && $value[0] !== '#') {
            return '#' . $value;
        }
        
        return $value ?: $default;
    }

    /**
     * Obtém um JSON decodificado
     */
    public static function getJson(string $key, array $default = []): array
    {
        $value = self::get($key);
        
        if (empty($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Define uma configuração do tema
     */
    public static function set(string $key, $value): void
    {
        $tenantId = TenantContext::id();
        $cacheKey = "{$tenantId}:{$key}";

        $db = Database::getConnection();
        
        // Se o valor for array, converter para JSON
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $db->prepare("
            INSERT INTO tenant_settings (tenant_id, `key`, value, created_at, updated_at)
            VALUES (:tenant_id, :key, :value, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                value = :value_update,
                updated_at = NOW()
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'key' => $key,
            'value' => $value,
            'value_update' => $value
        ]);

        // Atualizar cache
        self::$cache[$cacheKey] = $value;
    }

    /**
     * Obtém o menu principal (apenas itens habilitados)
     */
    public static function getMainMenu(): array
    {
        $menu = self::getJson('theme_menu_main', []);
        
        return array_filter($menu, function($item) {
            return isset($item['enabled']) && $item['enabled'] === true;
        });
    }

    /**
     * Limpa o cache (útil após atualizações)
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Retorna todas as cores do tema em um array padronizado
     * Útil para passar para views do storefront
     */
    public static function getAllThemeColors(): array
    {
        return [
            'color_primary' => self::getColor('theme_color_primary', '#2E7D32'),
            'color_secondary' => self::getColor('theme_color_secondary', '#F7931E'),
            'color_topbar_bg' => self::getColor('theme_color_topbar_bg', '#1a1a1a'),
            'color_topbar_text' => self::getColor('theme_color_topbar_text', '#ffffff'),
            'color_header_bg' => self::getColor('theme_color_header_bg', '#ffffff'),
            'color_header_text' => self::getColor('theme_color_header_text', '#333333'),
            'color_footer_bg' => self::getColor('theme_color_footer_bg', '#1a1a1a'),
            'color_footer_text' => self::getColor('theme_color_footer_text', '#ffffff'),
        ];
    }

    /**
     * Retorna todas as configurações do tema (cores + textos + menu + logo + footer)
     * Útil para passar para views do storefront
     */
    public static function getFullThemeConfig(): array
    {
        $colors = self::getAllThemeColors();
        
        return array_merge($colors, [
            // Textos
            'topbar_text' => self::get('topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe'),
            'newsletter_title' => self::get('newsletter_title', 'Receba nossas ofertas'),
            'newsletter_subtitle' => self::get('newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas'),
            
            // Menu
            'menu_main' => self::getMainMenu(),
            
            // Logo
            'logo_url' => self::get('logo_url', ''),
            
            // Footer
            'footer_phone' => self::get('footer_phone', ''),
            'footer_whatsapp' => self::get('footer_whatsapp', ''),
            'footer_email' => self::get('footer_email', ''),
            'footer_address' => self::get('footer_address', ''),
            'footer_social_instagram' => self::get('footer_social_instagram', ''),
            'footer_social_facebook' => self::get('footer_social_facebook', ''),
            'footer_social_youtube' => self::get('footer_social_youtube', ''),
        ]);
    }

    /**
     * Retorna os textos padrão das páginas institucionais
     */
    private static function getDefaultPages(): array
    {
        return [
            'sobre' => [
                'title' => 'Sobre o Ponto do Golfe',
                'content' => '<p>Bem-vindo ao Ponto do Golfe! Somos especialistas em equipamentos e acessórios de golfe, oferecendo produtos de alta qualidade para golfistas de todos os níveis.</p><p>Nossa missão é proporcionar a melhor experiência de compra, com produtos selecionados e atendimento diferenciado.</p>',
            ],
            'contato' => [
                'title' => 'Fale conosco',
                'intro' => '<p>Estamos à disposição para esclarecer suas dúvidas e ajudar você a encontrar os melhores produtos para o seu jogo.</p>',
            ],
            'trocas_devolucoes' => [
                'title' => 'Trocas e devoluções',
                'content' => '<p>Você tem até 7 dias corridos, contados a partir da data de recebimento do produto, para solicitar a troca ou devolução.</p><h3>Condições para troca/devolução:</h3><ul><li>O produto deve estar em sua embalagem original</li><li>O produto não deve ter sido usado</li><li>O produto deve estar completo, com todos os acessórios e manuais</li><li>A nota fiscal deve estar presente</li></ul><p>Para solicitar troca ou devolução, entre em contato conosco através dos nossos canais de atendimento.</p>',
            ],
            'frete_prazos' => [
                'title' => 'Frete e prazos de entrega',
                'content' => '<h3>Frete Grátis</h3><p>Frete grátis para compras acima de R$ 299,00 em todo o Brasil.</p><h3>Prazos de Entrega</h3><ul><li><strong>Capitais e Regiões Metropolitanas:</strong> 3 a 5 dias úteis</li><li><strong>Interior:</strong> 5 a 10 dias úteis</li><li><strong>Regiões Norte e Nordeste:</strong> 7 a 12 dias úteis</li></ul><p>Os prazos são contados a partir da confirmação do pagamento e podem variar conforme a região de entrega.</p>',
            ],
            'formas_pagamento' => [
                'title' => 'Formas de pagamento',
                'content' => '<h3>Cartões de Crédito</h3><p>Aceitamos todas as bandeiras: Visa, Mastercard, Elo, American Express, Hipercard e Diners.</p><p>Parcelamento em até 12x sem juros (parcelas mínimas de R$ 50,00).</p><h3>Boleto Bancário</h3><p>Pagamento à vista com desconto de 5%.</p><h3>PIX</h3><p>Pagamento à vista com desconto de 5% e aprovação imediata do pedido.</p>',
            ],
            'faq' => [
                'title' => 'Perguntas frequentes (FAQ)',
                'intro' => '<p>Veja abaixo as respostas para as dúvidas mais comuns.</p>',
                'items' => [
                    // Exemplo de estrutura (pode deixar vazio por default)
                    // [
                    //     'question' => 'Como funcionam os prazos de entrega?',
                    //     'answer'   => '<p>Resposta padrão...</p>',
                    // ],
                ],
            ],
            'politica_privacidade' => [
                'title' => 'Política de privacidade',
                'content' => '<p>O Ponto do Golfe respeita a privacidade de seus clientes e está comprometido em proteger suas informações pessoais.</p><h3>Coleta de Informações</h3><p>Coletamos informações que você nos fornece diretamente, como nome, e-mail, telefone e endereço, necessárias para processar seus pedidos e melhorar nossos serviços.</p><h3>Uso das Informações</h3><p>Utilizamos suas informações para processar pedidos, enviar comunicações sobre produtos e serviços, e melhorar sua experiência de compra.</p><h3>Segurança</h3><p>Adotamos medidas de segurança para proteger suas informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição.</p><h3>Seus Direitos</h3><p>Você tem o direito de acessar, corrigir ou excluir suas informações pessoais a qualquer momento. Entre em contato conosco para exercer esses direitos.</p>',
            ],
            'termos_uso' => [
                'title' => 'Termos de uso',
                'content' => '<p>Ao utilizar este site, você concorda com os seguintes termos e condições:</p><h3>Uso do Site</h3><p>O site é destinado para uso pessoal e não comercial. Você não pode reproduzir, duplicar, copiar, vender ou explorar qualquer parte do site sem nossa autorização prévia.</p><h3>Produtos</h3><p>Fazemos o possível para exibir com precisão as cores e imagens dos produtos, mas não garantimos que a exibição no seu monitor seja precisa.</p><h3>Preços</h3><p>Reservamo-nos o direito de alterar os preços a qualquer momento sem aviso prévio. Os preços exibidos são válidos apenas para compras online.</p><h3>Alterações</h3><p>Reservamo-nos o direito de modificar estes termos a qualquer momento. É sua responsabilidade revisar periodicamente estes termos.</p>',
            ],
            'politica_cookies' => [
                'title' => 'Política de cookies',
                'content' => '<p>Este site utiliza cookies para melhorar sua experiência de navegação e personalizar conteúdo.</p><h3>O que são cookies?</h3><p>Cookies são pequenos arquivos de texto armazenados em seu dispositivo quando você visita um site.</p><h3>Como usamos cookies</h3><ul><li>Para manter você conectado ao site</li><li>Para lembrar suas preferências</li><li>Para analisar como você usa o site</li><li>Para melhorar a funcionalidade do site</li></ul><h3>Gerenciamento de cookies</h3><p>Você pode controlar e/ou excluir cookies conforme desejar. Você pode excluir todos os cookies que já estão no seu computador e pode configurar a maioria dos navegadores para impedir que sejam colocados.</p>',
            ],
            'seja_parceiro' => [
                'title' => 'Seja parceiro / Atacado',
                'content' => '<p>Interessado em se tornar nosso parceiro ou comprar no atacado?</p><h3>Vantagens de ser parceiro</h3><ul><li>Preços especiais para revendedores</li><li>Descontos progressivos conforme volume</li><li>Suporte dedicado</li><li>Catálogo completo de produtos</li></ul><h3>Como se tornar parceiro</h3><p>Entre em contato conosco através dos nossos canais de atendimento informando seu interesse em se tornar parceiro. Nossa equipe comercial entrará em contato para apresentar as condições e benefícios.</p><p>Será necessário fornecer informações sobre seu negócio, como CNPJ, ramo de atividade e volume estimado de compras.</p>',
            ],
        ];
    }

    /**
     * Obtém todas as páginas institucionais com defaults aplicados
     */
    public static function getPages(): array
    {
        $defaults = self::getDefaultPages();
        $saved = self::getJson('theme_pages', []);
        
        // Fazer merge: defaults + salvos (salvos têm prioridade)
        $pages = [];
        foreach ($defaults as $slug => $defaultPage) {
            $pages[$slug] = array_merge($defaultPage, $saved[$slug] ?? []);
        }
        
        return $pages;
    }

    /**
     * Obtém dados de uma página específica
     */
    public static function getPage(string $slug): array
    {
        $pages = self::getPages();
        $page = $pages[$slug] ?? [];
        $defaults = self::getDefaultPages();
        $defaultPage = $defaults[$slug] ?? [];
        
        // Merge para garantir estrutura completa (especialmente para FAQ com items)
        $merged = array_merge($defaultPage, $page);
        
        // Garantir que FAQ sempre tenha items como array
        if ($slug === 'faq' && !isset($merged['items'])) {
            $merged['items'] = [];
        }
        
        return $merged ?: [
            'title' => 'Página não encontrada',
            'content' => '<p>Conteúdo não disponível.</p>',
        ];
    }

    /**
     * Salva o array completo de páginas
     */
    public static function setPages(array $pages): void
    {
        // Fazer merge com defaults para garantir que não percamos campos
        $defaults = self::getDefaultPages();
        $merged = [];
        
        foreach ($defaults as $slug => $defaultPage) {
            $merged[$slug] = array_merge($defaultPage, $pages[$slug] ?? []);
        }
        
        self::set('theme_pages', $merged);
    }

    /**
     * Retorna os defaults da configuração de footer
     */
    private static function getDefaultFooterConfig(): array
    {
        return [
            'sections' => [
                'ajuda' => [
                    'title' => 'Ajuda',
                    'enabled' => true,
                    'links' => [
                        'contato' => ['label' => 'Fale conosco', 'enabled' => true, 'route' => '/contato'],
                        'trocas_devolucoes' => ['label' => 'Trocas e devoluções', 'enabled' => true, 'route' => '/trocas-e-devolucoes'],
                        'frete_prazos' => ['label' => 'Frete e prazos de entrega', 'enabled' => true, 'route' => '/frete-prazos'],
                        'formas_pagamento' => ['label' => 'Formas de pagamento', 'enabled' => true, 'route' => '/formas-de-pagamento'],
                        'faq' => ['label' => 'Perguntas frequentes (FAQ)', 'enabled' => true, 'route' => '/faq'],
                    ],
                ],
                'minha_conta' => [
                    'title' => 'Minha Conta',
                    'enabled' => true,
                    'links' => [
                        'minha_conta' => ['label' => 'Minha conta', 'enabled' => true, 'route' => '/minha-conta'],
                        'carrinho' => ['label' => 'Carrinho', 'enabled' => true, 'route' => '/carrinho'],
                        'checkout' => ['label' => 'Finalizar compra', 'enabled' => true, 'route' => '/checkout'],
                        'meus_pedidos' => ['label' => 'Meus pedidos', 'enabled' => true, 'route' => '/minha-conta/pedidos'],
                        'meus_dados' => ['label' => 'Meus dados', 'enabled' => true, 'route' => '/minha-conta/perfil'],
                    ],
                ],
                'institucional' => [
                    'title' => 'Institucional',
                    'enabled' => true,
                    'links' => [
                        'sobre' => ['label' => 'Sobre o Ponto do Golfe', 'enabled' => true, 'route' => '/sobre'],
                        'politica_privacidade' => ['label' => 'Política de privacidade', 'enabled' => true, 'route' => '/politica-de-privacidade'],
                        'termos_uso' => ['label' => 'Termos de uso', 'enabled' => true, 'route' => '/termos-de-uso'],
                        'politica_cookies' => ['label' => 'Política de cookies', 'enabled' => true, 'route' => '/politica-de-cookies'],
                        'seja_parceiro' => ['label' => 'Seja parceiro / Atacado', 'enabled' => true, 'route' => '/seja-parceiro'],
                    ],
                ],
                'categorias' => [
                    'title' => 'Categorias',
                    'enabled' => true,
                    'limit' => 6,
                ],
            ],
        ];
    }

    /**
     * Obtém a configuração do footer com defaults aplicados
     */
    public static function getFooterConfig(): array
    {
        $defaults = self::getDefaultFooterConfig();
        $saved = self::getJson('theme_footer', []);
        
        // Fazer merge recursivo para manter estrutura completa
        $footer = $defaults;
        
        if (!empty($saved['sections'])) {
            foreach ($saved['sections'] as $sectionKey => $sectionData) {
                if (isset($footer['sections'][$sectionKey])) {
                    // Merge de seção
                    $footer['sections'][$sectionKey] = array_merge(
                        $footer['sections'][$sectionKey],
                        $sectionData
                    );
                    
                    // Merge de links se existirem
                    if (isset($sectionData['links']) && isset($footer['sections'][$sectionKey]['links'])) {
                        foreach ($sectionData['links'] as $linkKey => $linkData) {
                            if (isset($footer['sections'][$sectionKey]['links'][$linkKey])) {
                                $footer['sections'][$sectionKey]['links'][$linkKey] = array_merge(
                                    $footer['sections'][$sectionKey]['links'][$linkKey],
                                    $linkData
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return $footer;
    }

    /**
     * Salva a configuração do footer
     */
    public static function setFooterConfig(array $footer): void
    {
        // Fazer merge com defaults para garantir estrutura completa
        $defaults = self::getDefaultFooterConfig();
        $merged = $defaults;
        
        if (!empty($footer['sections'])) {
            foreach ($footer['sections'] as $sectionKey => $sectionData) {
                if (isset($merged['sections'][$sectionKey])) {
                    $merged['sections'][$sectionKey] = array_merge(
                        $merged['sections'][$sectionKey],
                        $sectionData
                    );
                    
                    if (isset($sectionData['links']) && isset($merged['sections'][$sectionKey]['links'])) {
                        foreach ($sectionData['links'] as $linkKey => $linkData) {
                            if (isset($merged['sections'][$sectionKey]['links'][$linkKey])) {
                                $merged['sections'][$sectionKey]['links'][$linkKey] = array_merge(
                                    $merged['sections'][$sectionKey]['links'][$linkKey],
                                    $linkData
                                );
                            }
                        }
                    }
                }
            }
        }
        
        self::set('theme_footer', $merged);
    }
}


