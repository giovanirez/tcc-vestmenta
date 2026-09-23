<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\ClienteRepository;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

class ClientesController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new ClienteRepository())->listarTodos());
    }

    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $repositorio = new ClienteRepository();

        $validador = $this->validar($dados);
        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if (!empty($dados['cpf']) && $repositorio->cpfJaExiste($dados['cpf'])) {
            Response::erro('Já existe um cliente com esse CPF.', 409);
        }

        Response::json($repositorio->criar($dados), 201);
    }

    public function atualizar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $repositorio = new ClienteRepository();

        if (!$repositorio->buscarPorId((int) $id)) {
            Response::erro('Cliente não encontrado.', 404);
        }

        $validador = $this->validar($dados);
        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if (!empty($dados['cpf']) && $repositorio->cpfJaExiste($dados['cpf'], (int) $id)) {
            Response::erro('Já existe outro cliente com esse CPF.', 409);
        }

        Response::json($repositorio->atualizar((int) $id, $dados));
    }

    private function validar(array $dados): Validator
    {
        return (new Validator())
            ->obrigatorio($dados, 'nome', 'Nome')
            ->obrigatorio($dados, 'telefone', 'Telefone')
            ->email($dados, 'email', 'E-mail');
    }
}
