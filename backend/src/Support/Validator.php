<?php

namespace App\Support;

// Validação simples de entrada. O front já valida pra dar feedback
// rápido ao usuário, mas isso aqui é o que realmente decide se o
// dado entra no banco — qualquer requisição pode chegar direto na
// API, sem passar pelo front nenhuma vez.
class Validator
{
    private array $erros = [];

    public function obrigatorio(array $dados, string $campo, string $rotulo): static
    {
        if (empty($dados[$campo]) && $dados[$campo] !== '0') {
            $this->erros[] = "{$rotulo} é obrigatório.";
        }
        return $this;
    }

    public function email(array $dados, string $campo, string $rotulo): static
    {
        if (!empty($dados[$campo]) && !filter_var($dados[$campo], FILTER_VALIDATE_EMAIL)) {
            $this->erros[] = "{$rotulo} precisa ser um e-mail válido.";
        }
        return $this;
    }

    public function tamanhoMinimo(array $dados, string $campo, int $minimo, string $rotulo): static
    {
        if (!empty($dados[$campo]) && strlen((string) $dados[$campo]) < $minimo) {
            $this->erros[] = "{$rotulo} precisa ter pelo menos {$minimo} caracteres.";
        }
        return $this;
    }

    public function dentroDaLista(array $dados, string $campo, array $lista, string $rotulo): static
    {
        if (!empty($dados[$campo]) && !in_array($dados[$campo], $lista, true)) {
            $this->erros[] = "{$rotulo} inválido.";
        }
        return $this;
    }

    public function valido(): bool
    {
        return empty($this->erros);
    }

    public function erros(): array
    {
        return $this->erros;
    }
}
