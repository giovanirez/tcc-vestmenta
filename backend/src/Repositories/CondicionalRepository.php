<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class CondicionalRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // venda_id: a venda gerada no "Finalizar Venda" (só em Aprovado).
    public function listarTodas(): array
    {
        return $this->pdo->query(
            "SELECT co.id, co.data_saida, co.data_conclusao, co.status, co.cliente_id,
                    c.nome AS cliente_nome,
                    (SELECT string_agg(ci.quantidade || 'x ' || p.nome, ', ' ORDER BY ci.id)
                     FROM condicional_itens ci JOIN produtos p ON p.id = ci.produto_id
                     WHERE ci.condicional_id = co.id) AS produtos_resumo,
                    (SELECT v.id FROM vendas v WHERE v.condicional_id = co.id AND v.status <> 'Cancelada'
                     ORDER BY v.id DESC LIMIT 1) AS venda_id
             FROM condicionais co
             JOIN clientes c ON c.id = co.cliente_id
             ORDER BY (co.status = 'Pendente') DESC, co.data_saida DESC, co.id DESC"
        )->fetchAll();
    }

    public function buscarPorId(int $id, bool $bloquear = false): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT co.*, c.nome AS cliente_nome
             FROM condicionais co
             JOIN clientes c ON c.id = co.cliente_id
             WHERE co.id = :id' . ($bloquear ? ' FOR UPDATE OF co' : '')
        );
        $consulta->execute(['id' => $id]);
        $condicional = $consulta->fetch();
        if (!$condicional) {
            return null;
        }

        // preco_venda/estoque vêm junto pra tela de "Finalizar Venda"
        // já mostrar o valor de cada peça.
        $itens = $this->pdo->prepare(
            'SELECT ci.*, p.codigo_interno, p.nome AS produto_nome, p.preco_venda
             FROM condicional_itens ci
             JOIN produtos p ON p.id = ci.produto_id
             WHERE ci.condicional_id = :id
             ORDER BY ci.id'
        );
        $itens->execute(['id' => $id]);
        $condicional['itens'] = $itens->fetchAll();

        return $condicional;
    }

    public function criar(int $clienteId, string $dataSaida): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO condicionais (cliente_id, data_saida) VALUES (:cliente_id, :data_saida) RETURNING id'
        );
        $consulta->execute(['cliente_id' => $clienteId, 'data_saida' => $dataSaida]);
        return (int) $consulta->fetchColumn();
    }

    public function adicionarItem(int $condicionalId, int $produtoId, int $quantidade): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO condicional_itens (condicional_id, produto_id, quantidade)
             VALUES (:condicional_id, :produto_id, :quantidade)'
        );
        $consulta->execute(['condicional_id' => $condicionalId, 'produto_id' => $produtoId, 'quantidade' => $quantidade]);
    }

    public function concluir(int $id, string $status, string $data): void
    {
        $consulta = $this->pdo->prepare(
            'UPDATE condicionais SET status = :status, data_conclusao = :data WHERE id = :id'
        );
        $consulta->execute(['id' => $id, 'status' => $status, 'data' => $data]);
    }
}
