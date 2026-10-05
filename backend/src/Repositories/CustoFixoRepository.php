<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class CustoFixoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query('SELECT * FROM custos_fixos ORDER BY ativo DESC, dia_vencimento, nome')->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM custos_fixos WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $custo = $consulta->fetch();
        return $custo ?: null;
    }

    public function criar(array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO custos_fixos (nome, categoria, valor, dia_vencimento, observacao)
             VALUES (:nome, :categoria, :valor, :dia_vencimento, :observacao)
             RETURNING *'
        );
        $consulta->execute($this->parametros($dados));
        return $consulta->fetch();
    }

    public function atualizar(int $id, array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'UPDATE custos_fixos SET
                nome = :nome, categoria = :categoria, valor = :valor,
                dia_vencimento = :dia_vencimento, observacao = :observacao
             WHERE id = :id
             RETURNING *'
        );
        $consulta->execute($this->parametros($dados) + ['id' => $id]);
        return $consulta->fetch();
    }

    public function alternarAtivo(int $id, bool $ativo): array
    {
        $consulta = $this->pdo->prepare('UPDATE custos_fixos SET ativo = :ativo WHERE id = :id RETURNING *');
        // Mesmo motivo do ProdutoRepository: bool precisa de tipo explícito no PDO_PGSQL.
        $consulta->bindValue('id', $id, PDO::PARAM_INT);
        $consulta->bindValue('ativo', $ativo, PDO::PARAM_BOOL);
        $consulta->execute();
        return $consulta->fetch();
    }

    // Soma dos custos ativos — usada no relatório pra comparar com o faturamento.
    public function totalMensalAtivo(): float
    {
        return (float) $this->pdo->query('SELECT COALESCE(SUM(valor), 0) FROM custos_fixos WHERE ativo')->fetchColumn();
    }

    private function parametros(array $dados): array
    {
        return [
            'nome' => trim($dados['nome']),
            'categoria' => ($dados['categoria'] ?? '') ?: null,
            'valor' => round((float) $dados['valor'], 2),
            'dia_vencimento' => (int) $dados['dia_vencimento'],
            'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
        ];
    }
}
