<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

// Contas a receber = parcelas das vendas "Fiado" (contas-a-receber.php).
class ParcelaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // "Atrasada" não existe no banco: é calculada aqui, comparando com
    // a data de hoje. A data vem do PHP (fuso de Brasília) e não do
    // CURRENT_DATE do Postgres, que no Supabase roda em UTC — depois
    // das 21h ele já estaria "amanhã".
    public function listarTodas(string $hoje): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT vp.id, vp.venda_id, vp.numero_parcela, vp.valor, vp.data_vencimento, vp.data_pagamento,
                    CASE
                        WHEN vp.status = 'Paga' THEN 'Paga'
                        WHEN vp.data_vencimento < :hoje THEN 'Atrasada'
                        ELSE 'Pendente'
                    END AS status,
                    v.cliente_id, c.nome AS cliente_nome
             FROM venda_parcelas vp
             JOIN vendas v ON v.id = vp.venda_id
             LEFT JOIN clientes c ON c.id = v.cliente_id
             WHERE v.status <> 'Cancelada'
             ORDER BY (vp.status = 'Paga'), vp.data_vencimento, vp.id"
        );
        $consulta->execute(['hoje' => $hoje]);
        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT vp.*, v.status AS venda_status
             FROM venda_parcelas vp
             JOIN vendas v ON v.id = vp.venda_id
             WHERE vp.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $parcela = $consulta->fetch();
        return $parcela ?: null;
    }

    public function marcarComoPaga(int $id, string $dataPagamento): array
    {
        $consulta = $this->pdo->prepare(
            "UPDATE venda_parcelas SET status = 'Paga', data_pagamento = :data
             WHERE id = :id
             RETURNING *"
        );
        $consulta->execute(['id' => $id, 'data' => $dataPagamento]);
        return $consulta->fetch();
    }
}
