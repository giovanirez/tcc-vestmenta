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

INSERT INTO fornecedores (razao_social, cnpj, inscricao_estadual, telefone, email, cep, logradouro, numero, bairro, cidade, uf) VALUES
('Têxtil Sul S.A.',            '12.345.678/0001-90', '110.222.333.444', '(11) 3333-4444', 'contato@textilsul.com.br', '01310-100', 'Rua das Malhas',    '123', 'Centro',        'São Paulo',       'SP'),
('Jeans & Cia Distribuidora',  '98.765.432/0001-10', '220.333.444.555', '(47) 3222-1111', 'vendas@jeanscia.com.br',   '89010-500', 'Av. Industrial',    '450', 'Velha',         'Blumenau',        'SC'),
('Couro Fino Importações',     '45.678.901/0001-55', '330.444.555.666', '(51) 3444-5555', 'import@courofino.com',     '93510-250', 'Rodovia BR-116',    'Km 12', 'Distrito Industrial', 'Novo Hamburgo', 'RS');

INSERT INTO clientes (nome, telefone, cpf, email, cep, logradouro, numero, complemento, bairro, cidade, uf, limite_credito) VALUES
('Mariana Oliveira', '(11) 98888-7777', '111.222.333-44', 'mariana.oliveira@email.com', '01310-100', 'Av. Central',        '45', 'Apto 302', 'Centro',     'São Paulo', 'SP', 500.00),
('Carlos Mendes',     '(11) 97777-6666', '222.333.444-55', 'carlos.mendes@email.com',    '01310-200', 'Rua das Flores',     '128', NULL,       'Bairro Alto','São Paulo', 'SP', 800.00),
('Ana Paula Silva',   '(11) 96666-5555', '333.444.555-66', 'anapaula.silva@email.com',   '01310-300', 'Praça da Liberdade', '10',  NULL,       'Bela Vista', 'São Paulo', 'SP', NULL);

-- categorias: 1 Vestidos, 2 Calças, 3 Camisetas, 4 Casacos, 5 Acessórios
-- fornecedores: 1 Têxtil Sul, 2 Jeans & Cia, 3 Couro Fino
--
-- estoque_atual nasce em 0 pra todo mundo: o saldo real vem só dos
-- documentos inseridos mais abaixo, via trigger. Isso prova que o
-- mecanismo funciona de verdade (ver README.md).
INSERT INTO produtos (codigo_interno, nome, descricao, categoria_id, fornecedor_id, preco_custo, preco_venda, estoque_atual) VALUES
('PROD-001', 'Camiseta Básica de Algodão', '100% algodão, gola careca', 3, 1, 24.90,  49.90,  0),
('PROD-002', 'Calça Jeans Skinny',         'Jeans com elastano',        2, 2, 64.95,  129.90, 0),
('PROD-003', 'Jaqueta de Couro PU',        'Jaqueta preta com zíper',   4, 3, 120.00, 249.90, 0),
('PROD-004', 'Vestido Floral Verão',       'Estampa floral, tecido leve', 1, 1, 70.00, 159.90, 0),
('PROD-005', 'Cinto Fino Couro',           'Fivela metálica',           5, 3, 20.00,  49.95,  0),
('PROD-006', 'Camisa Social Azul',         'Manga longa, slim fit',     3, 1, 40.00,  89.90,  0),
('PROD-007', 'Saia Plissada',              'Plissado fino, cintura alta', 1, 2, 45.00, 99.90, 0);

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
-- venda 1: paga em cartão de crédito parcelado em 3x (parcelas_cartao é só informativo)
-- venda 3: tem desconto por item (Calça Jeans) + desconto geral por cima
INSERT INTO vendas (cliente_id, data_venda, forma_pagamento, parcelas_cartao, valor_subtotal, valor_desconto, valor_total, status) VALUES
(1,    '2026-03-17 14:30:00', 'Cartão de Crédito', 3,    259.80, 0,     259.80, 'Concluída'),
(NULL, '2026-03-17 10:15:00', 'PIX',                NULL, 49.90,  0,     49.90,  'Concluída'),
(2,    '2026-03-16 16:45:00', 'Cartão de Débito',  NULL, 366.81, 16.81, 350.00, 'Concluída'),
(3,    '2026-03-15 11:00:00', 'PIX',               NULL, 189.80, 0,     189.80, 'Concluída');

INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_desconto, valor_total) VALUES
(1, 4, 1, 159.90, 0,     159.90), -- Vestido Floral Verão
(1, 5, 2, 49.95,  0,     99.90),  -- Cinto Fino Couro
(2, 1, 1, 49.90,  0,     49.90),  -- Camiseta Básica
(3, 2, 1, 129.90, 12.99, 116.91), -- Calça Jeans Skinny, 10% de desconto no item
(3, 3, 1, 249.90, 0,     249.90), -- Jaqueta de Couro PU
(4, 6, 1, 89.90,  0,     89.90),  -- Camisa Social Azul
(4, 7, 1, 99.90,  0,     99.90);  -- Saia Plissada

-- venda fiada (Carlos Mendes, cliente_id 2), parcelada em 3x
INSERT INTO vendas (cliente_id, data_venda, forma_pagamento, valor_subtotal, valor_total, status) VALUES
(2, '2026-03-16 17:20:00', 'Fiado', 299.40, 299.40, 'Concluída');

INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_total) VALUES
(5, 1, 6, 49.90, 299.40); -- 6x Camiseta Básica de Algodão

