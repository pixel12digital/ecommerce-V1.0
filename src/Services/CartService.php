<?php

namespace App\Services;

use App\Tenant\TenantContext;

class CartService
{
    private const SESSION_KEY_PREFIX = 'cart_';

    /**
     * Obtém a chave da sessão para o tenant atual
     */
    private static function getSessionKey(): string
    {
        $tenantId = TenantContext::id();
        return self::SESSION_KEY_PREFIX . $tenantId;
    }

    /**
     * Inicializa a sessão se necessário
     */
    private static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Obtém o carrinho completo
     */
    public static function get(): array
    {
        self::initSession();
        $key = self::getSessionKey();
        return $_SESSION[$key] ?? ['items' => []];
    }

    /**
     * Salva o carrinho na sessão
     */
    public static function save(array $cart): void
    {
        self::initSession();
        $key = self::getSessionKey();
        $_SESSION[$key] = $cart;
    }

    /**
     * Limpa o carrinho
     */
    public static function clear(): void
    {
        self::initSession();
        $key = self::getSessionKey();
        unset($_SESSION[$key]);
    }

    /**
     * Adiciona ou atualiza um item no carrinho
     */
    public static function addItem(int $produtoId, array $itemData): void
    {
        $cart = self::get();
        
        if (isset($cart['items'][$produtoId])) {
            // Se já existe, soma a quantidade
            $cart['items'][$produtoId]['quantidade'] += ($itemData['quantidade'] ?? 1);
        } else {
            // Se não existe, cria novo item
            $cart['items'][$produtoId] = $itemData;
        }
        
        self::save($cart);
    }

    /**
     * Atualiza a quantidade de um item
     */
    public static function updateItem(int $produtoId, int $quantidade): bool
    {
        $cart = self::get();
        
        if (!isset($cart['items'][$produtoId])) {
            return false;
        }
        
        if ($quantidade <= 0) {
            // Remove o item se quantidade for 0 ou negativa
            unset($cart['items'][$produtoId]);
        } else {
            $cart['items'][$produtoId]['quantidade'] = $quantidade;
        }
        
        self::save($cart);
        return true;
    }

    /**
     * Remove um item do carrinho
     */
    public static function removeItem(int $produtoId): bool
    {
        $cart = self::get();
        
        if (!isset($cart['items'][$produtoId])) {
            return false;
        }
        
        unset($cart['items'][$produtoId]);
        self::save($cart);
        return true;
    }

    /**
     * Calcula o subtotal do carrinho
     */
    public static function getSubtotal(): float
    {
        $cart = self::get();
        $subtotal = 0.0;
        
        foreach ($cart['items'] as $item) {
            $subtotal += ($item['preco_unitario'] ?? 0) * ($item['quantidade'] ?? 0);
        }
        
        return $subtotal;
    }

    /**
     * Conta o total de itens (soma das quantidades)
     */
    public static function getTotalItems(): int
    {
        $cart = self::get();
        $total = 0;
        
        foreach ($cart['items'] as $item) {
            $total += ($item['quantidade'] ?? 0);
        }
        
        return $total;
    }

    /**
     * Verifica se o carrinho está vazio
     */
    public static function isEmpty(): bool
    {
        $cart = self::get();
        return empty($cart['items']);
    }
}


