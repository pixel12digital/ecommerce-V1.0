<?php
/**
 * Script direto para consultar configuração Correios no banco remoto
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Consulta Correios</title>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .erro{color:red;} .aviso{color:orange;} pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}</style>";
echo "</head><body>";
echo "<h1>🔍 Consulta de Configuração Correios</h1>";

try {
    $db = Database::getConnection();
    echo "<p class='ok'>✅ Conectado ao banco remoto!</p>";
    
    // Buscar configurações
    $stmt = $db->query("
        SELECT 
            tenant_id,
            codigo,
            ativo,
            config_json,
            created_at,
            updated_at
        FROM tenant_gateways 
        WHERE tipo = 'shipping' 
        AND codigo = 'correios'
        ORDER BY tenant_id
    ");
    
    $configs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($configs)) {
        echo "<p class='erro'>❌ Nenhuma configuração Correios encontrada no banco.</p>";
        echo "<p>Configure o gateway Correios no painel admin primeiro.</p>";
    } else {
        foreach ($configs as $config) {
            echo "<hr><h2>Configuração (Tenant ID: {$config['tenant_id']})</h2>";
            echo "<p><strong>Status:</strong> " . ($config['ativo'] ? '<span class="ok">✅ Ativo</span>' : '<span class="erro">❌ Inativo</span>') . "</p>";
            echo "<p><strong>Atualizado em:</strong> {$config['updated_at']}</p>";
            
            if (empty($config['config_json'])) {
                echo "<p class='erro'>⚠️ config_json está vazio!</p>";
                continue;
            }
            
            $json = json_decode($config['config_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "<p class='erro'>❌ JSON inválido: " . json_last_error_msg() . "</p>";
                continue;
            }
            
            $correios = $json['correios'] ?? $json;
            
            // Modo
            $modo = $correios['modo_integracao'] ?? 'não definido';
            echo "<h3>📋 Modo de Integração</h3>";
            echo "<p>" . ($modo !== 'não definido' ? "✅ <strong>{$modo}</strong>" : "❌ Não definido") . "</p>";
            
            // Origem
            echo "<h3>📍 Dados de Origem</h3>";
            $origem = $correios['origem'] ?? [];
            $cep = $origem['cep'] ?? 'NÃO PREENCHIDO';
            $nome = $origem['nome'] ?? 'NÃO PREENCHIDO';
            $telefone = $origem['telefone'] ?? 'não preenchido';
            $documento = $origem['documento'] ?? 'não preenchido';
            
            echo "<p>" . (!empty($origem['cep']) && $origem['cep'] !== '00000000' ? "✅" : "❌") . " <strong>CEP:</strong> {$cep}</p>";
            echo "<p>" . (!empty($origem['nome']) ? "✅" : "❌") . " <strong>Nome:</strong> {$nome}</p>";
            echo "<p>" . (!empty($origem['telefone']) ? "✅" : "⚠️") . " <strong>Telefone:</strong> {$telefone}</p>";
            echo "<p>" . (!empty($origem['documento']) ? "✅" : "⚠️") . " <strong>Documento:</strong> {$documento}</p>";
            
            // Endereço
            $endereco = $origem['endereco'] ?? [];
            if (!empty($endereco)) {
                echo "<h4>Endereço:</h4><ul>";
                foreach ($endereco as $campo => $valor) {
                    echo "<li><strong>{$campo}:</strong> " . ($valor ?: 'não preenchido') . "</li>";
                }
                echo "</ul>";
            }
            
            // Credenciais
            echo "<h3>🔐 Credenciais</h3>";
            $credenciais = $correios['credenciais'] ?? [];
            $usuario = $credenciais['usuario'] ?? null;
            $senha = $credenciais['senha'] ?? null;
            $chaveCws = $credenciais['chave_acesso_cws'] ?? null;
            
            echo "<p>" . (!empty($usuario) ? "✅" : "❌") . " <strong>Usuário:</strong> " . ($usuario ? substr($usuario, 0, 3) . '***' : 'NÃO PREENCHIDO') . "</p>";
            echo "<p>" . (!empty($senha) ? "✅" : "⚠️") . " <strong>Senha (SFE):</strong> " . ($senha ? '***' . substr($senha, -2) : 'não preenchida') . "</p>";
            
            if ($chaveCws) {
                $tamanho = strlen($chaveCws);
                $preview = substr($chaveCws, 0, 10) . '...' . substr($chaveCws, -10);
                echo "<p class='ok'>✅ <strong>Chave de Acesso CWS:</strong> <code>{$preview}</code> (tamanho: {$tamanho} caracteres)</p>";
            } else {
                echo "<p class='erro'>❌ <strong>Chave de Acesso CWS:</strong> NÃO PREENCHIDA</p>";
            }
            
            // Serviços
            echo "<h3>🚚 Serviços</h3>";
            $servicos = $correios['servicos'] ?? [];
            $pac = $servicos['pac'] ?? false;
            $sedex = $servicos['sedex'] ?? false;
            echo "<p>" . ($pac ? "✅" : "❌") . " <strong>PAC:</strong> " . ($pac ? 'Habilitado' : 'Desabilitado') . "</p>";
            echo "<p>" . ($sedex ? "✅" : "❌") . " <strong>SEDEX:</strong> " . ($sedex ? 'Habilitado' : 'Desabilitado') . "</p>";
            
            // Resumo do que falta
            echo "<h3>📊 Resumo - O Que Falta</h3>";
            $faltando = [];
            
            if (empty($origem['cep']) || $origem['cep'] === '00000000') {
                $faltando[] = 'CEP de origem válido';
            }
            if (empty($origem['nome'])) {
                $faltando[] = 'Nome do remetente';
            }
            if (empty($usuario)) {
                $faltando[] = 'Usuário (Meu Correios)';
            }
            
            if ($modo === 'cws') {
                if (empty($chaveCws)) {
                    $faltando[] = 'Chave de Acesso CWS (obrigatória no modo CWS)';
                }
            } elseif ($modo === 'legado') {
                if (empty($senha)) {
                    $faltando[] = 'Senha (obrigatória no modo Legado/SIGEP)';
                }
            }
            
            if (!$pac && !$sedex) {
                $faltando[] = 'Pelo menos um serviço habilitado (PAC ou SEDEX)';
            }
            
            if (empty($faltando)) {
                echo "<p class='ok'><strong>✅ Todas as informações obrigatórias estão preenchidas!</strong></p>";
            } else {
                echo "<ul>";
                foreach ($faltando as $item) {
                    echo "<li class='erro'><strong>❌ {$item}</strong></li>";
                }
                echo "</ul>";
            }
            
            // JSON completo (para debug)
            echo "<details><summary>📄 Ver JSON Completo (Debug)</summary>";
            echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
            echo "</details>";
        }
    }
    
} catch (\Exception $e) {
    echo "<p class='erro'>❌ <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
