<?php

namespace App\Services;

use App\Config\Database;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProdutoRepository;
use App\Support\ErroDeNegocio;

class InventarioService
{
    // $contagens: [{produto_id, contagem}] — a contagem final de cada
    // produto contado (no modo de 3 contagens a tela já manda a mediana).
    //
    // A diferença é calculada contra o estoque NO MOMENTO de finalizar,
    // não contra o que a tela mostrava quando foi aberta: o documento
    // de ajuste precisa levar o saldo exatamente pro número contado.
    // Sobras viram um documento de Entrada e faltas um de Saída, os
    // dois com origem "Ajuste de Inventário" — nada é escrito direto
    // em produtos.estoque_atual.
    public function finalizar(array $contagens, ?string $observacao): array
    {
        $porProduto = [];
        foreach ($contagens as $linha) {
            $produtoId = (int) ($linha['produto_id'] ?? 0);
            $contagem = filter_var($linha['contagem'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if (!$produtoId || $contagem === false) {
                throw new ErroDeNegocio('Contagem inválida: use números inteiros a partir de 0.', 422);
            }
            $porProduto[$produtoId] = $contagem;
        }

        if (!$porProduto) {
            throw new ErroDeNegocio('Nenhuma contagem lançada.', 422);
        }

        return Database::transacao(function () use ($porProduto, $observacao) {
            $produtos = (new ProdutoRepository())->buscarParaMovimentar(array_keys($porProduto));
            $sobras = [];
            $faltas = [];

            foreach ($porProduto as $produtoId => $contagem) {
                if (!isset($produtos[$produtoId])) {
                    throw new ErroDeNegocio("Produto #{$produtoId} não encontrado.", 404);
                }
                $diferenca = $contagem - (int) $produtos[$produtoId]['estoque_atual'];
                if ($diferenca > 0) {
                    $sobras[] = ['produto_id' => $produtoId, 'quantidade' => $diferenca];
                } elseif ($diferenca < 0) {
                    $faltas[] = ['produto_id' => $produtoId, 'quantidade' => -$diferenca];
                }
            }

            $documentos = new DocumentoRepository();
            $hoje = date('Y-m-d');
            $observacao = $observacao ?: 'Inventário de ' . date('d/m/Y');

            if ($sobras) {
                $documentos->registrar('Entrada', 'Ajuste de Inventário', null, $hoje, $sobras, $observacao);
            }
            if ($faltas) {
                $documentos->registrar('Saída', 'Ajuste de Inventário', null, $hoje, $faltas, $observacao);
            }

            return [
                'produtos_contados' => count($porProduto),
                'produtos_ajustados' => count($sobras) + count($faltas),
                'unidades_a_mais' => array_sum(array_column($sobras, 'quantidade')),
                'unidades_a_menos' => array_sum(array_column($faltas, 'quantidade')),
            ];
        });
    }

    // Peça danificada, furtada, usada de mostruário... sai do estoque
    // com origem própria, pra não se misturar com venda nos relatórios.
    public function registrarPerda(int $produtoId, mixed $quantidade, ?string $observacao): void
    {
        $quantidade = filter_var($quantidade, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$produtoId || $quantidade === false) {
            throw new ErroDeNegocio('Informe o produto e uma quantidade de pelo menos 1.', 422);
        }
        if (!$observacao) {
            throw new ErroDeNegocio('Descreva o motivo da perda/avaria.', 422);
        }

        Database::transacao(function () use ($produtoId, $quantidade, $observacao) {
            $produto = (new ProdutoRepository())->buscarParaMovimentar([$produtoId])[$produtoId] ?? null;
            if (!$produto) {
                throw new ErroDeNegocio('Produto não encontrado.', 404);
            }
            if ((int) $produto['estoque_atual'] < $quantidade) {
                throw new ErroDeNegocio("O sistema só tem {$produto['estoque_atual']} un. de \"{$produto['nome']}\" — faça um inventário se o saldo estiver errado.", 409);
            }

            (new DocumentoRepository())->registrar(
                'Saída',
                'Perda/Avaria',
                null,
                date('Y-m-d'),
                [['produto_id' => $produtoId, 'quantidade' => $quantidade]],
                mb_substr($observacao, 0, 255)
            );
        });
    }
}
