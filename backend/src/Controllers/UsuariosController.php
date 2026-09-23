<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\UsuarioRepository;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

// Todas as rotas aqui exigem estar logado como 'admin' — só quem
// administra a loja pode criar/editar outros usuários do sistema.
class UsuariosController
{
    private const PAPEIS_VALIDOS = ['admin', 'vendedor'];

    public function listar(Request $requisicao): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        $repositorio = new UsuarioRepository();
        Response::json($repositorio->listarTodos());
    }

    public function criar(Request $requisicao): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        $dados = $requisicao->todaEntrada();
        $repositorio = new UsuarioRepository();

        $validador = (new Validator())
            ->obrigatorio($dados, 'nome', 'Nome')
            ->obrigatorio($dados, 'email', 'E-mail')
            ->email($dados, 'email', 'E-mail')
            ->obrigatorio($dados, 'senha', 'Senha')
            ->tamanhoMinimo($dados, 'senha', 8, 'Senha')
            ->obrigatorio($dados, 'papel', 'Papel')
            ->dentroDaLista($dados, 'papel', self::PAPEIS_VALIDOS, 'Papel');

        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if ($repositorio->emailJaExiste($dados['email'])) {
            Response::erro('Já existe um usuário com esse e-mail.', 409);
        }

        $senhaHash = password_hash($dados['senha'], PASSWORD_BCRYPT);
        $usuario = $repositorio->criar($dados['nome'], $dados['email'], $senhaHash, $dados['papel']);

        Response::json($usuario, 201);
    }

    public function atualizar(Request $requisicao, string $id): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        $dados = $requisicao->todaEntrada();
        $repositorio = new UsuarioRepository();

        if (!$repositorio->buscarPorId((int) $id)) {
            Response::erro('Usuário não encontrado.', 404);
        }

        $validador = (new Validator())
            ->obrigatorio($dados, 'nome', 'Nome')
            ->obrigatorio($dados, 'email', 'E-mail')
            ->email($dados, 'email', 'E-mail')
            ->obrigatorio($dados, 'papel', 'Papel')
            ->dentroDaLista($dados, 'papel', self::PAPEIS_VALIDOS, 'Papel');

        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        if ($repositorio->emailJaExiste($dados['email'], (int) $id)) {
            Response::erro('Já existe outro usuário com esse e-mail.', 409);
        }

        $usuario = $repositorio->atualizar((int) $id, $dados['nome'], $dados['email'], $dados['papel']);
        Response::json($usuario);
    }

    public function redefinirSenha(Request $requisicao, string $id): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        $dados = $requisicao->todaEntrada();
        $repositorio = new UsuarioRepository();

        if (!$repositorio->buscarPorId((int) $id)) {
            Response::erro('Usuário não encontrado.', 404);
        }

        $validador = (new Validator())
            ->obrigatorio($dados, 'senha', 'Senha')
            ->tamanhoMinimo($dados, 'senha', 8, 'Senha');

        if (!$validador->valido()) {
            Response::erro(implode(' ', $validador->erros()), 422);
        }

        $senhaHash = password_hash($dados['senha'], PASSWORD_BCRYPT);
        $repositorio->redefinirSenha((int) $id, $senhaHash);

        Response::json(['mensagem' => 'Senha redefinida.']);
    }

    public function alternarAtivo(Request $requisicao, string $id): void
    {
        $usuarioLogado = Auth::exigirPapel($requisicao, 'admin');

        if ((int) $usuarioLogado['sub'] === (int) $id) {
            Response::erro('Você não pode desativar sua própria conta.', 422);
        }

        $repositorio = new UsuarioRepository();
        $usuario = $repositorio->buscarPorId((int) $id);

        if (!$usuario) {
            Response::erro('Usuário não encontrado.', 404);
        }

        $atualizado = $repositorio->alternarAtivo((int) $id, !$usuario['ativo']);
        Response::json($atualizado);
    }
}
