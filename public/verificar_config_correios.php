<?php
/**
 * Página para verificar configuração Correios no banco de dados
 * Acesse via: http://localhost/ecommerce-v1.0/public/verificar_config_correios.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente do .env
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
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
} else {
    echo '<div style="padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin: 20px;">';
    echo '<h2>⚠️ Arquivo .env não encontrado</h2>';
    echo '<p>O arquivo <code>.env</code> não foi encontrado na raiz do projeto.</p>';
    echo '<p><strong>Para banco remoto, crie o arquivo <code>.env</code> com:</strong></p>';
    echo '<pre style="background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto;">';
    echo "DB_HOST=seu_host_remoto\n";
    echo "DB_PORT=3306\n";
    echo "DB_NAME=nome_do_banco\n";
    echo "DB_USER=usuario_banco\n";
    echo "DB_PASS=senha_banco\n";
    echo '</pre>';
    echo '</div>';
    exit;
}

use App\Core\Database;

header('Content-Type: text/html; charset=utf-8');

// Mostrar informações de conexão (sem senha)
$config = require __DIR__ . '/../config/database.php';
echo '<div style="padding: 15px; background: #e7f3ff; border-radius: 8px; margin-bottom: 20px;">';
echo '<strong>🔌 Tentando conectar ao banco:</strong><br>';
echo 'Host: ' . htmlspecialchars($config['host']) . '<br>';
echo 'Porta: ' . htmlspecialchars($config['port']) . '<br>';
echo 'Banco: ' . htmlspecialchars($config['name']) . '<br>';
echo 'Usuário: ' . htmlspecialchars($config['user']) . '<br>';
echo '</div>';

try {
    $db = Database::getConnection();
    echo '<div style="padding: 15px; background: #d4edda; border-radius: 8px; margin-bottom: 20px; color: #155724;">';
    echo '<strong>✅ Conexão estabelecida com sucesso!</strong>';
    echo '</div>';
} catch (\Exception $e) {
    echo '<div style="padding: 20px; background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px; margin: 20px;">';
    echo '<h2>❌ Erro ao conectar ao banco de dados</h2>';
    echo '<p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<h3>Possíveis causas:</h3>';
    echo '<ul>';
    echo '<li>O servidor MySQL remoto não está acessível</li>';
    echo '<li>As credenciais no arquivo <code>.env</code> estão incorretas</li>';
    echo '<li>O firewall está bloqueando a conexão</li>';
    echo '<li>O IP do seu servidor não está autorizado no banco remoto</li>';
    echo '</ul>';
    echo '<p><strong>Verifique:</strong></p>';
    echo '<ul>';
    echo '<li>Se o arquivo <code>.env</code> tem as credenciais corretas</li>';
    echo '<li>Se o banco remoto permite conexões do seu IP</li>';
    echo '<li>Se a porta 3306 está aberta no firewall</li>';
    echo '</ul>';
    echo '</div>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Configuração Correios</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0066cc;
            margin-top: 0;
        }
        .config-block {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        .config-block h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
        }
        .field {
            margin: 10px 0;
            padding: 8px;
            background: white;
            border-left: 4px solid #ddd;
            border-radius: 4px;
        }
        .field.ok {
            border-left-color: #28a745;
        }
        .field.warning {
            border-left-color: #ffc107;
        }
        .field.error {
            border-left-color: #dc3545;
        }
        .status {
            font-weight: 600;
            margin-right: 10px;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #e7f3ff;
        }
        .summary.error {
            background: #fff3cd;
        }
        .summary.success {
            background: #d4edda;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .empty {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Configuração Correios no Banco de Dados</h1>
        
        <?php
        // Buscar todas as configurações de frete Correios
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
            echo '<div class="config-block error">';
            echo '<h2>❌ Nenhuma configuração encontrada</h2>';
            echo '<p>Nenhuma configuração Correios encontrada no banco de dados.</p>';
            echo '<p>Configure o gateway Correios no painel admin primeiro.</p>';
            echo '</div>';
        } else {
            foreach ($configs as $index => $config) {
                echo '<div class="config-block">';
                echo '<h2>Configuração #' . ($index + 1) . ' (Tenant ID: ' . htmlspecialchars($config['tenant_id']) . ')</h2>';
                
                echo '<div class="field ' . ($config['ativo'] ? 'ok' : 'error') . '">';
                echo '<span class="status">' . ($config['ativo'] ? '✅' : '❌') . '</span>';
                echo '<strong>Status:</strong> ' . ($config['ativo'] ? 'Ativo' : 'Inativo');
                echo '</div>';
                
                echo '<div class="field">';
                echo '<strong>Criado em:</strong> ' . htmlspecialchars($config['created_at']);
                echo '</div>';
                
                echo '<div class="field">';
                echo '<strong>Atualizado em:</strong> ' . htmlspecialchars($config['updated_at']);
                echo '</div>';
                
                if (empty($config['config_json'])) {
                    echo '<div class="field error">';
                    echo '<span class="status">⚠️</span>';
                    echo '<strong>AVISO:</strong> config_json está vazio! Configure o gateway no painel admin.';
                    echo '</div>';
                    echo '</div>';
                    continue;
                }
                
                $json = json_decode($config['config_json'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo '<div class="field error">';
                    echo '<span class="status">❌</span>';
                    echo '<strong>ERRO:</strong> JSON inválido - ' . htmlspecialchars(json_last_error_msg());
                    echo '</div>';
                    echo '</div>';
                    continue;
                }
                
                $correios = $json['correios'] ?? $json;
                
                // Modo de Integração
                echo '<h3>📋 Modo de Integração</h3>';
                $modoIntegracao = $correios['modo_integracao'] ?? 'não definido';
                $modoStatus = $modoIntegracao !== 'não definido' ? 'ok' : 'error';
                echo '<div class="field ' . $modoStatus . '">';
                echo '<span class="status">' . ($modoIntegracao !== 'não definido' ? '✅' : '❌') . '</span>';
                echo '<strong>Modo:</strong> ' . htmlspecialchars($modoIntegracao);
                echo '</div>';
                
                // Dados de Origem
                echo '<h3>📍 Dados de Origem</h3>';
                $origem = $correios['origem'] ?? [];
                
                $camposOrigem = [
                    'cep' => 'CEP de Origem',
                    'nome' => 'Nome do Remetente',
                    'telefone' => 'Telefone',
                    'documento' => 'Documento (CPF/CNPJ)',
                ];
                
                foreach ($camposOrigem as $campo => $label) {
                    $valor = $origem[$campo] ?? null;
                    $status = 'error';
                    $icon = '❌';
                    $valorExibido = 'NÃO PREENCHIDO';
                    
                    if (!empty($valor)) {
                        if ($campo === 'cep' && $valor === '00000000') {
                            $status = 'error';
                            $icon = '❌';
                            $valorExibido = 'INVÁLIDO (00000000)';
                        } else {
                            $status = 'ok';
                            $icon = '✅';
                            $valorExibido = htmlspecialchars($valor);
                        }
                    }
                    
                    echo '<div class="field ' . $status . '">';
                    echo '<span class="status">' . $icon . '</span>';
                    echo '<strong>' . htmlspecialchars($label) . ':</strong> ' . $valorExibido;
                    echo '</div>';
                }
                
                // Endereço
                echo '<h4>📍 Endereço Completo</h4>';
                $endereco = $origem['endereco'] ?? [];
                $camposEndereco = [
                    'logradouro' => 'Logradouro',
                    'numero' => 'Número',
                    'complemento' => 'Complemento',
                    'bairro' => 'Bairro',
                    'cidade' => 'Cidade',
                    'uf' => 'UF',
                ];
                
                foreach ($camposEndereco as $campo => $label) {
                    $valor = $endereco[$campo] ?? null;
                    $status = !empty($valor) ? 'ok' : 'warning';
                    $icon = !empty($valor) ? '✅' : '⚠️';
                    $valorExibido = $valor ? htmlspecialchars($valor) : '<span class="empty">não preenchido</span>';
                    
                    echo '<div class="field ' . $status . '">';
                    echo '<span class="status">' . $icon . '</span>';
                    echo '<strong>' . htmlspecialchars($label) . ':</strong> ' . $valorExibido;
                    echo '</div>';
                }
                
                // Credenciais
                echo '<h3>🔐 Credenciais</h3>';
                $credenciais = $correios['credenciais'] ?? [];
                
                // Usuário
                $usuario = $credenciais['usuario'] ?? null;
                $statusUsuario = !empty($usuario) ? 'ok' : 'error';
                $usuarioExibido = $usuario ? substr($usuario, 0, 3) . '***' : 'NÃO PREENCHIDO';
                echo '<div class="field ' . $statusUsuario . '">';
                echo '<span class="status">' . (!empty($usuario) ? '✅' : '❌') . '</span>';
                echo '<strong>Usuário:</strong> ' . htmlspecialchars($usuarioExibido);
                echo '</div>';
                
                // Senha
                $senha = $credenciais['senha'] ?? null;
                $statusSenha = !empty($senha) ? 'ok' : 'warning';
                $senhaExibido = $senha ? '***' . substr($senha, -2) : '<span class="empty">não preenchida</span>';
                echo '<div class="field ' . $statusSenha . '">';
                echo '<span class="status">' . (!empty($senha) ? '✅' : '⚠️') . '</span>';
                echo '<strong>Senha (SFE):</strong> ' . $senhaExibido;
                echo '</div>';
                
                // Chave de Acesso CWS
                $chaveCws = $credenciais['chave_acesso_cws'] ?? null;
                $statusChaveCws = !empty($chaveCws) ? 'ok' : 'error';
                if ($chaveCws) {
                    $chaveExibida = substr($chaveCws, 0, 10) . '...' . substr($chaveCws, -10);
                    $tamanho = strlen($chaveCws);
                    echo '<div class="field ' . $statusChaveCws . '">';
                    echo '<span class="status">✅</span>';
                    echo '<strong>Chave de Acesso CWS:</strong> <code>' . htmlspecialchars($chaveExibida) . '</code> (tamanho: ' . $tamanho . ' caracteres)';
                    echo '</div>';
                } else {
                    echo '<div class="field ' . $statusChaveCws . '">';
                    echo '<span class="status">❌</span>';
                    echo '<strong>Chave de Acesso CWS:</strong> <span class="empty">NÃO PREENCHIDA</span>';
                    echo '</div>';
                }
                
                // Campos opcionais
                echo '<h4>📋 Campos Opcionais</h4>';
                $camposOpcionais = [
                    'cartao_postagem' => 'Cartão de Postagem',
                    'contrato' => 'Contrato',
                    'codigo_administrativo' => 'Código Administrativo',
                    'diretoria' => 'Diretoria',
                ];
                
                foreach ($camposOpcionais as $campo => $label) {
                    $valor = $credenciais[$campo] ?? null;
                    $status = !empty($valor) ? 'ok' : 'warning';
                    $icon = !empty($valor) ? '✅' : '⚠️';
                    $valorExibido = $valor ? htmlspecialchars($valor) : '<span class="empty">não preenchido</span>';
                    
                    echo '<div class="field ' . $status . '">';
                    echo '<span class="status">' . $icon . '</span>';
                    echo '<strong>' . htmlspecialchars($label) . ':</strong> ' . $valorExibido;
                    echo '</div>';
                }
                
                // Serviços
                echo '<h3>🚚 Serviços Habilitados</h3>';
                $servicos = $correios['servicos'] ?? [];
                $pac = $servicos['pac'] ?? false;
                $sedex = $servicos['sedex'] ?? false;
                
                echo '<div class="field ' . ($pac ? 'ok' : 'error') . '">';
                echo '<span class="status">' . ($pac ? '✅' : '❌') . '</span>';
                echo '<strong>PAC:</strong> ' . ($pac ? 'Habilitado' : 'Desabilitado');
                echo '</div>';
                
                echo '<div class="field ' . ($sedex ? 'ok' : 'error') . '">';
                echo '<span class="status">' . ($sedex ? '✅' : '❌') . '</span>';
                echo '<strong>SEDEX:</strong> ' . ($sedex ? 'Habilitado' : 'Desabilitado');
                echo '</div>';
                
                if (!$pac && !$sedex) {
                    echo '<div class="field error">';
                    echo '<span class="status">⚠️</span>';
                    echo '<strong>AVISO:</strong> Nenhum serviço habilitado!';
                    echo '</div>';
                }
                
                // Resumo do que falta
                $faltando = [];
                
                if (empty($origem['cep']) || $origem['cep'] === '00000000') {
                    $faltando[] = 'CEP de origem válido';
                }
                if (empty($origem['nome'])) {
                    $faltando[] = 'Nome do remetente';
                }
                if (empty($credenciais['usuario'])) {
                    $faltando[] = 'Usuário (Meu Correios)';
                }
                
                if ($modoIntegracao === 'cws') {
                    if (empty($credenciais['chave_acesso_cws'])) {
                        $faltando[] = 'Chave de Acesso CWS (obrigatória no modo CWS)';
                    }
                } elseif ($modoIntegracao === 'legado') {
                    if (empty($credenciais['senha'])) {
                        $faltando[] = 'Senha (obrigatória no modo Legado/SIGEP)';
                    }
                }
                
                if (!$pac && !$sedex) {
                    $faltando[] = 'Pelo menos um serviço habilitado (PAC ou SEDEX)';
                }
                
                echo '<div class="summary ' . (empty($faltando) ? 'success' : 'error') . '">';
                echo '<h3>📊 Resumo - O Que Falta</h3>';
                
                if (empty($faltando)) {
                    echo '<p><strong>✅ Todas as informações obrigatórias estão preenchidas!</strong></p>';
                } else {
                    echo '<ul>';
                    foreach ($faltando as $item) {
                        echo '<li><strong>❌ ' . htmlspecialchars($item) . '</strong></li>';
                    }
                    echo '</ul>';
                }
                echo '</div>';
                
                echo '</div>';
            }
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 8px;">
            <p><strong>💡 Dica:</strong> Para preencher as informações faltantes, acesse o painel admin:</p>
            <p><code>Configurações > Gateways > Correios</code></p>
        </div>
    </div>
</body>
</html>
