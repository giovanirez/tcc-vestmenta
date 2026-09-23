-- ============================================================
-- ModaSys (vestmenta-erp) — Estrutura do banco no Supabase
-- Sistema distribuído = backend (Cloud Run) e front-end (Cloud Run,
-- serviço à parte) rodando em lugares diferentes, falando com este
-- banco único via API. Um schema só ("public") — nada de separar
-- por microsserviço aqui.
--
-- Convertido de schema.sql (MySQL/MariaDB, usado no XAMPP local).
-- Supabase já cria e conecta você a um banco Postgres por projeto —
-- não existe CREATE DATABASE/USE aqui, é só colar isto no SQL Editor
-- do painel (Supabase > SQL Editor > New query > Run).
--
-- Principais diferenças em relação à versão MySQL:
--   - AUTO_INCREMENT           -> GENERATED ALWAYS AS IDENTITY
--   - ENUM(...)                -> TEXT + CHECK (col IN (...))
--   - TINYINT(1)                -> BOOLEAN
--   - TIMESTAMP/DATETIME        -> TIMESTAMPTZ (com fuso horário)
--   - INT UNSIGNED               -> BIGINT / INTEGER (Postgres não tem
--                                    UNSIGNED; onde importava impedir
--                                    negativo, virou CHECK)
--   - COMMENT '...' na coluna   -> COMMENT ON COLUMN, separado
--   - Trigger (DELIMITER/BEGIN) -> função PL/pgSQL + CREATE TRIGGER
--
-- Cada tabela corresponde a uma tela já existente no sistema:
--   usuarios          -> login.php (autenticação, ainda não conectada)
--   categorias        -> combo "Categoria" em produtos.php
--   fornecedores      -> fornecedores.php
--   produtos          -> produtos.php
--   entradas / itens  -> entradas.php (Nota Fiscal de compra)
--   clientes          -> clientes.php
--   condicionais/itens-> condicionais.php
--   vendas / itens    -> vendas.php (PDV) e dashboard.php (KPIs)
--   venda_parcelas    -> contas-a-receber.php (vendas "Fiado")
--   custos_fixos      -> custos-fixos.php
--   documentos/itens  -> inventario.php, e por baixo dos panos de
--                        entradas/vendas/condicionais
-- ============================================================

