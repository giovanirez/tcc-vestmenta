<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\VendedorRepository;
use App\Support\Request;
use App\Support\Response;

class VendedoresController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new VendedorRepository())->listarComVendas());
    }

    // Se já existe alguém com esse nome (ignorando maiúsculas), devolve o
    // existente em vez de duplicar — quem chama só quer o id pra selecionar.
    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $nome = preg_replace('/\s+/u', ' ', trim((string) $requisicao->entrada('nome', '')));

        if ($nome === '') {
            Response::erro('Informe o nome do vendedor.', 422);
        }
        if (mb_strlen($nome) > 80) {
            Response::erro('Nome do vendedor pode ter no máximo 80 caracteres.', 422);
        }

        $repositorio = new VendedorRepository();
        $existente = $repositorio->buscarPorNome($nome);
        if ($existente) {
            Response::json($existente);
        }

        Response::json($repositorio->criar($nome), 201);
    }
}
