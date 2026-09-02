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
    cep                 VARCHAR(9),
    logradouro          VARCHAR(150),
    numero              VARCHAR(10),
    complemento         VARCHAR(100),
    bairro              VARCHAR(100),
    cidade              VARCHAR(100),
    uf                  CHAR(2),
    ativo               TINYINT(1) NOT NULL DEFAULT 1,
    criado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- clientes (clientes.php)
-- cpf é opcional/único: fica NULL para vendas de "Cliente Balcão".
-- Únicos campos obrigatórios são nome e telefone — o resto (CPF,
-- e-mail, endereço) é preenchido quando o cliente informa.
-- ------------------------------------------------------------
CREATE TABLE clientes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    telefone        VARCHAR(20) NOT NULL,
    cpf             VARCHAR(14) UNIQUE,
    email           VARCHAR(150),
    cep             VARCHAR(9),
    logradouro      VARCHAR(150),
    numero          VARCHAR(10),
    complemento     VARCHAR(100),
    bairro          VARCHAR(100),
    cidade          VARCHAR(100),
    uf              CHAR(2),
    limite_credito  DECIMAL(10,2) COMMENT 'Limite para venda fiado. NULL = sem fiado liberado para este cliente.',
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
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
-- lento com histórico grande). Ele é atualizado por uma trigger
-- na tabela documento_itens (ver o final deste arquivo) — nunca
-- deve ser alterado direto por UPDATE da aplicação.
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
-- Só pode ser NULL quando forma_pagamento != 'Fiado': não dá pra
-- vender fiado pra alguém não identificado, porque não tem pra
-- quem cobrar depois. Essa regra é validada na aplicação, não dá
-- pra expressar como constraint simples de coluna no MySQL.
--
-- condicional_id aceita NULL, e só é preenchido quando a venda
-- nasce de um "Finalizar Venda" em condicionais.php.
-- ------------------------------------------------------------
CREATE TABLE vendas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id       INT UNSIGNED,
    condicional_id   INT UNSIGNED,
    usuario_id       INT UNSIGNED,
    data_venda       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento  ENUM('PIX', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro', 'Fiado') NOT NULL,
    parcelas_cartao  TINYINT UNSIGNED COMMENT 'Em quantas vezes o cliente parcelou no cartão. Só informativo — quem financia é a operadora, não a loja. NULL quando a forma de pagamento não é cartão.',
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
--
-- valor_desconto guarda o valor em reais do desconto daquele item
-- (não o percentual). No PDV, quem digita é um "% Desc." por linha,
-- mas isso é só uma calculadora na tela — o mesmo padrão já usado no
-- % de lucro da tela de Entradas. valor_total já sai com o desconto
-- aplicado: quantidade * valor_unitario - valor_desconto.
CREATE TABLE venda_itens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venda_id        INT UNSIGNED NOT NULL,
    produto_id      INT UNSIGNED NOT NULL,
    quantidade      INT NOT NULL,
    valor_unitario  DECIMAL(10,2) NOT NULL,
    valor_desconto  DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total     DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id)   REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- venda_parcelas: contas a receber de vendas "Fiado".
-- Uma venda à vista nunca tem linhas aqui; só vendas com
-- forma_pagamento = 'Fiado' geram 1 ou mais parcelas.
--
-- Não existe status 'Atrasada' guardado na coluna — atraso é
-- calculado na consulta (status = 'Pendente' AND data_vencimento
-- < CURDATE()), porque senão seria preciso um job rodando todo dia
-- só pra atualizar essa coluna. Calcular é mais simples e nunca
-- fica desatualizado.
-- ------------------------------------------------------------
CREATE TABLE venda_parcelas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venda_id         INT UNSIGNED NOT NULL,
    numero_parcela   TINYINT UNSIGNED NOT NULL,
    valor            DECIMAL(10,2) NOT NULL,
    data_vencimento  DATE NOT NULL,
    data_pagamento   DATE,
    status           ENUM('Pendente', 'Paga') NOT NULL DEFAULT 'Pendente',
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_parcelas_vencimento ON venda_parcelas(data_vencimento);

