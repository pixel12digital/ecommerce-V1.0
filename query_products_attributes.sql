-- ===================================================================
-- CONSULTA: PRODUTOS COM ATRIBUTOS/VARIAÇÕES POR CATEGORIA
-- Execute este SQL no banco de dados de produção
-- ===================================================================

-- 1. TOTAL DE PRODUTOS COM ATRIBUTOS
SELECT 'TOTAL DE PRODUTOS COM ATRIBUTOS' as info;
SELECT COUNT(DISTINCT p.id) as total_produtos_com_atributos
FROM produtos p
INNER JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
WHERE p.tenant_id = 1
AND p.status = 'publish';

-- 2. PRODUTOS POR CATEGORIA (COM ATRIBUTOS E VARIÁVEIS)
SELECT 'PRODUTOS POR CATEGORIA' as info;
SELECT 
    c.nome as categoria,
    c.slug as categoria_slug,
    COUNT(DISTINCT p.id) as total_produtos,
    COUNT(DISTINCT CASE WHEN pa.id IS NOT NULL THEN p.id END) as produtos_com_atributos,
    COUNT(DISTINCT CASE WHEN p.tipo = 'variable' THEN p.id END) as produtos_variaveis
FROM categorias c
LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
LEFT JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = pc.tenant_id AND p.status = 'publish'
LEFT JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
WHERE c.tenant_id = 1
GROUP BY c.id, c.nome, c.slug
HAVING total_produtos > 0
ORDER BY produtos_com_atributos DESC, c.nome ASC;

-- 3. ATRIBUTOS MAIS USADOS
SELECT 'ATRIBUTOS MAIS USADOS' as info;
SELECT 
    a.nome as atributo,
    a.tipo,
    COUNT(DISTINCT pa.produto_id) as total_produtos
FROM atributos a
INNER JOIN produto_atributos pa ON pa.atributo_id = a.id AND pa.tenant_id = a.tenant_id
WHERE a.tenant_id = 1
GROUP BY a.id, a.nome, a.tipo
ORDER BY total_produtos DESC
LIMIT 10;

-- 4. PRODUTOS COM TERMOS DE ATRIBUTOS (TAMANHOS)
SELECT 'PRODUTOS COM TERMOS POR CATEGORIA' as info;
SELECT 
    c.nome as categoria,
    COUNT(DISTINCT pat.produto_id) as produtos_com_termos
FROM categorias c
LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
LEFT JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id AND pat.tenant_id = pc.tenant_id
WHERE c.tenant_id = 1
GROUP BY c.id, c.nome
HAVING produtos_com_termos > 0
ORDER BY produtos_com_termos DESC;

-- 5. TAMANHOS DISPONÍVEIS POR CATEGORIA
SELECT 'TAMANHOS DISPONÍVEIS POR CATEGORIA' as info;
SELECT 
    c.nome as categoria,
    GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos_disponiveis
FROM categorias c
INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
INNER JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id AND pat.tenant_id = pc.tenant_id
INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id AND at.tenant_id = pat.tenant_id
INNER JOIN atributos a ON a.id = at.atributo_id AND a.tenant_id = at.tenant_id
WHERE c.tenant_id = 1
GROUP BY c.id, c.nome
ORDER BY c.nome;

-- 6. EXEMPLO: PRODUTOS DE UMA CATEGORIA ESPECÍFICA COM SEUS TAMANHOS
SELECT 'EXEMPLO: BLUSAS FEMININAS COM TAMANHOS' as info;
SELECT 
    p.nome as produto,
    GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos
FROM produtos p
INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = p.tenant_id
INNER JOIN categorias c ON c.id = pc.categoria_id AND c.tenant_id = pc.tenant_id
INNER JOIN produto_atributo_termos pat ON pat.produto_id = p.id AND pat.tenant_id = p.tenant_id
INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id AND at.tenant_id = pat.tenant_id
WHERE p.tenant_id = 1
AND c.slug LIKE '%blusa%'
AND p.status = 'publish'
GROUP BY p.id, p.nome
ORDER BY p.nome
LIMIT 20;
