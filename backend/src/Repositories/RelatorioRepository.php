<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

// Consultas só de leitura pro dashboard e pra tela de relatórios.
//
// Todo período chega como [inicio, fim) — fim exclusivo — já com o fuso
// de Brasília (ex.: "2026-10-01 00:00:00-03:00"). data_venda é
// TIMESTAMPTZ, então comparar com o offset explícito garante que uma
// venda das 22h do dia 31 não caia no mês seguinte (o que aconteceria
// comparando em UTC).
//
// Receita por produto/categoria é "líquida": o desconto geral da venda
// é rateado proporcionalmente entre os itens (valor_total do item *
// total da venda / subtotal da venda). Assim a soma por categoria bate
// com o faturamento total, em vez de ficar maior que ele.
class RelatorioRepository
{
    private const RECEITA_ITEM = 'vi.valor_total * v.valor_total / NULLIF(v.valor_subtotal, 0)';

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    public function resumoVendas(string $inicio, string $fim): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT
                COUNT(*) FILTER (WHERE status = 'Concluída') AS qtd_vendas,
                COALESCE(SUM(valor_total) FILTER (WHERE status = 'Concluída'), 0) AS faturamento,
                COALESCE(SUM(valor_subtotal - valor_total) FILTER (WHERE status = 'Concluída'), 0) AS descontos_gerais,
                COUNT(*) FILTER (WHERE status = 'Cancelada') AS qtd_canceladas
             FROM vendas
             WHERE data_venda >= :inicio AND data_venda < :fim"
        );
        $consulta->execute(['inicio' => $inicio, 'fim' => $fim]);
        $resumo = $consulta->fetch();

        $itens = $this->pdo->prepare(
            "SELECT COALESCE(SUM(vi.quantidade), 0) AS pecas_vendidas,
                    COALESCE(SUM(vi.valor_desconto), 0) AS descontos_itens
             FROM venda_itens vi
             JOIN vendas v ON v.id = vi.venda_id
             WHERE v.status = 'Concluída' AND v.data_venda >= :inicio AND v.data_venda < :fim"
        );
        $itens->execute(['inicio' => $inicio, 'fim' => $fim]);
        $dadosItens = $itens->fetch();

        $qtd = (int) $resumo['qtd_vendas'];
        $faturamento = (float) $resumo['faturamento'];

        return [
            'qtd_vendas' => $qtd,
            'faturamento' => $faturamento,
            'ticket_medio' => $qtd ? round($faturamento / $qtd, 2) : 0,
            'pecas_vendidas' => (int) $dadosItens['pecas_vendidas'],
            'descontos' => round((float) $resumo['descontos_gerais'] + (float) $dadosItens['descontos_itens'], 2),
            'qtd_canceladas' => (int) $resumo['qtd_canceladas'],
        ];
    }

    public function vendasPorCategoria(string $inicio, string $fim): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT COALESCE(c.nome, 'Sem categoria') AS categoria,
                    ROUND(SUM(" . self::RECEITA_ITEM . "), 2) AS valor
             FROM venda_itens vi
             JOIN vendas v ON v.id = vi.venda_id
             JOIN produtos p ON p.id = vi.produto_id
             LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE v.status = 'Concluída' AND v.data_venda >= :inicio AND v.data_venda < :fim
             GROUP BY 1
             ORDER BY 2 DESC NULLS LAST"
        );
        $consulta->execute(['inicio' => $inicio, 'fim' => $fim]);
        return $this->comPercentual($consulta->fetchAll(), 'valor');
    }

    public function vendasPorPagamento(string $inicio, string $fim): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT forma_pagamento, COUNT(*) AS qtd_vendas, SUM(valor_total) AS valor
             FROM vendas
             WHERE status = 'Concluída' AND data_venda >= :inicio AND data_venda < :fim
             GROUP BY forma_pagamento
             ORDER BY valor DESC"
        );
        $consulta->execute(['inicio' => $inicio, 'fim' => $fim]);
        return $this->comPercentual($consulta->fetchAll(), 'valor');
    }

    public function topProdutos(string $inicio, string $fim, int $limite = 10): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT p.id, p.codigo_interno, p.nome,
                    SUM(vi.quantidade) AS qtd,
                    ROUND(SUM(" . self::RECEITA_ITEM . "), 2) AS receita
             FROM venda_itens vi
             JOIN vendas v ON v.id = vi.venda_id
             JOIN produtos p ON p.id = vi.produto_id
             WHERE v.status = 'Concluída' AND v.data_venda >= :inicio AND v.data_venda < :fim
             GROUP BY p.id
             ORDER BY receita DESC NULLS LAST, qtd DESC
             LIMIT :limite"
        );
        $consulta->bindValue('inicio', $inicio);
        $consulta->bindValue('fim', $fim);
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    // Só clientes identificados — venda de Cliente Balcão não tem pra
    // quem atribuir. fiado_em_aberto é o saldo devedor ATUAL (não do
    // período), que é o que interessa ao olhar o histórico do cliente.
    public function topClientes(string $inicio, string $fim, int $limite = 10): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT c.id, c.nome, c.telefone,
                    COUNT(v.id) AS qtd_compras,
                    SUM(v.valor_total) AS total,
                    MAX(v.data_venda) AS ultima_compra,
                    (SELECT COALESCE(SUM(vp.valor), 0)
                     FROM venda_parcelas vp JOIN vendas v2 ON v2.id = vp.venda_id
                     WHERE v2.cliente_id = c.id AND v2.status <> 'Cancelada' AND vp.status = 'Pendente') AS fiado_em_aberto
             FROM vendas v
             JOIN clientes c ON c.id = v.cliente_id
             WHERE v.status = 'Concluída' AND v.data_venda >= :inicio AND v.data_venda < :fim
             GROUP BY c.id
             ORDER BY total DESC
             LIMIT :limite"
        );
        $consulta->bindValue('inicio', $inicio);
        $consulta->bindValue('fim', $fim);
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function contarCondicionaisAbertas(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'Pendente'")->fetchColumn();
    }

    public function produtosComEstoqueBaixo(int $limite): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, codigo_interno, nome, estoque_atual FROM produtos
             WHERE ativo AND estoque_atual <= :limite
             ORDER BY estoque_atual, nome'
        );
        $consulta->execute(['limite' => $limite]);
        return $consulta->fetchAll();
    }

    public function contarClientesNovos(string $inicio, string $fim): int
    {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM clientes WHERE criado_em >= :inicio AND criado_em < :fim');
        $consulta->execute(['inicio' => $inicio, 'fim' => $fim]);
        return (int) $consulta->fetchColumn();
    }

    public function ultimasVendas(int $limite): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT v.id, v.data_venda, v.valor_total, v.status, v.forma_pagamento, c.nome AS cliente_nome
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             ORDER BY v.data_venda DESC, v.id DESC
             LIMIT :limite'
        );
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    private function comPercentual(array $linhas, string $campo): array
    {
        $total = array_sum(array_map(fn ($linha) => (float) $linha[$campo], $linhas));
        foreach ($linhas as &$linha) {
            $linha['percentual'] = $total > 0 ? round((float) $linha[$campo] / $total * 100, 1) : 0;
        }
        return $linhas;
    }
}
