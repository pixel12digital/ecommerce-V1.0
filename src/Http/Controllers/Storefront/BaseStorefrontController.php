<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Services\ThemeConfig;
use App\Services\CartService;
use App\Tenant\TenantContext;

/**
 * Classe base para controllers do storefront
 * Fornece métodos comuns para carregar tema e dados do carrinho
 */
abstract class BaseStorefrontController extends Controller
{
    /**
     * Carrega todas as configurações do tema
     */
    protected function getThemeConfig(): array
    {
        return ThemeConfig::getFullThemeConfig();
    }

    /**
     * Carrega dados do carrinho para o header
     */
    protected function getCartData(): array
    {
        return [
            'cartTotalItems' => CartService::getTotalItems(),
            'cartSubtotal' => CartService::getSubtotal(),
        ];
    }

    /**
     * Carrega dados básicos da loja
     */
    protected function getStoreData(): array
    {
        $tenant = TenantContext::tenant();
        return [
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
        ];
    }

    /**
     * Retorna dados padrão para views do storefront
     */
    protected function getDefaultViewData(): array
    {
        return array_merge(
            $this->getStoreData(),
            [
                'theme' => $this->getThemeConfig(),
            ],
            $this->getCartData()
        );
    }
}

