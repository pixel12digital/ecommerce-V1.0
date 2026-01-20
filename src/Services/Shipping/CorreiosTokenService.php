<?php

namespace App\Services\Shipping;

use App\Tenant\TenantContext;

/**
 * Serviço para geração e cache de tokens da API Correios CWS
 * 
 * Fluxo:
 * - Autenticação Basic (usuario:codigo_acesso_apis)
 * - Se contrato fornecido: POST https://api.correios.com.br/token/v1/autentica/contrato
 * - Se não: POST https://api.correios.com.br/token/v1/autentica
 * - Cache persistente do token por tenant até expiraEm
 */
class CorreiosTokenService
{
    /**
     * Cache em memória (por processo) para performance
     * Formato: ['token' => string, 'expiraEm' => timestamp, 'usuario' => string, 'chave' => string]
     */
    private static $tokenCache = [];
    
    /**
     * Diretório base para cache persistente
     */
    private static function getCacheDir(): string
    {
        $cacheDir = __DIR__ . '/../../../../storage/cache/correios_tokens';
        if (!is_dir($cacheDir)) {
            $created = @mkdir($cacheDir, 0755, true);
            if (!$created && !is_dir($cacheDir)) {
                error_log("CorreiosTokenService: Não foi possível criar diretório de cache: {$cacheDir}");
                // Continuar mesmo sem cache persistente (usar apenas cache em memória)
            }
        }
        return $cacheDir;
    }
    
    /**
     * Caminho do arquivo de cache para um tenant e credenciais
     */
    private static function getCacheFilePath(int $tenantId, string $cacheKey): string
    {
        $cacheDir = self::getCacheDir();
        $tenantDir = $cacheDir . '/' . $tenantId;
        if (!is_dir($tenantDir)) {
            $created = @mkdir($tenantDir, 0755, true);
            if (!$created && !is_dir($tenantDir)) {
                error_log("CorreiosTokenService: Não foi possível criar diretório de cache do tenant: {$tenantDir}");
                // Retornar caminho mesmo sem criar (pode falhar ao salvar, mas não quebra o fluxo)
            }
        }
        return $tenantDir . '/' . md5($cacheKey) . '.json';
    }

