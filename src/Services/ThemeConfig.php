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
}


