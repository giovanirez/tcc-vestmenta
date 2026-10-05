<?php

namespace App\Repositories;

use App\Config\Database;
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
    public function registrar(string $tipo, string $origem, ?int $referenciaId, string $data, array $itens, ?string $observacao = null): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO documentos (tipo, origem, referencia_id, data, observacao)
             VALUES (:tipo, :origem, :referencia_id, :data, :observacao)
             RETURNING id'
        );
        $consulta->execute([
            'tipo' => $tipo,
            'origem' => $origem,
            'referencia_id' => $referenciaId,
            'data' => $data,
            'observacao' => $observacao,
        ]);
        $documentoId = (int) $consulta->fetchColumn();

        $insereItem = $this->pdo->prepare(
            'INSERT INTO documento_itens (documento_id, produto_id, quantidade)
             VALUES (:documento_id, :produto_id, :quantidade)'
        );
        foreach ($itens as $item) {
            $insereItem->execute([
                'documento_id' => $documentoId,
                'produto_id' => $item['produto_id'],
                'quantidade' => $item['quantidade'],
            ]);
        }

        return $documentoId;
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