    /**
     * Obtém token válido para as APIs Correios CWS
     * 
     * @param string $usuario Usuário/ID Correios
     * @param string $codigoAcessoApis Código de acesso às APIs gerado no portal CWS
     * @param int|null $tenantId ID do tenant (opcional, usa TenantContext se não fornecido)
     * @param string|null $contrato Número do contrato (opcional, se fornecido usa endpoint autentica/contrato)
     * @return string Token de autenticação
     * @throws \Exception Em caso de erro na autenticação
     */
    public static function getToken(string $usuario, string $codigoAcessoApis, ?int $tenantId = null, ?string $contrato = null): string
    {
        // Validar parâmetros
        if (empty($usuario) || empty($codigoAcessoApis)) {
            throw new \Exception('Usuário e Código de acesso às APIs são obrigatórios para gerar token.');
        }

        // Obter tenant ID
        if ($tenantId === null) {
            try {
                $tenantId = TenantContext::id();
            } catch (\Exception $e) {
                $tenantId = 0; // Fallback para tenant 0
            }
        }

        // Chave do cache baseada em usuario+codigo+contrato (se houver)
        $cacheKey = $usuario . ':' . $codigoAcessoApis . ($contrato ? ':' . $contrato : '');
        $cacheKeyHash = md5($cacheKey);

        // 1. Verificar cache em memória
        if (isset(self::$tokenCache[$cacheKeyHash])) {
            $cached = self::$tokenCache[$cacheKeyHash];
            if (isset($cached['expiraEm']) && $cached['expiraEm'] > (time() + 60)) {
                return $cached['token'];
            }
        }

        // 2. Verificar cache persistente
        $cacheFile = self::getCacheFilePath($tenantId, $cacheKey);
        if (file_exists($cacheFile)) {
            $cachedData = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cachedData) && isset($cachedData['token'], $cachedData['expiraEm'])) {
                // Verificar se token ainda é válido (com margem de 60 segundos)
                if ($cachedData['expiraEm'] > (time() + 60)) {
                    // Atualizar cache em memória
                    self::$tokenCache[$cacheKeyHash] = $cachedData;
                    return $cachedData['token'];
                }
            }
        }

        // 3. Gerar novo token
        try {
            $resultado = self::gerarNovoToken($usuario, $codigoAcessoApis, $contrato);
            $token = $resultado['token'];
            $expiraEm = $resultado['expiraEm'] ?? (time() + 3600);
        } catch (\Exception $e) {
            // Se falhar, limpar cache e tentar uma vez mais
            @unlink($cacheFile);
            unset(self::$tokenCache[$cacheKeyHash]);
            
            // Tentar novamente apenas uma vez
            $resultado = self::gerarNovoToken($usuario, $codigoAcessoApis, $contrato);
            $token = $resultado['token'];
            $expiraEm = $resultado['expiraEm'] ?? (time() + 3600);
        }

        // 4. Salvar em cache (memória + persistente)
        $cacheData = [
            'token' => $token,
            'expiraEm' => $expiraEm,
            'created_at' => time(),
        ];
        
        self::$tokenCache[$cacheKeyHash] = $cacheData;
        
        // Salvar em arquivo (sem salvar credenciais por segurança)
        @file_put_contents($cacheFile, json_encode($cacheData), LOCK_EX);

        return $token;
    }

    /**
     * Gera novo token via API Correios CWS
     * 
     * @param string $usuario Usuário/ID Correios
     * @param string $codigoAcessoApis Código de acesso às APIs
     * @param string|null $contrato Número do contrato (opcional)
     * @return array ['token' => string, 'expiraEm' => int, 'endpoint_usado' => string]
     * @throws \Exception Em caso de erro na API
     */
    private static function gerarNovoToken(string $usuario, string $codigoAcessoApis, ?string $contrato = null): array
    {
        // Escolher endpoint automaticamente baseado na presença do contrato
        $url = 'https://api.correios.com.br/token/v1/autentica';
        $endpointUsado = 'autentica';
        
        if (!empty($contrato)) {
            $url = 'https://api.correios.com.br/token/v1/autentica/contrato';
            $endpointUsado = 'autentica/contrato';
        }

        // Limpar espaços e garantir que não estejam vazios
        $usuario = trim($usuario);
        $codigoAcessoApis = trim($codigoAcessoApis);
        $contrato = $contrato ? trim($contrato) : null;
        
        if (empty($usuario) || empty($codigoAcessoApis)) {
            throw new \Exception('Usuário e Código de acesso às APIs não podem estar vazios.');
        }

        // Preparar Basic Auth (formato: usuario:codigo_acesso_apis)
        $auth = base64_encode($usuario . ':' . $codigoAcessoApis);

        // Preparar body (se for endpoint de contrato, incluir número do contrato)
        $body = null;
        if (!empty($contrato)) {
            $body = json_encode(['numero' => $contrato]);
        }

        // Preparar requisição
        $ch = curl_init($url);
        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        // Se tiver body (contrato), enviar
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        // Verificar erros de conexão
        if ($response === false || !empty($curlError)) {
            error_log("Erro ao gerar token Correios CWS (conexão): " . $curlError);
            throw new \Exception('Erro ao conectar com API Correios CWS: ' . $curlError);
        }

        // Verificar código HTTP (200 = OK, 201 = Created - ambos são sucesso)
        if ($httpCode !== 200 && $httpCode !== 201) {
            // Log detalhado (sem credenciais - nunca logar usuario/codigo)
            $responsePreview = substr($response, 0, 500);
            error_log("Erro ao gerar token Correios CWS: HTTP {$httpCode} - Endpoint: {$endpointUsado} - Response preview: " . $responsePreview);
            
            // Tentar extrair mensagem de erro da resposta (se for JSON)
            $errorMessage = "Erro ao autenticar na API Correios CWS (HTTP {$httpCode})";
            if (!empty($response)) {
                $errorData = @json_decode($response, true);
                if (is_array($errorData) && isset($errorData['mensagem'])) {
                    $errorMessage .= ': ' . $errorData['mensagem'];
                } elseif (is_array($errorData) && isset($errorData['message'])) {
                    $errorMessage .= ': ' . $errorData['message'];
                } elseif (is_array($errorData) && isset($errorData['erro'])) {
                    $errorMessage .= ': ' . $errorData['erro'];
                }
            }
            
            // Mensagens específicas para códigos comuns
            if ($httpCode === 401) {
                $errorMessage = 'Credenciais inválidas. Verifique se o Usuário (login do Meu Correios) e o Código de acesso às APIs estão corretos. O usuário pode ser diferente do número do contrato.';
            } elseif ($httpCode === 403) {
                $errorMessage = 'Acesso negado. Verifique se o Código de acesso às APIs tem permissão para gerar tokens e se está associado ao contrato informado.';
            } elseif ($httpCode === 404) {
                $errorMessage = 'Endpoint não encontrado. Verifique se a URL da API está correta.';
            } elseif ($httpCode === 201) {
                // HTTP 201 (Created) também é sucesso, mas não deveria chegar aqui
                // Se chegou, significa que a resposta não foi processada corretamente
                $errorMessage = 'Token criado (HTTP 201), mas resposta não pôde ser processada. Verifique a resposta da API.';
            }
            
            throw new \Exception($errorMessage);
        }

        // Decodificar resposta JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro ao decodificar resposta token Correios CWS: " . json_last_error_msg());
            throw new \Exception('Resposta inválida da API Correios CWS');
        }

        // Extrair token da resposta
        // Formato esperado: {"token": "...", "expiraEm": "..."} ou similar
        $token = $data['token'] ?? $data['access_token'] ?? $data['accessToken'] ?? null;

        if (empty($token)) {
            error_log("Token não encontrado na resposta Correios CWS: " . substr($response, 0, 200));
            throw new \Exception('Token não retornado pela API Correios CWS');
        }

        // Calcular expiração (padrão: 1 hora se não vier na resposta)
        $expiraEm = time() + 3600; // 1 hora padrão
        
        // Atualizar expiração se vier na resposta
        if (isset($data['expiraEm']) || isset($data['expires_in'])) {
            $expiresIn = $data['expiraEm'] ?? $data['expires_in'];
            
            // Se for timestamp numérico, usar direto
            if (is_numeric($expiresIn) && $expiresIn > time()) {
                $expiraEm = (int)$expiresIn;
            } elseif (is_numeric($expiresIn)) {
                // Se for número menor que time(), assumir que são segundos para somar
                $expiraEm = time() + (int)$expiresIn;
            } elseif (is_string($expiresIn)) {
                // Se for string (formato ISO 8601 como "2026-01-20T15:14:14"), converter para timestamp
                $timestamp = strtotime($expiresIn);
                if ($timestamp !== false && $timestamp > time()) {
                    $expiraEm = $timestamp;
                }
            }
        }

        return [
            'token' => $token,
            'expiraEm' => $expiraEm,
            'endpoint_usado' => $endpointUsado,
        ];
    }

    /**
     * Limpa cache de tokens (útil para testes ou forçar renovação)
     * 
     * @param int|null $tenantId ID do tenant (opcional, limpa todos se não fornecido)
     */
    public static function limparCache(?int $tenantId = null): void
    {
        self::$tokenCache = [];
        
        if ($tenantId !== null) {
            // Limpar cache persistente do tenant específico
            $cacheDir = self::getCacheDir();
            $tenantDir = $cacheDir . '/' . $tenantId;
            if (is_dir($tenantDir)) {
                $files = glob($tenantDir . '/*.json');
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        } else {
            // Limpar todo o cache persistente
            $cacheDir = self::getCacheDir();
            if (is_dir($cacheDir)) {
                $dirs = glob($cacheDir . '/*', GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    $files = glob($dir . '/*.json');
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                }
            }
        }
    }
}