-- ------------------------------------------------------------
-- custos_fixos: despesas recorrentes da loja (aluguel, luz,
-- internet, salários...) — custos-fixos.php
--
-- Não tem valor_pago/data_pagamento por mês aqui: essa tabela é só
-- o CADASTRO do custo recorrente. Um próximo passo natural seria
-- uma tabela custos_fixos_pagamentos (mesmo padrão de venda_parcelas)
-- pra controlar mês a mês o que já foi pago — não existe ainda
-- porque não foi pedido, mas a estrutura já deixa espaço pra isso.
-- ------------------------------------------------------------
CREATE TABLE custos_fixos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(120) NOT NULL,
    categoria       VARCHAR(60) COMMENT 'Ex.: Ocupação, Utilidades, Pessoal, Serviços',
    valor           DECIMAL(10,2) NOT NULL,
    dia_vencimento  TINYINT UNSIGNED NOT NULL COMMENT 'Dia do mês em que o custo vence (1 a 31)',
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    observacao      VARCHAR(255),
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- documentos / documento_itens: o "kardex" — ledger único de todo
-- movimento de estoque, seja qual for a origem. inventario.php,
-- entradas.php, vendas.php e condicionais.php todos escrevem aqui.
--
-- Por que isso existe além de entrada_itens/venda_itens/condicional_itens:
-- aquelas tabelas guardam o dado DE NEGÓCIO de cada operação (preço,
-- custo, desconto, forma de pagamento...). documento_itens guarda só
-- "produto X moveu Y unidades" — é o único lugar que decide se o
-- estoque sobe ou desce, não importa se o motivo foi uma compra, uma
-- venda, uma condicional ou um ajuste de inventário sem nenhuma
-- tabela de origem. Isso significa: pra auditar TODO movimento de
-- estoque já feito, basta consultar essa tabela — não precisa cruzar
-- entradas + vendas + condicionais.
--
-- referencia_id aponta pro id da entrada/venda/condicional de origem,
-- quando existir. Não dá pra ser uma FOREIGN KEY de verdade porque a
-- tabela de destino muda conforme a coluna `origem` (o MySQL não tem
-- FK polimórfica) — a aplicação decide qual tabela consultar. Fica
-- NULL em ajustes manuais de inventário/perda, que não têm origem
-- em nenhuma outra tabela.
-- ------------------------------------------------------------
CREATE TABLE documentos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo           ENUM('Entrada', 'Saída') NOT NULL,
    origem         ENUM('Compra', 'Venda', 'Condicional', 'Ajuste de Inventário', 'Perda/Avaria') NOT NULL,
    referencia_id  INT UNSIGNED COMMENT 'id de entradas/vendas/condicionais conforme a coluna origem. NULL quando não há tabela de origem.',
    data           DATE NOT NULL,
    observacao     VARCHAR(255),
    criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_documentos_origem ON documentos(origem, referencia_id);

CREATE TABLE documento_itens (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    documento_id  INT UNSIGNED NOT NULL,
    produto_id    INT UNSIGNED NOT NULL,
    quantidade    INT UNSIGNED NOT NULL COMMENT 'Sempre positivo — o sinal (soma ou subtrai) vem do tipo do documento pai, não daqui.',
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id)   REFERENCES produtos(id)
) ENGINE=InnoDB;

-- A trigger: dispara a cada item de documento inserido, olha o tipo
-- do documento pai e ajusta produtos.estoque_atual pra mais (Entrada)
-- ou pra menos (Saída). É o único lugar do sistema que muda esse saldo.
DELIMITER $$

CREATE TRIGGER trg_documento_itens_atualiza_estoque
AFTER INSERT ON documento_itens
FOR EACH ROW
BEGIN
    DECLARE v_tipo VARCHAR(10);

    SELECT tipo INTO v_tipo FROM documentos WHERE id = NEW.documento_id;

    IF v_tipo = 'Entrada' THEN
        UPDATE produtos SET estoque_atual = estoque_atual + NEW.quantidade WHERE id = NEW.produto_id;
    ELSE
        UPDATE produtos SET estoque_atual = estoque_atual - NEW.quantidade WHERE id = NEW.produto_id;
    END IF;
END$$

DELIMITER ;
