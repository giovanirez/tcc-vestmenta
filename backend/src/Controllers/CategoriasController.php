<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\CategoriaRepository;
use App\Support\Request;
use App\Support\Response;

class CategoriasController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new CategoriaRepository())->listarEmUso());
    }

    // Se já existe uma categoria com esse nome (ignorando maiúsculas),
    // devolve a existente em vez de duplicar — quem chama só quer o id
    // pra selecionar no formulário.
    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        // Tira espaços das pontas e espaços repetidos no meio.
        $nome = preg_replace('/\s+/u', ' ', trim((string) $requisicao->entrada('nome', '')));

        if ($nome === '') {
            Response::erro('Informe o nome da categoria.', 422);
        }
        if (mb_strlen($nome) > 60) {
            Response::erro('Nome da categoria pode ter no máximo 60 caracteres.', 422);
        }

        $repositorio = new CategoriaRepository();
        $existente = $repositorio->buscarPorNome($nome);
        if ($existente) {
            Response::json($existente);
        }

        Response::json($repositorio->criar($nome), 201);
    }
}
