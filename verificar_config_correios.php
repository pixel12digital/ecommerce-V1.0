<?php
/**
 * Script para verificar configuração Correios no banco de dados
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getConnection();

echo "=== Verificação de Configuração Correios no Banco de Dados ===\n\n";

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
    echo "❌ Nenhuma configuração Correios encontrada no banco de dados.\n";
    echo "   Configure o gateway Correios no painel admin primeiro.\n";
    exit(1);
}

foreach ($configs as $index => $config) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Configuração #" . ($index + 1) . " (Tenant ID: {$config['tenant_id']})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    echo "Status: " . ($config['ativo'] ? '✅ Ativo' : '❌ Inativo') . "\n";
    echo "Criado em: {$config['created_at']}\n";
    echo "Atualizado em: {$config['updated_at']}\n\n";
    
    if (empty($config['config_json'])) {
        echo "⚠️  AVISO: config_json está vazio!\n";
        echo "   Configure o gateway no painel admin.\n\n";
        continue;
    }
    
    $json = json_decode($config['config_json'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ ERRO: JSON inválido - " . json_last_error_msg() . "\n\n";
        continue;
    }
    
    $correios = $json['correios'] ?? $json;
    
    // Verificar Modo de Integração
    echo "📋 MODO DE INTEGRAÇÃO:\n";
    $modoIntegracao = $correios['modo_integracao'] ?? 'não definido';
    echo "   " . ($modoIntegracao !== 'não definido' ? "✅ Modo: {$modoIntegracao}" : "❌ Não definido") . "\n\n";
    
    // Verificar Origem
    echo "📍 DADOS DE ORIGEM:\n";
    $origem = $correios['origem'] ?? [];
    
    $camposOrigem = [
        'cep' => 'CEP de Origem',
        'nome' => 'Nome do Remetente',
        'telefone' => 'Telefone',
        'documento' => 'Documento (CPF/CNPJ)',
    ];
    
    foreach ($camposOrigem as $campo => $label) {
        $valor = $origem[$campo] ?? null;
        $status = !empty($valor) ? '✅' : '❌';
        $valorExibido = $valor ?: 'NÃO PREENCHIDO';
        if ($campo === 'cep' && $valor === '00000000') {
            $status = '❌';
            $valorExibido = 'INVÁLIDO (00000000)';
        }
        echo "   {$status} {$label}: {$valorExibido}\n";
    }
    
    // Verificar Endereço
    echo "\n   📍 Endereço Completo:\n";
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
        $status = !empty($valor) ? '✅' : '⚠️ ';
        $valorExibido = $valor ?: 'não preenchido';
        echo "      {$status} {$label}: {$valorExibido}\n";
    }
    
    // Verificar Credenciais
    echo "\n🔐 CREDENCIAIS:\n";
    $credenciais = $correios['credenciais'] ?? [];
    
    // Usuário
    $usuario = $credenciais['usuario'] ?? null;
    $statusUsuario = !empty($usuario) ? '✅' : '❌';
    $usuarioExibido = $usuario ? substr($usuario, 0, 3) . '***' : 'NÃO PREENCHIDO';
    echo "   {$statusUsuario} Usuário: {$usuarioExibido}\n";
    
    // Senha
    $senha = $credenciais['senha'] ?? null;
    $statusSenha = !empty($senha) ? '✅' : '⚠️ ';
    $senhaExibido = $senha ? '***' . substr($senha, -2) : 'não preenchida';
    echo "   {$statusSenha} Senha (SFE): {$senhaExibido}\n";
    
    // Chave de Acesso CWS
    $chaveCws = $credenciais['chave_acesso_cws'] ?? null;
    $statusChaveCws = !empty($chaveCws) ? '✅' : '❌';
    if ($chaveCws) {
        $chaveExibida = substr($chaveCws, 0, 10) . '...' . substr($chaveCws, -10);
        $tamanho = strlen($chaveCws);
        echo "   {$statusChaveCws} Chave de Acesso CWS: {$chaveExibida} (tamanho: {$tamanho} caracteres)\n";
    } else {
        echo "   {$statusChaveCws} Chave de Acesso CWS: NÃO PREENCHIDA\n";
    }
    
    // Campos opcionais
    echo "\n   📋 Campos Opcionais:\n";
    $camposOpcionais = [
        'cartao_postagem' => 'Cartão de Postagem',
        'contrato' => 'Contrato',
        'codigo_administrativo' => 'Código Administrativo',
        'diretoria' => 'Diretoria',
    ];
    
    foreach ($camposOpcionais as $campo => $label) {
        $valor = $credenciais[$campo] ?? null;
        $status = !empty($valor) ? '✅' : '⚠️ ';
        $valorExibido = $valor ?: 'não preenchido';
        echo "      {$status} {$label}: {$valorExibido}\n";
    }
    
    // Verificar Serviços
    echo "\n🚚 SERVIÇOS HABILITADOS:\n";
    $servicos = $correios['servicos'] ?? [];
    $pac = $servicos['pac'] ?? false;
    $sedex = $servicos['sedex'] ?? false;
    echo "   " . ($pac ? '✅' : '❌') . " PAC: " . ($pac ? 'Habilitado' : 'Desabilitado') . "\n";
    echo "   " . ($sedex ? '✅' : '❌') . " SEDEX: " . ($sedex ? 'Habilitado' : 'Desabilitado') . "\n";
    
    if (!$pac && !$sedex) {
        echo "   ⚠️  AVISO: Nenhum serviço habilitado!\n";
    }
    
    // Verificar Seguro
    echo "\n🛡️  SEGURO:\n";
    $seguro = $correios['seguro'] ?? [];
    $seguroHabilitado = $seguro['habilitado'] ?? false;
    echo "   " . ($seguroHabilitado ? '✅' : '⚠️ ') . " Habilitado: " . ($seguroHabilitado ? 'Sim' : 'Não') . "\n";
    
    // Resumo do que falta
    echo "\n📊 RESUMO - O QUE FALTA:\n";
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
    
    if (empty($faltando)) {
        echo "   ✅ Todas as informações obrigatórias estão preenchidas!\n";
    } else {
        foreach ($faltando as $item) {
            echo "   ❌ {$item}\n";
        }
    }
    
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Verificação concluída!\n";
