<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class EntradaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // qtd_total é somado na consulta (não existe coluna pra isso) —
    // é o "Qtd Peças" do histórico em entradas.php.
    public function listarTodas(): array
    {
        return $this->pdo->query(
            'SELECT e.id, e.numero_nf, e.serie, e.data_entrada, e.valor_total, e.status,
                    f.razao_social AS fornecedor_nome,
                    COALESCE((SELECT SUM(i.quantidade) FROM entrada_itens i WHERE i.entrada_id = e.id), 0) AS qtd_total
             FROM entradas e
             JOIN fornecedores f ON f.id = e.fornecedor_id
             ORDER BY e.data_entrada DESC, e.id DESC'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT e.*, f.razao_social AS fornecedor_nome
             FROM entradas e
             JOIN fornecedores f ON f.id = e.fornecedor_id
             WHERE e.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $entrada = $consulta->fetch();
        if (!$entrada) {
            return null;
        }

        $itens = $this->pdo->prepare(
            'SELECT i.*, p.codigo_interno, p.nome AS produto_nome
             FROM entrada_itens i
             JOIN produtos p ON p.id = i.produto_id
             WHERE i.entrada_id = :id
             ORDER BY i.id'
        );
        $itens->execute(['id' => $id]);
        $entrada['itens'] = $itens->fetchAll();

        return $entrada;
    }

    // $cabecalho já vem com os totais calculados pelo controller.
    public function criar(array $cabecalho): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO entradas
                (fornecedor_id, numero_nf, serie, chave_acesso, natureza_operacao, data_emissao, data_entrada,
                 modalidade_frete, transportadora, placa_veiculo, valor_produtos, valor_frete, valor_seguro,
                 valor_outras_despesas, valor_desconto, valor_icms, valor_ipi, valor_total,
                 informacoes_complementares, status)
             VALUES
                (:fornecedor_id, :numero_nf, :serie, :chave_acesso, :natureza_operacao, :data_emissao, :data_entrada,
                 :modalidade_frete, :transportadora, :placa_veiculo, :valor_produtos, :valor_frete, :valor_seguro,
                 :valor_outras_despesas, :valor_desconto, :valor_icms, :valor_ipi, :valor_total,
                 :informacoes_complementares, \'Concluída\')
             RETURNING id'
        );
        $consulta->execute($cabecalho);
        return (int) $consulta->fetchColumn();
    }

    public function adicionarItem(int $entradaId, int $produtoId, int $quantidade, float $custoUnitario): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO entrada_itens (entrada_id, produto_id, quantidade, custo_unitario, valor_total)
             VALUES (:entrada_id, :produto_id, :quantidade, :custo_unitario, :valor_total)'
        );
        $consulta->execute([
            'entrada_id' => $entradaId,
            'produto_id' => $produtoId,
            'quantidade' => $quantidade,
            'custo_unitario' => $custoUnitario,
            'valor_total' => round($quantidade * $custoUnitario, 2),
        ]);
    }
}
