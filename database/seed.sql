-- ============================================================
-- ModaSys — Dados de exemplo
-- Mesmos dados que hoje estão mockados direto no PHP (arrays
-- $produtos, $clientes, $vendas etc.). Rodar depois de schema.sql.
--
-- Serve para: testar a estrutura agora, e depois comparar com o
-- que o front-end mostra enquanto trocamos os arrays fixos por
-- consultas reais (SELECT) nesta base.
-- ============================================================

USE modasys;

-- usuário padrão (senha ainda não é validada por lógica nenhuma;
-- troque este hash por um gerado com password_hash() em PHP antes
-- de ligar o login de verdade)
INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES
('Admin', 'admin@modasys.com.br', '$2y$10$SUBSTITUA.ESTE.HASH.POR.UM.REAL.COM.PASSWORD_HASH', 'admin');

INSERT INTO categorias (nome) VALUES
('Vestidos'), ('Calças'), ('Camisetas'), ('Casacos'), ('Acessórios');

INSERT INTO fornecedores (razao_social, cnpj, inscricao_estadual, telefone, email, endereco) VALUES
('Têxtil Sul S.A.',            '12.345.678/0001-90', '110.222.333.444', '(11) 3333-4444', 'contato@textilsul.com.br', 'Rua das Malhas, 123 - São Paulo/SP'),
('Jeans & Cia Distribuidora',  '98.765.432/0001-10', '220.333.444.555', '(47) 3222-1111', 'vendas@jeanscia.com.br',   'Av. Industrial, 450 - Blumenau/SC'),
('Couro Fino Importações',     '45.678.901/0001-55', '330.444.555.666', '(51) 3444-5555', 'import@courofino.com',     'Rodovia BR-116, Km 12 - Novo Hamburgo/RS');

INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES
('Mariana Oliveira', '111.222.333-44', '(11) 98888-7777', 'mariana.oliveira@email.com', 'Av. Central, 45, Apto 302 - Centro'),
('Carlos Mendes',     '222.333.444-55', '(11) 97777-6666', 'carlos.mendes@email.com',    'Rua das Flores, 128 - Bairro Alto'),
('Ana Paula Silva',   '333.444.555-66', '(11) 96666-5555', 'anapaula.silva@email.com',   'Praça da Liberdade, 10 - Bela Vista');

-- categorias: 1 Vestidos, 2 Calças, 3 Camisetas, 4 Casacos, 5 Acessórios
-- fornecedores: 1 Têxtil Sul, 2 Jeans & Cia, 3 Couro Fino
INSERT INTO produtos (codigo_interno, nome, descricao, categoria_id, fornecedor_id, preco_custo, preco_venda, estoque_atual) VALUES
('PROD-001', 'Camiseta Básica de Algodão', '100% algodão, gola careca', 3, 1, 24.90,  49.90,  50),
('PROD-002', 'Calça Jeans Skinny',         'Jeans com elastano',        2, 2, 64.95,  129.90, 15),
('PROD-003', 'Jaqueta de Couro PU',        'Jaqueta preta com zíper',   4, 3, 120.00, 249.90, 5),
('PROD-004', 'Vestido Floral Verão',       'Estampa floral, tecido leve', 1, 1, 70.00, 159.90, 20),
('PROD-005', 'Cinto Fino Couro',           'Fivela metálica',           5, 3, 20.00,  49.95,  30),
('PROD-006', 'Camisa Social Azul',         'Manga longa, slim fit',     3, 1, 40.00,  89.90,  10),
('PROD-007', 'Saia Plissada',              'Plissado fino, cintura alta', 1, 2, 45.00, 99.90, 8);

-- entradas: cabeçalho da NF + itens (produtos: 1 Camiseta, 2 Calça, 3 Jaqueta)
INSERT INTO entradas (fornecedor_id, numero_nf, data_entrada, valor_produtos, valor_total, status) VALUES
(1, '001.234.567',    '2026-03-16', 3500.00, 3500.00, 'Concluída'),
(2, NULL,             '2026-03-14', 1200.00, 1200.00, 'Concluída'),
(3, '009.876.543',    '2026-03-10', 2800.00, 2800.00, 'Pendente');

INSERT INTO entrada_itens (entrada_id, produto_id, quantidade, custo_unitario, valor_total) VALUES
(1, 1, 140, 25.00,  3500.00),
(2, 2, 30,  40.00,  1200.00),
(3, 3, 15,  186.67, 2800.05);

-- condicionais: peças enviadas para experimentação (clientes: 1 Mariana, 2 Carlos, 3 Ana Paula)
INSERT INTO condicionais (cliente_id, data_saida, data_conclusao, status) VALUES
(1, '2026-03-15', NULL,         'Pendente'),
(2, '2026-03-12', '2026-03-14', 'Aprovado'),
(3, '2026-03-10', '2026-03-11', 'Devolvido');

INSERT INTO condicional_itens (condicional_id, produto_id, quantidade) VALUES
(1, 4, 1), -- Vestido Floral Verão
(1, 5, 1), -- Cinto Fino Couro
(2, 6, 1), -- Camisa Social Azul
(3, 7, 1); -- Saia Plissada

-- vendas: histórico do PDV (cliente_id NULL = Cliente Balcão)
INSERT INTO vendas (cliente_id, data_venda, forma_pagamento, valor_subtotal, valor_total, status) VALUES
(1,    '2026-03-17 14:30:00', 'Cartão de Crédito', 259.80, 259.80, 'Concluída'),
(NULL, '2026-03-17 10:15:00', 'PIX',                49.90,  49.90, 'Concluída'),
(2,    '2026-03-16 16:45:00', 'Cartão de Débito',  379.80, 379.80, 'Concluída'),
(3,    '2026-03-15 11:00:00', 'PIX',               189.80, 189.80, 'Concluída');

INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_total) VALUES
(1, 4, 1, 159.90, 159.90), -- Vestido Floral Verão
(1, 5, 2, 49.95,  99.90),  -- Cinto Fino Couro
(2, 1, 1, 49.90,  49.90),  -- Camiseta Básica
(3, 2, 1, 129.90, 129.90), -- Calça Jeans Skinny
(3, 3, 1, 249.90, 249.90), -- Jaqueta de Couro PU
(4, 6, 1, 89.90,  89.90),  -- Camisa Social Azul
(4, 7, 1, 99.90,  99.90);  -- Saia Plissada
