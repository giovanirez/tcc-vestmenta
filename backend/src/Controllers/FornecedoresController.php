<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\FornecedorRepository;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

class FornecedoresController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new FornecedorRepository())->listarTodos());
    }

    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $repositorio = new FornecedorRepository();

        $validador = $this->validar($dados);
        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if ($repositorio->cnpjJaExiste($dados['cnpj'])) {
            Response::erro('Já existe um fornecedor com esse CNPJ.', 409);
        }

        Response::json($repositorio->criar($dados), 201);
    }

    public function atualizar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $repositorio = new FornecedorRepository();

        if (!$repositorio->buscarPorId((int) $id)) {
            Response::erro('Fornecedor não encontrado.', 404);
        }

        $validador = $this->validar($dados);
        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if ($repositorio->cnpjJaExiste($dados['cnpj'], (int) $id)) {
            Response::erro('Já existe outro fornecedor com esse CNPJ.', 409);
        }

        Response::json($repositorio->atualizar((int) $id, $dados));
    }

    public function alternarAtivo(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $repositorio = new FornecedorRepository();
        $fornecedor = $repositorio->buscarPorId((int) $id);

        if (!$fornecedor) {
            Response::erro('Fornecedor não encontrado.', 404);
        }

        Response::json($repositorio->alternarAtivo((int) $id, !$fornecedor['ativo']));
    }

    private function validar(array $dados): Validator
    {
        return (new Validator())
            ->obrigatorio($dados, 'razao_social', 'Razão social')
            ->obrigatorio($dados, 'cnpj', 'CNPJ')
            ->obrigatorio($dados, 'telefone', 'Telefone')
            ->obrigatorio($dados, 'email', 'E-mail')
            ->email($dados, 'email', 'E-mail');
    }
}
