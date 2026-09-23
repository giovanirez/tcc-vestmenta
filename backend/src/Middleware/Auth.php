<?php

namespace App\Middleware;

use App\Support\Jwt;
use App\Support\Request;
use App\Support\Response;

class Auth
{
    // Exige um token válido no cabeçalho Authorization: Bearer <token>.
    // Se estiver tudo certo, devolve os dados do usuário (id, papel...)
    // que foram gravados no token no momento do login. Se não, responde
    // 401 e interrompe a requisição — a rota nem chega a rodar.
    public static function exigirLogin(Request $requisicao): array
    {
        $cabecalho = $requisicao->cabecalhoAutorizacao();
        if (!$cabecalho || !str_starts_with($cabecalho, 'Bearer ')) {
            Response::erro('Não autenticado.', 401);
        }

        $token = substr($cabecalho, 7);
        $payload = Jwt::validar($token);

        if ($payload === null) {
            Response::erro('Token inválido ou expirado.', 401);
        }

        return $payload;
    }

    // Além de estar logado, exige um papel específico (ex.: 'admin').
    // Usado nas rotas de gestão de usuários — vendedor não pode
    // criar/editar outros usuários.
    public static function exigirPapel(Request $requisicao, string $papel): array
    {
        $usuario = self::exigirLogin($requisicao);

        if (($usuario['papel'] ?? null) !== $papel) {
            Response::erro('Você não tem permissão pra fazer isso.', 403);
        }

        return $usuario;
    }
}