-- usuarios: quem acessa o sistema (login.php)
CREATE TABLE IF NOT EXISTS usuarios (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    papel       TEXT NOT NULL DEFAULT 'vendedor' CHECK (papel IN ('admin', 'vendedor')),
    ativo       BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- categorias: evita repetir "Vestidos", "Calças"... como texto solto
-- em cada produto (produtos.php usava um campo de texto livre)
CREATE TABLE IF NOT EXISTS categorias (
    id    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome  VARCHAR(60) NOT NULL UNIQUE
);

-- fornecedores (fornecedores.php)
CREATE TABLE IF NOT EXISTS fornecedores (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
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
    ativo               BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em           TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- clientes (clientes.php)
-- cpf é opcional/único: fica NULL para vendas de "Cliente Balcão".
-- Únicos campos obrigatórios são nome e telefone — o resto (CPF,
-- e-mail, endereço) é preenchido quando o cliente informa.
CREATE TABLE IF NOT EXISTS clientes (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
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
    limite_credito  DECIMAL(10,2),
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT now()
);

COMMENT ON COLUMN clientes.limite_credito IS 'Limite para venda fiado. NULL = sem fiado liberado para este cliente.';

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
CREATE TABLE IF NOT EXISTS produtos (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_interno  VARCHAR(30) NOT NULL UNIQUE,
    nome            VARCHAR(150) NOT NULL,
    descricao       VARCHAR(255),
    categoria_id    BIGINT REFERENCES categorias(id) ON DELETE SET NULL,
    fornecedor_id   BIGINT REFERENCES fornecedores(id) ON DELETE SET NULL,
    preco_custo     DECIMAL(10,2) NOT NULL DEFAULT 0,
    preco_venda     DECIMAL(10,2) NOT NULL DEFAULT 0,
    estoque_atual   INTEGER NOT NULL DEFAULT 0,
    ativo           BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_produtos_nome ON produtos(nome);

-- entradas: cabeçalho da Nota Fiscal de compra (entradas.php)
-- Os campos aqui batem 1 para 1 com as seções do formulário
-- "Nova Entrada de Produtos" (Identificação, Fornecedor,
-- Transporte, Totais) — é justamente o que o futuro botão
-- "Importar XML" vai preencher automaticamente.
CREATE TABLE IF NOT EXISTS entradas (
    id                          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fornecedor_id               BIGINT NOT NULL REFERENCES fornecedores(id),
    numero_nf                   VARCHAR(20),
    serie                       VARCHAR(10),
    chave_acesso                CHAR(44),
    natureza_operacao           VARCHAR(100),
    data_emissao                DATE,
    data_entrada                DATE NOT NULL,
    modalidade_frete            SMALLINT NOT NULL DEFAULT 9,
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
    status                      TEXT NOT NULL DEFAULT 'Pendente' CHECK (status IN ('Pendente', 'Concluída')),
    criado_em                   TIMESTAMPTZ NOT NULL DEFAULT now()
);

COMMENT ON COLUMN entradas.modalidade_frete IS '0=Emitente 1=Destinatário 2=Terceiros 9=Sem frete';

CREATE INDEX IF NOT EXISTS idx_entradas_data ON entradas(data_entrada);

-- itens de cada entrada (1 entrada -> N produtos recebidos)
CREATE TABLE IF NOT EXISTS entrada_itens (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    entrada_id      BIGINT NOT NULL REFERENCES entradas(id) ON DELETE CASCADE,
    produto_id      BIGINT NOT NULL REFERENCES produtos(id),
    quantidade      INTEGER NOT NULL,
    custo_unitario  DECIMAL(10,2) NOT NULL,
    valor_total     DECIMAL(10,2) NOT NULL
);

-- condicionais: peças que saem para o cliente experimentar em
-- casa antes de decidir a compra (condicionais.php)
CREATE TABLE IF NOT EXISTS condicionais (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id),
    data_saida      DATE NOT NULL,
    data_conclusao  DATE,
    status          TEXT NOT NULL DEFAULT 'Pendente' CHECK (status IN ('Pendente', 'Aprovado', 'Devolvido')),
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS condicional_itens (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    condicional_id  BIGINT NOT NULL REFERENCES condicionais(id) ON DELETE CASCADE,
    produto_id      BIGINT NOT NULL REFERENCES produtos(id),
    quantidade      INTEGER NOT NULL DEFAULT 1
);

-- vendas: histórico do PDV (vendas.php) e KPIs do dashboard
--
-- cliente_id aceita NULL -> representa o "Cliente Balcão" do PDV.
-- Só pode ser NULL quando forma_pagamento != 'Fiado': não dá pra
-- vender fiado pra alguém não identificado, porque não tem pra
-- quem cobrar depois. Essa regra é validada na aplicação, não dá
-- pra expressar como constraint simples de coluna.
--
-- condicional_id aceita NULL, e só é preenchido quando a venda
-- nasce de um "Finalizar Venda" em condicionais.php.
CREATE TABLE IF NOT EXISTS vendas (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cliente_id       BIGINT REFERENCES clientes(id) ON DELETE SET NULL,
    condicional_id   BIGINT REFERENCES condicionais(id) ON DELETE SET NULL,
    usuario_id       BIGINT REFERENCES usuarios(id) ON DELETE SET NULL,
    data_venda       TIMESTAMPTZ NOT NULL DEFAULT now(),
    forma_pagamento  TEXT NOT NULL CHECK (forma_pagamento IN ('PIX', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro', 'Fiado')),
    parcelas_cartao  SMALLINT,
    valor_subtotal   DECIMAL(10,2) NOT NULL,
    valor_desconto   DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total      DECIMAL(10,2) NOT NULL,
    status           TEXT NOT NULL DEFAULT 'Concluída' CHECK (status IN ('Concluída', 'Cancelada'))
);

COMMENT ON COLUMN vendas.parcelas_cartao IS 'Em quantas vezes o cliente parcelou no cartão. Só informativo — quem financia é a operadora, não a loja. NULL quando a forma de pagamento não é cartão.';

CREATE INDEX IF NOT EXISTS idx_vendas_data ON vendas(data_venda);

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
CREATE TABLE IF NOT EXISTS venda_itens (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    venda_id        BIGINT NOT NULL REFERENCES vendas(id) ON DELETE CASCADE,
    produto_id      BIGINT NOT NULL REFERENCES produtos(id),
    quantidade      INTEGER NOT NULL,
    valor_unitario  DECIMAL(10,2) NOT NULL,
    valor_desconto  DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total     DECIMAL(10,2) NOT NULL
);

-- venda_parcelas: contas a receber de vendas "Fiado".
-- Uma venda à vista nunca tem linhas aqui; só vendas com
-- forma_pagamento = 'Fiado' geram 1 ou mais parcelas.
--
-- Não existe status 'Atrasada' guardado na coluna — atraso é
-- calculado na consulta (status = 'Pendente' AND data_vencimento
-- < CURRENT_DATE), porque senão seria preciso um job rodando todo
-- dia só pra atualizar essa coluna. Calcular é mais simples e nunca
-- fica desatualizado.
CREATE TABLE IF NOT EXISTS venda_parcelas (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    venda_id         BIGINT NOT NULL REFERENCES vendas(id) ON DELETE CASCADE,
    numero_parcela   SMALLINT NOT NULL,
    valor            DECIMAL(10,2) NOT NULL,
    data_vencimento  DATE NOT NULL,
    data_pagamento   DATE,
    status           TEXT NOT NULL DEFAULT 'Pendente' CHECK (status IN ('Pendente', 'Paga'))
);

CREATE INDEX IF NOT EXISTS idx_parcelas_vencimento ON venda_parcelas(data_vencimento);

-- custos_fixos: despesas recorrentes da loja (aluguel, luz,
-- internet, salários...) — custos-fixos.php
--
-- Não tem valor_pago/data_pagamento por mês aqui: essa tabela é só
-- o CADASTRO do custo recorrente. Um próximo passo natural seria
-- uma tabela custos_fixos_pagamentos (mesmo padrão de venda_parcelas)
-- pra controlar mês a mês o que já foi pago — não existe ainda
-- porque não foi pedido, mas a estrutura já deixa espaço pra isso.
CREATE TABLE IF NOT EXISTS custos_fixos (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome            VARCHAR(120) NOT NULL,
    categoria       VARCHAR(60),
    valor           DECIMAL(10,2) NOT NULL,
    dia_vencimento  SMALLINT NOT NULL CHECK (dia_vencimento BETWEEN 1 AND 31),
    ativo           BOOLEAN NOT NULL DEFAULT TRUE,
    observacao      VARCHAR(255),
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT now()
);

COMMENT ON COLUMN custos_fixos.categoria IS 'Ex.: Ocupação, Utilidades, Pessoal, Serviços';

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
-- tabela de destino muda conforme a coluna `origem` (não existe FK
-- polimórfica em SQL relacional puro) — a aplicação decide qual
-- tabela consultar. Fica NULL em ajustes manuais de inventário/perda,
-- que não têm origem em nenhuma outra tabela.
CREATE TABLE IF NOT EXISTS documentos (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo           TEXT NOT NULL CHECK (tipo IN ('Entrada', 'Saída')),
    origem         TEXT NOT NULL CHECK (origem IN ('Compra', 'Venda', 'Condicional', 'Ajuste de Inventário', 'Perda/Avaria')),
    referencia_id  BIGINT,
    data           DATE NOT NULL,
    observacao     VARCHAR(255),
    criado_em      TIMESTAMPTZ NOT NULL DEFAULT now()
);

COMMENT ON COLUMN documentos.referencia_id IS 'id de entradas/vendas/condicionais conforme a coluna origem. NULL quando não há tabela de origem.';

CREATE INDEX IF NOT EXISTS idx_documentos_origem ON documentos(origem, referencia_id);

CREATE TABLE IF NOT EXISTS documento_itens (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    documento_id  BIGINT NOT NULL REFERENCES documentos(id) ON DELETE CASCADE,
    produto_id    BIGINT NOT NULL REFERENCES produtos(id),
    quantidade    INTEGER NOT NULL CHECK (quantidade > 0)
);

COMMENT ON COLUMN documento_itens.quantidade IS 'Sempre positivo — o sinal (soma ou subtrai) vem do tipo do documento pai, não daqui.';

-- A trigger: dispara a cada item de documento inserido, olha o tipo
-- do documento pai e ajusta produtos.estoque_atual pra mais (Entrada)
-- ou pra menos (Saída). É o único lugar do sistema que muda esse saldo.
-- Em Postgres, trigger é sempre função + CREATE TRIGGER separados
-- (não existe o BEGIN...END inline do MySQL).
CREATE OR REPLACE FUNCTION fn_documento_itens_atualiza_estoque()
RETURNS TRIGGER AS $$
DECLARE
    v_tipo TEXT;
BEGIN
    SELECT tipo INTO v_tipo FROM documentos WHERE id = NEW.documento_id;

    IF v_tipo = 'Entrada' THEN
        UPDATE produtos SET estoque_atual = estoque_atual + NEW.quantidade WHERE id = NEW.produto_id;
    ELSE
        UPDATE produtos SET estoque_atual = estoque_atual - NEW.quantidade WHERE id = NEW.produto_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_documento_itens_atualiza_estoque ON documento_itens;

CREATE TRIGGER trg_documento_itens_atualiza_estoque
    AFTER INSERT ON documento_itens
    FOR EACH ROW
    EXECUTE FUNCTION fn_documento_itens_atualiza_estoque();

-- ================================================================
-- Papel (role) único para o backend se conectar
--
-- Com back-end e front-end em serviços separados (mas um banco só,
-- sem divisão por schema), não tem mais isolamento entre "serviços
-- de domínio" pra fazer — só faz sentido o backend não usar o
-- usuário "postgres" (superusuário) do projeto direto. Um papel só,
-- com acesso total ao schema public, resolve.
--
-- TROQUE a senha abaixo antes de rodar.
-- ================================================================

CREATE ROLE backend_role LOGIN PASSWORD 'TROQUE_ESTA_SENHA';

GRANT USAGE ON SCHEMA public TO backend_role;
GRANT ALL ON ALL TABLES IN SCHEMA public TO backend_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO backend_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO backend_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO backend_role;
