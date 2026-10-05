<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\DocumentoRepository;
use App\Services\InventarioService;
use App\Support\Request;
use App\Support\Response;

// Ajustes de estoque são só pra admin: um ajuste errado "some" com
// peças do sistema sem nenhuma venda/entrada que explique o movimento.
// A contagem em si qualquer um faz na tela — só o fechamento é restrito.
class InventarioController
{
    public function ajustes(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new DocumentoRepository())->listarAjustes());
    }

    public function finalizar(Request $requisicao): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        $contagens = $requisicao->entrada('contagens');
        $resultado = (new InventarioService())->finalizar(
            is_array($contagens) ? $contagens : [],
            trim((string) $requisicao->entrada('observacao', '')) ?: null
        );

        Response::json($resultado, 201);
    }

    public function registrarPerda(Request $requisicao): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        (new InventarioService())->registrarPerda(
            (int) $requisicao->entrada('produto_id', 0),
            $requisicao->entrada('quantidade'),
            trim((string) $requisicao->entrada('observacao', '')) ?: null
        );

        Response::json(['ok' => true], 201);
    }
}
