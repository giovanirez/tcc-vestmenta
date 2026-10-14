-- ============================================================
-- Migração 001 — vendedores
--
-- Rodar UMA vez no banco que já existe (Supabase > SQL Editor > New
-- query > colar > Run). Quem cria o banco do zero com o
-- schema-supabase.sql já recebe isso tudo e não precisa rodar este.
-- Pode rodar de novo sem problema: tudo tem IF NOT EXISTS.
--
-- Vendedor é quem atendeu o cliente na loja — diferente de
-- vendas.usuario_id, que é o login usado no sistema (várias vendedoras
-- podem usar o mesmo login do caixa). É uma tabela, e não texto solto
-- em vendas, pelo mesmo motivo de categorias: "Ana" e "ana " não podem
-- virar duas vendedoras diferentes nos relatórios.
-- ============================================================

CREATE TABLE IF NOT EXISTS vendedores (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome       VARCHAR(80) NOT NULL,
    criado_em  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Único sem diferenciar maiúsculas/minúsculas. 
CREATE UNIQUE INDEX IF NOT EXISTS ux_vendedores_nome ON vendedores (lower(nome));

-- Opcional: vendas antigas (e quem não quiser informar) ficam NULL.
ALTER TABLE vendas ADD COLUMN IF NOT EXISTS vendedor_id BIGINT REFERENCES vendedores(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_vendas_vendedor ON vendas(vendedor_id);

-- O backend conecta como backend_role, que precisa enxergar a tabela nova.
GRANT ALL ON TABLE vendedores TO backend_role;
GRANT ALL ON SEQUENCE vendedores_id_seq TO backend_role;
