<?php

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\Auth;
use App\Repositories\DocumentoRepository;
use App\Repositories\EntradaRepository;
use App\Repositories\FornecedorRepository;
use App\Repositories\ProdutoRepository;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

class EntradasController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new EntradaRepository())->listarTodas());
    }

    public function detalhar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $entrada = (new EntradaRepository())->buscarPorId((int) $id);
        if (!$entrada) {
            Response::erro('Entrada não encontrada.', 404);
        }

        Response::json($entrada);
    }

    // Processa uma entrada inteira numa transação só:
    //   1. produto novo (código ainda não existe) é cadastrado na hora;
    //      produto existente tem o custo/preço atualizados;
    //   2. grava o cabeçalho em `entradas` e cada linha em `entrada_itens`;
    //   3. grava um documento Entrada/Compra no kardex — é ele que, via
    //      trigger, soma as peças no estoque.
    // Os totais são recalculados aqui, não confiados ao que o front mandou.
    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $itens = is_array($dados['itens'] ?? null) ? $dados['itens'] : [];

        $erros = $this->validar($dados, $itens);
        if ($erros) {
            Response::erro(implode(' ', $erros), 422);
        }

        if (!(new FornecedorRepository())->buscarPorId((int) $dados['fornecedor_id'])) {
            Response::erro('Fornecedor não encontrado.', 404);
        }

        $id = Database::transacao(function () use ($dados, $itens) {
            $produtos = new ProdutoRepository();
            $entradas = new EntradaRepository();
            $fornecedorId = (int) $dados['fornecedor_id'];

            $itensResolvidos = [];
            $valorProdutos = 0.0;

            foreach ($itens as $item) {
                $codigo = trim($item['codigo']);
                $quantidade = (int) $item['quantidade'];
                $custo = round((float) $item['custo_unitario'], 2);
                $precoVenda = $this->numeroOuNulo($item['preco_venda'] ?? null);

                $produto = $produtos->buscarPorCodigo($codigo);
                if ($produto) {
                    $produtos->atualizarPrecosDaEntrada((int) $produto['id'], $custo, $precoVenda);
                } else {
                    $produto = $produtos->criar([
                        'codigo_interno' => $codigo,
                        'nome' => trim($item['nome']),
                        'descricao' => null,
                        'categoria_id' => $item['categoria_id'] ?? null,
                        'fornecedor_id' => $fornecedorId,
                        'preco_custo' => $custo,
                        'preco_venda' => $precoVenda ?? 0,
                    ]);
                }

                $itensResolvidos[] = ['produto_id' => (int) $produto['id'], 'quantidade' => $quantidade, 'custo' => $custo];
                $valorProdutos += $quantidade * $custo;
            }

            $valores = [
                'valor_frete' => $this->valor($dados, 'valor_frete'),
                'valor_seguro' => $this->valor($dados, 'valor_seguro'),
                'valor_outras_despesas' => $this->valor($dados, 'valor_outras_despesas'),
                'valor_desconto' => $this->valor($dados, 'valor_desconto'),
                'valor_icms' => $this->valor($dados, 'valor_icms'),
                'valor_ipi' => $this->valor($dados, 'valor_ipi'),
            ];
            $valorProdutos = round($valorProdutos, 2);

            // Mesma fórmula do vNF da NF-e: o ICMS já está embutido no
            // preço dos produtos, por isso não entra na soma; o IPI sim.
            $valorTotal = $valorProdutos + $valores['valor_frete'] + $valores['valor_seguro']
                + $valores['valor_outras_despesas'] + $valores['valor_ipi'] - $valores['valor_desconto'];

            $entradaId = $entradas->criar([
                'fornecedor_id' => $fornecedorId,
                'numero_nf' => $this->textoOuNulo($dados['numero_nf'] ?? null),
                'serie' => $this->textoOuNulo($dados['serie'] ?? null),
                'chave_acesso' => $this->textoOuNulo(preg_replace('/\D/', '', (string) ($dados['chave_acesso'] ?? ''))),
                'natureza_operacao' => $this->textoOuNulo($dados['natureza_operacao'] ?? null),
                'data_emissao' => $this->textoOuNulo($dados['data_emissao'] ?? null),
                'data_entrada' => $dados['data_entrada'],
                'modalidade_frete' => (int) ($dados['modalidade_frete'] ?? 9),
                'transportadora' => $this->textoOuNulo($dados['transportadora'] ?? null),
                'placa_veiculo' => $this->textoOuNulo($dados['placa_veiculo'] ?? null),
                'valor_produtos' => $valorProdutos,
                'valor_total' => round($valorTotal, 2),
                'informacoes_complementares' => $this->textoOuNulo($dados['informacoes_complementares'] ?? null),
            ] + $valores);

            foreach ($itensResolvidos as $item) {
                $entradas->adicionarItem($entradaId, $item['produto_id'], $item['quantidade'], $item['custo']);
            }

            (new DocumentoRepository())->registrar('Entrada', 'Compra', $entradaId, $dados['data_entrada'], $itensResolvidos);

            return $entradaId;
        });

        Response::json((new EntradaRepository())->buscarPorId($id), 201);
    }

    private function validar(array $dados, array $itens): array
    {
        $validador = (new Validator())
            ->obrigatorio($dados, 'fornecedor_id', 'Fornecedor')
            ->obrigatorio($dados, 'data_entrada', 'Data de entrada')
            ->dentroDaLista($dados, 'modalidade_frete', ['0', '1', '2', '9', 0, 1, 2, 9], 'Modalidade do frete');
        $erros = $validador->erros();

        $chave = preg_replace('/\D/', '', (string) ($dados['chave_acesso'] ?? ''));
        if ($chave !== '' && strlen($chave) !== 44) {
            $erros[] = 'Chave de acesso precisa ter 44 dígitos.';
        }

        foreach (['valor_frete', 'valor_seguro', 'valor_outras_despesas', 'valor_desconto', 'valor_icms', 'valor_ipi'] as $campo) {
            if ($this->valor($dados, $campo) < 0) {
                $erros[] = 'Valores da nota não podem ser negativos.';
                break;
            }
        }

        if (!$itens) {
            $erros[] = 'Adicione pelo menos um item à entrada.';
            return $erros;
        }

        $produtos = new ProdutoRepository();
        foreach ($itens as $posicao => $item) {
            $linha = $posicao + 1;
            $codigo = trim((string) ($item['codigo'] ?? ''));

            if ($codigo === '') {
                $erros[] = "Item {$linha}: código é obrigatório.";
                continue;
            }
            if ((int) ($item['quantidade'] ?? 0) < 1) {
                $erros[] = "Item {$linha}: quantidade precisa ser pelo menos 1.";
            }
            if (!is_numeric($item['custo_unitario'] ?? null) || (float) $item['custo_unitario'] < 0) {
                $erros[] = "Item {$linha}: custo unitário inválido.";
            }
            // Nome só é exigido se o produto ainda não existe — é com ele
            // que o cadastro automático vai ser feito.
            if (trim((string) ($item['nome'] ?? '')) === '' && !$produtos->buscarPorCodigo($codigo)) {
                $erros[] = "Item {$linha}: produto novo precisa de descrição.";
            }
        }

        return $erros;
    }

    private function valor(array $dados, string $campo): float
    {
        return round((float) ($dados[$campo] ?? 0), 2);
    }

    private function numeroOuNulo(mixed $valor): ?float
    {
        return is_numeric($valor) && (float) $valor > 0 ? round((float) $valor, 2) : null;
    }

    private function textoOuNulo(mixed $valor): ?string
    {
        $texto = trim((string) $valor);
        return $texto === '' ? null : $texto;
    }
}
