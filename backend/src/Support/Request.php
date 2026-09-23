<?php

namespace App\Support;

class Request
{
    private array $corpo;

    public function __construct()
    {
        $bruto = file_get_contents('php://input');
        $decodificado = $bruto ? json_decode($bruto, true) : null;
        $this->corpo = is_array($decodificado) ? $decodificado : [];
    }

    public function entrada(string $chave, mixed $padrao = null): mixed
    {
        return $this->corpo[$chave] ?? $padrao;
    }

    public function todaEntrada(): array
    {
        return $this->corpo;
    }

    public function query(string $chave, mixed $padrao = null): mixed
    {
        return $_GET[$chave] ?? $padrao;
    }

    public function cabecalhoAutorizacao(): ?string
    {
        $cabecalhos = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($cabecalhos as $nome => $valor) {
            if (strcasecmp($nome, 'Authorization') === 0) {
                return $valor;
            }
        }
        return null;
    }
}
