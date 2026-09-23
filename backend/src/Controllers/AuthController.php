<?php

namespace App\Controllers;

use App\Repositories\UsuarioRepository;
use App\Support\Jwt;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

class AuthController
{
    public function login(Request $requisicao): void
    {
        $dados = $requisicao->todaEntrada();

        $validador = (new Validator())
            ->obrigatorio($dados, 'email', 'E-mail')
            ->obrigatorio($dados, 'senha', 'Senha');

        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        $repositorio = new UsuarioRepository();
        $usuario = $repositorio->buscarPorEmail($dados['email']);

        // Mensagem genérica de propósito: não revela se o problema foi
        // e-mail inexistente ou senha errada (evita enumeração de contas).
        if (!$usuario || !password_verify($dados['senha'], $usuario['senha_hash'])) {
            Response::erro('E-mail ou senha inválidos.', 401);
        }

        if (!$usuario['ativo']) {
            Response::erro('Este usuário está desativado.', 403);
        }

        $token = Jwt::gerar([
            'sub' => $usuario['id'],
            'nome' => $usuario['nome'],
            'papel' => $usuario['papel'],
            'exp' => time() + (8 * 60 * 60), // 8 horas
        ]);

        Response::json([
            'token' => $token,
            'usuario' => [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'papel' => $usuario['papel'],
            ],
        ]);
    }
}
