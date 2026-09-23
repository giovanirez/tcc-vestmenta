<?php

namespace App\Middleware;

use App\Config\Env;

class Cors
{
    public static function aplicar(): void
    {
        $origemPermitida = Env::obter('FRONTEND_ORIGIN', '*');

        header("Access-Control-Allow-Origin: {$origemPermitida}");
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Requisição de "pré-checagem" do navegador (CORS preflight) —
        // não chega a executar nenhuma rota, só confirma que pode.
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
