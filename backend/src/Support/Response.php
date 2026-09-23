<?php

namespace App\Support;

class Response
{
    public static function json(mixed $dado, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // A API sempre reflete o estado atual do banco — nunca deixa
        // o navegador guardar uma resposta antiga em cache.
        header('Cache-Control: no-store');
        echo json_encode($dado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function erro(string $mensagem, int $status = 400): never
    {
        self::json(['erro' => $mensagem], $status);
    }
}
