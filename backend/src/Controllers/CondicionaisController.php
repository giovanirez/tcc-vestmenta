<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\CondicionalRepository;
use App\Repositories\VendaRepository;
use App\Services\CondicionalService;
use App\Support\Request;
use App\Support\Response;

class CondicionaisController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new CondicionalRepository())->listarTodas());
    }

    public function detalhar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $condicional = (new CondicionalRepository())->buscarPorId((int) $id);
        if (!$condicional) {
            Response::erro('Condicional não encontrada.', 404);
        }

        Response::json($condicional);
    }

    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $id = (new CondicionalService())->abrir($requisicao->todaEntrada());

        Response::json((new CondicionalRepository())->buscarPorId($id), 201);
    }

    // Devolve a venda gerada (pra tela poder oferecer o recibo).
    public function finalizar(Request $requisicao, string $id): void
    {
        $usuario = Auth::exigirLogin($requisicao);

        $vendaId = (new CondicionalService())->finalizar((int) $id, $requisicao->todaEntrada(), (int) ($usuario['sub'] ?? 0) ?: null);

        Response::json((new VendaRepository())->buscarPorId($vendaId), 201);
    }

    public function devolver(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        (new CondicionalService())->devolver((int) $id);

        Response::json((new CondicionalRepository())->buscarPorId((int) $id));
    }
}
