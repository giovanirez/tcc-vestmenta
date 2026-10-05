<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class VendaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // itens_resumo já vem montado ("2x Camiseta, 1x Cinto") pra coluna
    // "Itens" do histórico, sem a tela precisar buscar item por item.
    public function listarTodas(): array
    {
        return $this->pdo->query(
            "SELECT v.id, v.data_venda, v.forma_pagamento, v.parcelas_cartao, v.valor_total, v.status,
                    c.nome AS cliente_nome,
                    (SELECT string_agg(vi.quantidade || 'x ' || p.nome, ', ' ORDER BY vi.id)
                     FROM venda_itens vi JOIN produtos p ON p.id = vi.produto_id
                     WHERE vi.venda_id = v.id) AS itens_resumo
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             ORDER BY v.data_venda DESC, v.id DESC"
        )->fetchAll();
    }

    public function buscarPorId(int $id, bool $bloquear = false): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT v.*, c.nome AS cliente_nome, u.nome AS usuario_nome
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             LEFT JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.id = :id' . ($bloquear ? ' FOR UPDATE OF v' : '')
        );
        $consulta->execute(['id' => $id]);
        $venda = $consulta->fetch();
        if (!$venda) {
            return null;
        }

        $itens = $this->pdo->prepare(
            'SELECT vi.*, p.codigo_interno, p.nome AS produto_nome
             FROM venda_itens vi
             JOIN produtos p ON p.id = vi.produto_id
             WHERE vi.venda_id = :id
             ORDER BY vi.id'
        );
        $itens->execute(['id' => $id]);
        $venda['itens'] = $itens->fetchAll();

        $parcelas = $this->pdo->prepare(
            'SELECT * FROM venda_parcelas WHERE venda_id = :id ORDER BY numero_parcela'
        );
        $parcelas->execute(['id' => $id]);
        $venda['parcelas'] = $parcelas->fetchAll();

        return $venda;
    }

    public function criar(array $dados): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO vendas
                (cliente_id, condicional_id, usuario_id, forma_pagamento, parcelas_cartao,
                 valor_subtotal, valor_desconto, valor_total)
             VALUES
                (:cliente_id, :condicional_id, :usuario_id, :forma_pagamento, :parcelas_cartao,
                 :valor_subtotal, :valor_desconto, :valor_total)
             RETURNING id'
        );
        $consulta->execute($dados);
        return (int) $consulta->fetchColumn();
    }

    public function adicionarItem(int $vendaId, array $item): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO venda_itens (venda_id, produto_id, quantidade, valor_unitario, valor_desconto, valor_total)
             VALUES (:venda_id, :produto_id, :quantidade, :valor_unitario, :valor_desconto, :valor_total)'
        );
        $consulta->execute(['venda_id' => $vendaId] + $item);
    }

    public function adicionarParcela(int $vendaId, int $numero, float $valor, string $vencimento): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO venda_parcelas (venda_id, numero_parcela, valor, data_vencimento)
             VALUES (:venda_id, :numero, :valor, :vencimento)'
        );
        $consulta->execute(['venda_id' => $vendaId, 'numero' => $numero, 'valor' => $valor, 'vencimento' => $vencimento]);
    }

    public function cancelar(int $id): void
    {
        $this->pdo->prepare("UPDATE vendas SET status = 'Cancelada' WHERE id = :id")->execute(['id' => $id]);
        // Parcelas de uma venda cancelada deixam de ser dívida. Só chega
        // aqui se nenhuma estiver paga (o serviço confere antes).
        $this->pdo->prepare('DELETE FROM venda_parcelas WHERE venda_id = :id')->execute(['id' => $id]);
    }

    // Soma do que o cliente ainda deve de fiado — é contra isso (mais
    // a venda nova) que o limite_credito é comparado.
    public function dividaEmAberto(int $clienteId): float
    {
        $consulta = $this->pdo->prepare(
            "SELECT COALESCE(SUM(vp.valor), 0)
             FROM venda_parcelas vp
             JOIN vendas v ON v.id = vp.venda_id
             WHERE v.cliente_id = :cliente_id AND v.status <> 'Cancelada' AND vp.status = 'Pendente'"
        );
        $consulta->execute(['cliente_id' => $clienteId]);
        return (float) $consulta->fetchColumn();
    }
}
