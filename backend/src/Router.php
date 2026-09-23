<?php

namespace App;

use App\Support\Request;
use App\Support\Response;

// Roteador simples: casa "método + caminho" com um [Controller, método].
// Suporta parâmetros de rota com {chave}, ex.: /usuarios/{id}.
class Router
{
    private array $rotas = [];

    public function adicionar(string $metodo, string $padrao, array $acao): void
    {
        $this->rotas[] = ['metodo' => $metodo, 'padrao' => $padrao, 'acao' => $acao];
    }

    public function get(string $padrao, array $acao): void
    {
        $this->adicionar('GET', $padrao, $acao);
    }

    public function post(string $padrao, array $acao): void
    {
        $this->adicionar('POST', $padrao, $acao);
    }

    public function put(string $padrao, array $acao): void
    {
        $this->adicionar('PUT', $padrao, $acao);
    }

    public function patch(string $padrao, array $acao): void
    {
        $this->adicionar('PATCH', $padrao, $acao);
    }

    public function despachar(string $metodo, string $caminho): void
    {
        $caminho = '/' . trim($caminho, '/');

        foreach ($this->rotas as $rota) {
            if ($rota['metodo'] !== $metodo) {
                continue;
            }

            $parametros = $this->casar($rota['padrao'], $caminho);
            if ($parametros === null) {
                continue;
            }

            [$classe, $acao] = $rota['acao'];
            $controller = new $classe();
            $requisicao = new Request();

            $controller->$acao($requisicao, ...$parametros);
            return;
        }

        Response::erro('Rota não encontrada.', 404);
    }

    // Converte um padrão tipo "/usuarios/{id}" numa regex e extrai os
    // valores reais do caminho recebido, na ordem em que aparecem.
    private function casar(string $padrao, string $caminho): ?array
    {
        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $padrao);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $caminho, $capturas)) {
            return null;
        }

        array_shift($capturas);
        return $capturas;
    }
}
