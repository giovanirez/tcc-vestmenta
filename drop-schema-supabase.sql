-- ============================================================
-- ModaSys — Zera o banco no Supabase antes de rodar schema-supabase.sql
-- Cole isto inteiro no SQL Editor do Supabase e rode ANTES do schema
-- novo. É destrutivo: apaga tabelas, dados, a trigger/função e o
-- papel (role) de conexão. Sem volta.
-- ============================================================

-- Tabelas em ordem que respeita as dependências (as que são
-- referenciadas por outras vêm por último). CASCADE cobre qualquer
-- ordem errada e também derruba a trigger junto com sua tabela.
DROP TABLE IF EXISTS documento_itens CASCADE;
DROP TABLE IF EXISTS documentos CASCADE;
DROP TABLE IF EXISTS venda_parcelas CASCADE;
DROP TABLE IF EXISTS venda_itens CASCADE;
DROP TABLE IF EXISTS vendas CASCADE;
DROP TABLE IF EXISTS condicional_itens CASCADE;
DROP TABLE IF EXISTS condicionais CASCADE;
DROP TABLE IF EXISTS entrada_itens CASCADE;
DROP TABLE IF EXISTS entradas CASCADE;
DROP TABLE IF EXISTS custos_fixos CASCADE;
DROP TABLE IF EXISTS produtos CASCADE;
DROP TABLE IF EXISTS fornecedores CASCADE;
DROP TABLE IF EXISTS clientes CASCADE;
DROP TABLE IF EXISTS categorias CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;

DROP FUNCTION IF EXISTS fn_documento_itens_atualiza_estoque() CASCADE;

DROP ROLE IF EXISTS backend_role;

-- Se a versão anterior (com 4 schemas de microsserviço) já tiver
-- sido rodada nesse projeto, limpa ela também.
DROP SCHEMA IF EXISTS auth_service CASCADE;
DROP SCHEMA IF EXISTS estoque_service CASCADE;
DROP SCHEMA IF EXISTS pedidos_service CASCADE;
DROP SCHEMA IF EXISTS financeiro_service CASCADE;
DROP ROLE IF EXISTS auth_role;
DROP ROLE IF EXISTS estoque_role;
DROP ROLE IF EXISTS pedidos_role;
DROP ROLE IF EXISTS financeiro_role;
