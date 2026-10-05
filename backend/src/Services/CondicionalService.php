<?php

namespace App\Services;

use App\Config\Database;
use App\Repositories\ClienteRepository;
use App\Repositories\CondicionalRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProdutoRepository;
use App\Support\ErroDeNegocio;

// Ciclo de vida de uma condicional (malote):
//
//   abrir      -> peças saem do estoque (documento Saída/Condicional).
//                 Enquanto estão com o cliente, não podem ser vendidas
//                 pra outra pessoa no PDV.
//   finalizar  -> TODAS as peças voltam pro estoque (Entrada/Condicional)
//                 e as que o cliente ficou viram uma venda normal pelo
//                 VendaService (que dá a Saída/Venda). Parece uma volta a
//                 mais, mas assim o kardex conta a história certa: saiu
//                 em condicional, voltou, foi vendido — e a venda passa
//                 pelas mesmas regras do PDV (preço, estoque, fiado).
//   devolver   -> todas as peças voltam pro estoque, sem venda.
class CondicionalService
{
    public function abrir(array $dados): int
    {
        $clienteId = (int) ($dados['cliente_id'] ?? 0);
        $itens = $this->agruparItens($dados['itens'] ?? null);
        $dataSaida = date('Y-m-d');

        if (!$clienteId) {
            throw new ErroDeNegocio('Selecione o cliente da condicional.', 422);
        }
        if (!$itens) {
            throw new ErroDeNegocio('Adicione pelo menos uma peça à condicional.', 422);
        }

        return Database::transacao(function () use ($clienteId, $itens, $dataSaida) {
            if (!(new ClienteRepository())->buscarPorId($clienteId)) {
                throw new ErroDeNegocio('Cliente não encontrado.', 404);
            }

            $produtos = (new ProdutoRepository())->buscarParaMovimentar(array_keys($itens));
            foreach ($itens as $produtoId => $quantidade) {
                $produto = $produtos[$produtoId] ?? null;
                if (!$produto) {
                    throw new ErroDeNegocio("Produto #{$produtoId} não encontrado.", 404);
                }
                if (!$produto['ativo']) {
                    throw new ErroDeNegocio("\"{$produto['nome']}\" está desativado.", 409);
                }
                if ((int) $produto['estoque_atual'] < $quantidade) {
                    throw new ErroDeNegocio("Estoque insuficiente de \"{$produto['nome']}\": há {$produto['estoque_atual']} un.", 409);
                }
            }

            $condicionais = new CondicionalRepository();
            $id = $condicionais->criar($clienteId, $dataSaida);
            foreach ($itens as $produtoId => $quantidade) {
                $condicionais->adicionarItem($id, $produtoId, $quantidade);
            }

            (new DocumentoRepository())->registrar('Saída', 'Condicional', $id, $dataSaida, $this->paraDocumento($itens));

            return $id;
        });
    }

    // $dadosVenda segue o formato do VendaService::registrar, mas os
    // itens são só as peças que o cliente FICOU (o resto é devolvido).
    public function finalizar(int $id, array $dadosVenda, ?int $usuarioId): int
    {
        $comprados = $this->agruparItens($dadosVenda['itens'] ?? null);
        if (!$comprados) {
            throw new ErroDeNegocio('Nenhuma peça marcada como comprada — para devolver tudo, use "Registrar Devolução".', 422);
        }

        return Database::transacao(function () use ($id, $dadosVenda, $comprados, $usuarioId) {
            $condicional = $this->buscarPendente($id);
            $levados = $this->agruparItens($condicional['itens']);

            foreach ($comprados as $produtoId => $quantidade) {
                if ($quantidade > ($levados[$produtoId] ?? 0)) {
                    throw new ErroDeNegocio('A venda tem peça ou quantidade que não estava nesta condicional.', 422);
                }
            }

            $this->devolverPecas($condicional);

            $dadosVenda['cliente_id'] = $condicional['cliente_id'];
            $dadosVenda['itens'] = array_values(array_filter(
                $dadosVenda['itens'],
                fn ($item) => (int) ($item['quantidade'] ?? 0) > 0
            ));
            $vendaId = (new VendaService())->registrar($dadosVenda, $usuarioId, $id);

            (new CondicionalRepository())->concluir($id, 'Aprovado', date('Y-m-d'));

            return $vendaId;
        });
    }

    public function devolver(int $id): void
    {
        Database::transacao(function () use ($id) {
            $condicional = $this->buscarPendente($id);
            $this->devolverPecas($condicional);
            (new CondicionalRepository())->concluir($id, 'Devolvido', date('Y-m-d'));
        });
    }

    private function buscarPendente(int $id): array
    {
        $condicional = (new CondicionalRepository())->buscarPorId($id, true);
        if (!$condicional) {
            throw new ErroDeNegocio('Condicional não encontrada.', 404);
        }
        if ($condicional['status'] !== 'Pendente') {
            throw new ErroDeNegocio("Essa condicional já foi concluída ({$condicional['status']}).", 409);
        }
        return $condicional;
    }

    private function devolverPecas(array $condicional): void
    {
        (new DocumentoRepository())->registrar(
            'Entrada',
            'Condicional',
            (int) $condicional['id'],
            date('Y-m-d'),
            $this->paraDocumento($this->agruparItens($condicional['itens'])),
            "Retorno da condicional #{$condicional['id']}"
        );
    }

    // [{produto_id, quantidade}, ...] -> [produto_id => quantidade total]
    // ignorando linhas com quantidade zero. Lança erro em quantidade
    // negativa ou não numérica.
    private function agruparItens(mixed $itens): array
    {
        if (!is_array($itens)) {
            return [];
        }

        $agrupados = [];
        foreach ($itens as $item) {
            $produtoId = (int) ($item['produto_id'] ?? 0);
            $quantidade = filter_var($item['quantidade'] ?? null, FILTER_VALIDATE_INT);
            if (!$produtoId || $quantidade === false || $quantidade < 0) {
                throw new ErroDeNegocio('Item inválido: informe produto e quantidade.', 422);
            }
            if ($quantidade > 0) {
                $agrupados[$produtoId] = ($agrupados[$produtoId] ?? 0) + $quantidade;
            }
        }
        return $agrupados;
    }

    private function paraDocumento(array $agrupados): array
    {
        $itens = [];
        foreach ($agrupados as $produtoId => $quantidade) {
            $itens[] = ['produto_id' => $produtoId, 'quantidade' => $quantidade];
        }
        return $itens;
    }
}
