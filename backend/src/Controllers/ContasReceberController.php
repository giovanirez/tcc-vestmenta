<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\ParcelaRepository;
use App\Support\Request;
use App\Support\Response;

class ContasReceberController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new ParcelaRepository())->listarTodas(date('Y-m-d')));
    }

    // data_pagamento é opcional (padrão: hoje) — permite registrar um
    // pagamento recebido em outro dia e só lançado depois.
    public function pagar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $repositorio = new ParcelaRepository();
        $parcela = $repositorio->buscarPorId((int) $id);

        if (!$parcela || $parcela['venda_status'] === 'Cancelada') {
            Response::erro('Parcela não encontrada.', 404);
        }
        if ($parcela['status'] === 'Paga') {
            Response::erro('Essa parcela já está paga.', 409);
        }

        $data = $requisicao->entrada('data_pagamento') ?: date('Y-m-d');
        $dataValida = \DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        if (!$dataValida || $dataValida->format('Y-m-d') !== $data || $data > date('Y-m-d')) {
            Response::erro('Data de pagamento inválida.', 422);
        }

        Response::json($repositorio->marcarComoPaga((int) $id, $data));
    }
}
