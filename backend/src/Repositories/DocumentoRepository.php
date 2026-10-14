<?php

namespace App\Repositories;

use App\Config\Database;
use App\Support\Lote;
use PDO;

// O "kardex": único caminho pra mexer no estoque. Inserir itens aqui
// dispara a trigger fn_documento_itens_atualiza_estoque, que soma ou
// subtrai produtos.estoque_atual conforme o tipo do documento.
// Entradas, vendas, condicionais e inventário usam todos esta classe.
class DocumentoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // $itens: lista de ['produto_id' => int, 'quantidade' => int > 0]
    //
    // Cabeçalho e todos os itens numa ida só ao banco: o INSERT do
    // documento fica num WITH, e os itens usam o id que ele devolve.
    // A trigger de estoque e a FK rodam no fim do comando, quando o
    // documento já existe — por isso funciona num comando só.
    public function registrar(string $tipo, string $origem, ?int $referenciaId, string $data, array $itens, ?string $observacao = null): int
    {
        if (!$itens) {
            throw new \InvalidArgumentException('Documento de estoque sem itens.');
        }

        [$valores, $parametrosItens] = Lote::valores($itens, ['produto_id', 'quantidade'], ['produto_id' => 'BIGINT', 'quantidade' => 'INTEGER']);

        $consulta = $this->pdo->prepare(
            "WITH documento AS (
                INSERT INTO documentos (tipo, origem, referencia_id, data, observacao)
                VALUES (?, ?, CAST(? AS BIGINT), CAST(? AS DATE), ?)
                RETURNING id
             )
             INSERT INTO documento_itens (documento_id, produto_id, quantidade)
             SELECT documento.id, itens.produto_id, itens.quantidade
             FROM documento CROSS JOIN (VALUES {$valores}) AS itens(produto_id, quantidade)
             RETURNING documento_id"
        );
        $consulta->execute(array_merge([$tipo, $origem, $referenciaId, $data, $observacao], $parametrosItens));

        return (int) $consulta->fetchColumn();
    }

    // Últimos ajustes manuais (inventário e perdas) — os que não têm
    // uma entrada/venda/condicional por trás pra explicar o movimento.
    public function listarAjustes(int $limite = 30): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT d.id, d.tipo, d.origem, d.data, d.observacao, d.criado_em,
                    string_agg(di.quantidade || 'x ' || p.nome, ', ' ORDER BY di.id) AS itens_resumo,
                    SUM(di.quantidade) AS total_unidades
             FROM documentos d
             JOIN documento_itens di ON di.documento_id = d.id
             JOIN produtos p ON p.id = di.produto_id
             WHERE d.origem IN ('Ajuste de Inventário', 'Perda/Avaria')
             GROUP BY d.id
             ORDER BY d.criado_em DESC, d.id DESC
             LIMIT :limite"
        );
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }
}
