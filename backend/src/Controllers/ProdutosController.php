<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\ProdutoRepository;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

class ProdutosController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new ProdutoRepository())->listarTodos());
    }

    // Não usado pela tela de produtos.php hoje (produto nasce em
    // entradas.php) — já deixado pronto pra quando isso for ligado.
    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $repositorio = new ProdutoRepository();

        $validador = (new Validator())
            ->obrigatorio($dados, 'codigo_interno', 'Código')
            ->obrigatorio($dados, 'nome', 'Nome');

        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if ($repositorio->codigoJaExiste($dados['codigo_interno'])) {
            Response::erro('Já existe um produto com esse código.', 409);
        }

        Response::json($repositorio->criar($dados), 201);
    }

    public function atualizar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $repositorio = new ProdutoRepository();

        if (!$repositorio->buscarPorId((int) $id)) {
            Response::erro('Produto não encontrado.', 404);
        }

        $validador = (new Validator())
            ->obrigatorio($dados, 'nome', 'Nome')
            ->obrigatorio($dados, 'preco_venda', 'Preço de venda');

        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        Response::json($repositorio->atualizar((int) $id, $dados));
    }

    public function alternarAtivo(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $repositorio = new ProdutoRepository();
        $produto = $repositorio->buscarPorId((int) $id);

        if (!$produto) {
            Response::erro('Produto não encontrado.', 404);
        }

        Response::json($repositorio->alternarAtivo((int) $id, !$produto['ativo']));
    }
}
