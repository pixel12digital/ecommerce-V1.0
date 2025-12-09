-- SQL para adicionar domínio ao tenant manualmente
-- Execute este SQL no phpMyAdmin ou via linha de comando

-- Primeiro, verifique qual tenant você quer usar (geralmente ID 1)
SELECT id, name, slug FROM tenants;

-- Adicione o domínio ao tenant (substitua o tenant_id se necessário)
INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain, ssl_status, created_at, updated_at)
VALUES (1, 'pontodogolfeoutlet.com.br', 1, 1, 'pending', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    tenant_id = VALUES(tenant_id),
    is_primary = VALUES(is_primary),
    is_custom_domain = VALUES(is_custom_domain),
    updated_at = NOW();

-- Verificar se foi adicionado
SELECT td.*, t.name as tenant_name 
FROM tenant_domains td
INNER JOIN tenants t ON t.id = td.tenant_id
WHERE td.domain = 'pontodogolfeoutlet.com.br';

