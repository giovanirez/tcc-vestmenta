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
    //      produto existente tem descrição/categoria/preço de venda
    //      atualizados no cadastro (o custo é só desta compra e fica
    //      em entrada_itens — não sobrescreve o cadastro);
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

            // Tudo em lote, pra não ir ao banco uma vez por item (a API fica
            // longe do banco): uma busca pra todos os códigos, um INSERT pra
            // todos os produtos novos, um pros itens e um pro kardex.
            $existentes = $produtos->buscarPorCodigos(array_map(fn ($item) => trim($item['codigo']), $itens), true);

            // Produtos novos: o mesmo código novo pode aparecer em duas
            // linhas — cadastra uma vez só, com os dados da primeira.
            $novos = [];
            // Existentes: o que a entrada quer no cadastro (a última linha
            // daquele código vale).
            $mudancasCadastro = [];

            foreach ($itens as $item) {
                $codigo = trim($item['codigo']);
                $precoVenda = $this->numeroOuNulo($item['preco_venda'] ?? null);

                if (isset($existentes[$codigo])) {
                    $mudancasCadastro[$codigo] = $item;
                } elseif (!isset($novos[$codigo])) {
                    $categoria = $item['categoria_id'] ?? null;
                    $novos[$codigo] = [
                        'codigo_interno' => $codigo,
                        'nome' => trim($item['nome']),
                        'categoria_id' => $categoria !== null && $categoria !== '' ? (int) $categoria : null,
                        'fornecedor_id' => $fornecedorId,
                        'preco_custo' => round((float) $item['custo_unitario'], 2),
                        'preco_venda' => $precoVenda ?? 0,
                    ];
                }
            }

            $todos = $existentes + $produtos->criarVarios(array_values($novos));

            // Só vai ao banco se o cadastro realmente mudou (o normal é não
            // mudar nada — a tela só manda valores diferentes quando a
            // pessoa editou e confirmou o aviso).
            foreach ($mudancasCadastro as $codigo => $item) {
                $atual = $existentes[$codigo];
                $nome = $this->textoOuNulo($item['nome'] ?? null);
                $precoVenda = $this->numeroOuNulo($item['preco_venda'] ?? null);
                $mudarCategoria = array_key_exists('categoria_id', $item);
                $categoria = $item['categoria_id'] ?? null;
                $categoria = $categoria !== null && $categoria !== '' ? (int) $categoria : null;

                $mudou = ($nome !== null && $nome !== $atual['nome'])
                    || ($mudarCategoria && $categoria !== ($atual['categoria_id'] === null ? null : (int) $atual['categoria_id']))
                    || ($precoVenda !== null && round($precoVenda, 2) !== round((float) $atual['preco_venda'], 2));

                if ($mudou) {
                    $produtos->atualizarCadastroPelaEntrada((int) $atual['id'], $nome, $mudarCategoria, $categoria, $precoVenda);
                }
            }

            $itensResolvidos = [];
            $valorProdutos = 0.0;
            foreach ($itens as $item) {
                $quantidade = (int) $item['quantidade'];
                $custo = round((float) $item['custo_unitario'], 2);
                $itensResolvidos[] = ['produto_id' => (int) $todos[trim($item['codigo'])]['id'], 'quantidade' => $quantidade, 'custo' => $custo];
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

            $entradas->adicionarItens($entradaId, $itensResolvidos);

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

        // Uma consulta só pra saber quais códigos já existem.
        $existentes = (new ProdutoRepository())->buscarPorCodigos(
            array_filter(array_map(fn ($item) => trim((string) ($item['codigo'] ?? '')), $itens))
        );
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
            if (trim((string) ($item['nome'] ?? '')) === '' && !isset($existentes[$codigo])) {
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
