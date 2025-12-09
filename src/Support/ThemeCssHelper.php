<?php

namespace App\Support;

use App\Services\ThemeConfig;

/**
 * Helper para gerar variáveis CSS do tema
 */
class ThemeCssHelper
{
    /**
     * Gera o bloco de variáveis CSS :root com todas as cores do tema
     */
    public static function generateCssVariables(): string
    {
        $colors = ThemeConfig::getAllThemeColors();
        
        $css = ":root {\n";
        $css .= "    --pg-color-primary: " . htmlspecialchars($colors['color_primary'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-secondary: " . htmlspecialchars($colors['color_secondary'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-topbar-bg: " . htmlspecialchars($colors['color_topbar_bg'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-topbar-text: " . htmlspecialchars($colors['color_topbar_text'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-header-bg: " . htmlspecialchars($colors['color_header_bg'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-header-text: " . htmlspecialchars($colors['color_header_text'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-footer-bg: " . htmlspecialchars($colors['color_footer_bg'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "    --pg-color-footer-text: " . htmlspecialchars($colors['color_footer_text'], ENT_QUOTES, 'UTF-8') . ";\n";
        $css .= "}\n";
        
        return $css;
    }
}

