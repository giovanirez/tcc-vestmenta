-- ============================================================
-- ModaSys — Estrutura do banco de dados
-- Sistema de gestão para loja de roupas (TCC)
--
-- Cada tabela corresponde a uma tela já existente no sistema:
--   fornecedores      -> fornecedores.php
--   clientes          -> clientes.php
--   categorias        -> combo "Categoria" em produtos.php
--   produtos          -> produtos.php
--   entradas / itens  -> entradas.php (Nota Fiscal de compra)
--   condicionais/itens-> condicionais.php
--   vendas / itens    -> vendas.php (PDV) e index.php (KPIs)
--   usuarios          -> login.php (autenticação, ainda não conectada)
--
-- Motor: InnoDB (suporta chaves estrangeiras e transações,
-- essencial para não deixar estoque inconsistente).
-- Charset: utf8mb4 (acentuação e emojis sem problema).
-- ============================================================

CREATE DATABASE IF NOT EXISTS modasys
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE modasys;

-- ------------------------------------------------------------
-- usuarios: quem acessa o sistema (login.php)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(120) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    senha_hash    VARCHAR(255) NOT NULL,
    papel         ENUM('admin', 'vendedor') NOT NULL DEFAULT 'vendedor',
    ativo         TINYINT(1) NOT NULL DEFAULT 1,
    criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- categorias: evita repetir "Vestidos", "Calças"... como texto solto