INSERT INTO venda_parcelas (venda_id, numero_parcela, valor, data_vencimento, data_pagamento, status) VALUES
(5, 1, 99.80, '2026-04-16', '2026-04-15', 'Paga'),
(5, 2, 99.80, '2026-05-16', NULL,         'Pendente'),
(5, 3, 99.80, '2026-06-16', NULL,         'Pendente');

-- custos fixos: despesas recorrentes da loja
INSERT INTO custos_fixos (nome, categoria, valor, dia_vencimento, observacao) VALUES
('Aluguel da Loja',        'Ocupação',  2800.00, 5,  NULL),
('Energia Elétrica',       'Utilidades', 420.00, 10, NULL),
('Internet e Telefone',    'Utilidades', 180.00, 10, NULL),
('Salário - Vendedora',    'Pessoal',   1800.00, 5,  'Carga horária integral'),
('Contador',               'Serviços',  350.00,  15, NULL);

-- ============================================================
-- documentos / documento_itens: reconstrói o estoque de cada
-- produto a partir do zero, só com movimentos. Cada INSERT em
-- documento_itens dispara a trigger que soma/subtrai
-- produtos.estoque_atual — nada aqui grava o saldo diretamente.
--
-- Resultado esperado ao final (conferir com SELECT):
--   PROD-001: 140 - 1 - 6           = 133
--   PROD-002: 30 - 1                = 29
--   PROD-003: 15 - 1                = 14
--   PROD-004: 20 - 1 - 1            = 18
--   PROD-005: 30 - 1 - 2            = 27
--   PROD-006: 10 - 1 - 1            = 8
--   PROD-007: 8 - 1 + 1 - 1         = 7
-- ============================================================

-- compras (origem = Compra, referencia_id = entradas.id)
INSERT INTO documentos (tipo, origem, referencia_id, data) VALUES
('Entrada', 'Compra', 1, '2026-03-16'), -- doc 1: NF Têxtil Sul
('Entrada', 'Compra', 2, '2026-03-14'), -- doc 2: NF Jeans & Cia
('Entrada', 'Compra', 3, '2026-03-10'); -- doc 3: NF Couro Fino

INSERT INTO documento_itens (documento_id, produto_id, quantidade) VALUES
(1, 1, 140), -- Camiseta Básica
(2, 2, 30),  -- Calça Jeans Skinny
(3, 3, 15);  -- Jaqueta de Couro PU

-- saldo inicial de implantação: produtos que já tinham estoque físico
-- antes do sistema existir, sem nenhuma NF de compra registrada
INSERT INTO documentos (tipo, origem, referencia_id, data, observacao) VALUES
('Entrada', 'Ajuste de Inventário', NULL, '2026-01-01', 'Saldo inicial de implantação do sistema');

INSERT INTO documento_itens (documento_id, produto_id, quantidade) VALUES
(4, 4, 20), -- Vestido Floral Verão
(4, 5, 30), -- Cinto Fino Couro
(4, 6, 10), -- Camisa Social Azul
(4, 7, 8);  -- Saia Plissada

-- condicionais (origem = Condicional, referencia_id = condicionais.id)
-- saída de cada peça quando ela deixa a loja...
INSERT INTO documentos (tipo, origem, referencia_id, data) VALUES
('Saída', 'Condicional', 1, '2026-03-15'), -- doc 5: condicional 1 (Mariana, ainda Pendente)
('Saída', 'Condicional', 2, '2026-03-12'), -- doc 6: condicional 2 (Carlos, Aprovado = ficou com o cliente)
('Saída', 'Condicional', 3, '2026-03-10'); -- doc 7: condicional 3 (Ana Paula, saída)

INSERT INTO documento_itens (documento_id, produto_id, quantidade) VALUES
(5, 4, 1), -- Vestido Floral Verão
(5, 5, 1), -- Cinto Fino Couro
(6, 6, 1), -- Camisa Social Azul
(7, 7, 1); -- Saia Plissada

-- ...e volta pro estoque quando a condicional 3 foi devolvida
INSERT INTO documentos (tipo, origem, referencia_id, data, observacao) VALUES
('Entrada', 'Condicional', 3, '2026-03-11', 'Devolução da condicional #3');

INSERT INTO documento_itens (documento_id, produto_id, quantidade) VALUES
(8, 7, 1); -- Saia Plissada volta ao estoque

-- vendas (origem = Venda, referencia_id = vendas.id)
INSERT INTO documentos (tipo, origem, referencia_id, data) VALUES
('Saída', 'Venda', 1, '2026-03-17'), -- doc 9: venda 1 (Mariana)
('Saída', 'Venda', 2, '2026-03-17'), -- doc 10: venda 2 (Cliente Balcão)
('Saída', 'Venda', 3, '2026-03-16'), -- doc 11: venda 3 (Carlos, débito)
('Saída', 'Venda', 4, '2026-03-16'), -- doc 12: venda 4 (Ana Paula)
('Saída', 'Venda', 5, '2026-03-16'); -- doc 13: venda 5 (Carlos, fiado)

INSERT INTO documento_itens (documento_id, produto_id, quantidade) VALUES
(9,  4, 1),  -- venda 1: Vestido Floral Verão
(9,  5, 2),  -- venda 1: Cinto Fino Couro
(10, 1, 1),  -- venda 2: Camiseta Básica
(11, 2, 1),  -- venda 3: Calça Jeans Skinny
(11, 3, 1),  -- venda 3: Jaqueta de Couro PU
(12, 6, 1),  -- venda 4: Camisa Social Azul
(12, 7, 1),  -- venda 4: Saia Plissada
(13, 1, 6);  -- venda 5 (fiado): Camiseta Básica
