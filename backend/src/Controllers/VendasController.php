<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\VendaRepository;
use App\Services\VendaService;
use App\Support\Request;
use App\Support\Response;

class VendasController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new VendaRepository())->listarTodas());
    }

    // Usado pelo recibo.php — traz itens e parcelas junto.
    public function detalhar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $venda = (new VendaRepository())->buscarPorId((int) $id);
        if (!$venda) {
            Response::erro('Venda não encontrada.', 404);
        }

        Response::json($venda);
    }

    public function criar(Request $requisicao): void
    {
        $usuario = Auth::exigirLogin($requisicao);

        $vendaId = (new VendaService())->registrar($requisicao->todaEntrada(), (int) ($usuario['sub'] ?? 0) ?: null);

        Response::json((new VendaRepository())->buscarPorId($vendaId), 201);
    }

    // Só admin cancela: cancelar devolve peça pro estoque e apaga
    // dívida de fiado — não é algo que o vendedor deva fazer sozinho.
    public function cancelar(Request $requisicao, string $id): void
    {
        Auth::exigirPapel($requisicao, 'admin');

        (new VendaService())->cancelar((int) $id);

        Response::json((new VendaRepository())->buscarPorId((int) $id));
    }
}
