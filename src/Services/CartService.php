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
     * Gera a chave de identificação do item no carrinho
     * Se tiver variacao_id: "v:{variacao_id}"
     * Senão: "p:{produto_id}"
     */
    private static function getItemKey(?int $variacaoId, int $produtoId): string
    {
        if ($variacaoId !== null && $variacaoId > 0) {
            return "v:{$variacaoId}";
        }
        return "p:{$produtoId}";
    }

    /**
     * Adiciona ou atualiza um item no carrinho
     * 
     * @param int $produtoId ID do produto
     * @param array $itemData Dados do item (deve incluir 'variacao_id' se for variação)
     */
    public static function addItem(int $produtoId, array $itemData): void
    {
        $cart = self::get();
        
        $variacaoId = isset($itemData['variacao_id']) && $itemData['variacao_id'] > 0 
            ? (int)$itemData['variacao_id'] 
            : null;
        
        $itemKey = self::getItemKey($variacaoId, $produtoId);
        
        if (isset($cart['items'][$itemKey])) {
            // Se já existe, soma a quantidade
            $cart['items'][$itemKey]['quantidade'] += ($itemData['quantidade'] ?? 1);
        } else {
            // Se não existe, cria novo item
            $cart['items'][$itemKey] = $itemData;
        }
        
        self::save($cart);
    }

    /**
     * Atualiza a quantidade de um item
     * 
     * @param string $itemKey Chave do item (formato "p:{id}" ou "v:{id}")
     * @param int $quantidade Nova quantidade
     */
    public static function updateItem(string $itemKey, int $quantidade): bool
    {
        $cart = self::get();
        
        if (!isset($cart['items'][$itemKey])) {
            return false;
        }
        
        if ($quantidade <= 0) {
            // Remove o item se quantidade for 0 ou negativa
            unset($cart['items'][$itemKey]);
        } else {
            $cart['items'][$itemKey]['quantidade'] = $quantidade;
        }
        
        self::save($cart);
        return true;
    }

    /**
     * Remove um item do carrinho
     * 
     * @param string $itemKey Chave do item (formato "p:{id}" ou "v:{id}")
     */
    public static function removeItem(string $itemKey): bool
    {
        $cart = self::get();
        
        if (!isset($cart['items'][$itemKey])) {
            return false;
        }
        
        unset($cart['items'][$itemKey]);
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