-- em cada produto (produtos.php usava um campo de texto livre)
-- ------------------------------------------------------------
CREATE TABLE categorias (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome  VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- fornecedores (fornecedores.php)
-- ------------------------------------------------------------
CREATE TABLE fornecedores (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razao_social        VARCHAR(150) NOT NULL,
    cnpj                VARCHAR(18) NOT NULL UNIQUE,
    inscricao_estadual  VARCHAR(20),
    telefone            VARCHAR(20),
    email               VARCHAR(150),
    endereco            VARCHAR(255),
    ativo               TINYINT(1) NOT NULL DEFAULT 1,
    criado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- clientes (clientes.php)
-- cpf é opcional/único: fica NULL para vendas de "Cliente Balcão"
-- ------------------------------------------------------------
CREATE TABLE clientes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    cpf        VARCHAR(14) UNIQUE,
    telefone   VARCHAR(20),
    email      VARCHAR(150),
    endereco   VARCHAR(255),
    criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- produtos (produtos.php)
--
-- fornecedor_id é o fornecedor "padrão" do produto (o que aparece
-- na listagem). O histórico real de quem entregou cada lote fica
-- registrado em entrada_itens/entradas — então, se um produto um
-- dia vier de mais de um fornecedor, a informação não se perde,
-- só a coluna aqui passa a mostrar apenas o fornecedor mais comum.
--
-- estoque_atual é um saldo em cache (não é recalculado a cada
-- consulta somando entradas/vendas/condicionais, o que ficaria
-- lento com histórico grande). Ele é atualizado sempre que uma
-- entrada, venda ou condicional muda a quantidade em loja.
-- ------------------------------------------------------------
CREATE TABLE produtos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_interno  VARCHAR(30) NOT NULL UNIQUE,
    nome            VARCHAR(150) NOT NULL,
    descricao       VARCHAR(255),
    categoria_id    INT UNSIGNED,
    fornecedor_id   INT UNSIGNED,
    preco_custo     DECIMAL(10,2) NOT NULL DEFAULT 0,
    preco_venda     DECIMAL(10,2) NOT NULL DEFAULT 0,
    estoque_atual   INT NOT NULL DEFAULT 0,
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id)  REFERENCES categorias(id)   ON DELETE SET NULL,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_produtos_nome ON produtos(nome);

-- ------------------------------------------------------------
-- entradas: cabeçalho da Nota Fiscal de compra (entradas.php)
-- Os campos aqui batem 1 para 1 com as seções do formulário
-- "Nova Entrada de Produtos" (Identificação, Fornecedor,
-- Transporte, Totais) — é justamente o que o futuro botão
-- "Importar XML" vai preencher automaticamente.
-- ------------------------------------------------------------
CREATE TABLE entradas (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id               INT UNSIGNED NOT NULL,
    numero_nf                   VARCHAR(20),
    serie                       VARCHAR(10),
    chave_acesso                CHAR(44),
    natureza_operacao           VARCHAR(100),
    data_emissao                DATE,
    data_entrada                DATE NOT NULL,
    modalidade_frete            TINYINT NOT NULL DEFAULT 9 COMMENT '0=Emitente 1=Destinatário 2=Terceiros 9=Sem frete',
    transportadora              VARCHAR(150),
    placa_veiculo               VARCHAR(10),
    valor_produtos              DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_frete                 DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_seguro                DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_outras_despesas       DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_desconto              DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_icms                  DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_ipi                   DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total                 DECIMAL(10,2) NOT NULL DEFAULT 0,
    informacoes_complementares  TEXT,
    status                      ENUM('Pendente', 'Concluída') NOT NULL DEFAULT 'Pendente',
    criado_em                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
) ENGINE=InnoDB;

CREATE INDEX idx_entradas_data ON entradas(data_entrada);

-- itens de cada entrada (1 entrada -> N produtos recebidos)
CREATE TABLE entrada_itens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entrada_id      INT UNSIGNED NOT NULL,
    produto_id      INT UNSIGNED NOT NULL,
    quantidade      INT NOT NULL,
    custo_unitario  DECIMAL(10,2) NOT NULL,
    valor_total     DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (entrada_id) REFERENCES entradas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- condicionais: peças que saem para o cliente experimentar em
-- casa antes de decidir a compra (condicionais.php)
-- ------------------------------------------------------------
CREATE TABLE condicionais (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    data_saida      DATE NOT NULL,
    data_conclusao  DATE,
    status          ENUM('Pendente', 'Aprovado', 'Devolvido') NOT NULL DEFAULT 'Pendente',
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;

CREATE TABLE condicional_itens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condicional_id  INT UNSIGNED NOT NULL,
    produto_id      INT UNSIGNED NOT NULL,
    quantidade      INT NOT NULL DEFAULT 1,
    FOREIGN KEY (condicional_id) REFERENCES condicionais(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id)     REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- vendas: histórico do PDV (vendas.php) e KPIs do dashboard
--
-- cliente_id aceita NULL -> representa o "Cliente Balcão" do PDV.
-- condicional_id aceita NULL, e só é preenchido quando a venda
-- nasce de um "Finalizar Venda" em condicionais.php.
-- ------------------------------------------------------------
CREATE TABLE vendas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id       INT UNSIGNED,
    condicional_id   INT UNSIGNED,
    usuario_id       INT UNSIGNED,
    data_venda       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento  ENUM('PIX', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro') NOT NULL,
    valor_subtotal   DECIMAL(10,2) NOT NULL,
    valor_desconto   DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total      DECIMAL(10,2) NOT NULL,
    status           ENUM('Concluída', 'Cancelada') NOT NULL DEFAULT 'Concluída',
    FOREIGN KEY (cliente_id)     REFERENCES clientes(id)     ON DELETE SET NULL,
    FOREIGN KEY (condicional_id) REFERENCES condicionais(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_vendas_data ON vendas(data_venda);

-- itens de cada venda. valor_unitario é o preço no momento da
-- venda (uma "fotografia" do preço) — não referencia produtos.preco_venda,
-- porque o preço do produto pode mudar depois e o histórico da venda
-- antiga não pode mudar junto.
CREATE TABLE venda_itens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venda_id        INT UNSIGNED NOT NULL,
    produto_id      INT UNSIGNED NOT NULL,
    quantidade      INT NOT NULL,
    valor_unitario  DECIMAL(10,2) NOT NULL,
    valor_total     DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id)   REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;
