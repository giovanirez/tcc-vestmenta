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
        Response::json((new CategoriaRepository())->listarTodas());
    }
}
