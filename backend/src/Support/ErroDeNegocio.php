<?php

namespace App\Support;

// Erro "esperado" de regra de negócio (estoque insuficiente, limite de
// fiado estourado...). Diferente de Response::erro(), que encerra o
// script na hora, isto aqui é uma exceção: se acontecer dentro de
// Database::transacao(), a transação é desfeita antes de responder.
// O index.php captura e devolve a mensagem com o status HTTP dado.
class ErroDeNegocio extends \RuntimeException
{
    public function __construct(string $mensagem, private int $status = 422)
    {
        parent::__construct($mensagem);
    }

    public function status(): int
    {
        return $this->status;
    }
}
