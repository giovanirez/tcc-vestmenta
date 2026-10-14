<?php

namespace App\Support;

// Monta um INSERT de várias linhas de uma vez:
//   INSERT INTO t (a, b) VALUES (?, ?), (?, ?), (?, ?)
// em vez de um INSERT por linha. A API fica longe do banco (EUA x São
// Paulo), então cada ida ao banco custa caro — com isto, uma entrada de
// 30 itens grava os itens numa ida só, não em 30.
class Lote
{
    // $linhas: lista de arrays associativos; $colunas: quais chaves usar,
    // na ordem. $tipos (opcional): "coluna" => "bigint" pra forçar o tipo
    // do parâmetro (útil dentro de VALUES, onde o Postgres não sabe o tipo).
    // Devolve [ "(?, ?), (?, ?)", [valores...] ].
    public static function valores(array $linhas, array $colunas, array $tipos = []): array
    {
        $marcadores = [];
        $parametros = [];

        foreach ($linhas as $linha) {
            $campos = [];
            foreach ($colunas as $coluna) {
                $campos[] = isset($tipos[$coluna]) ? "CAST(? AS {$tipos[$coluna]})" : '?';
                $parametros[] = $linha[$coluna] ?? null;
            }
            $marcadores[] = '(' . implode(', ', $campos) . ')';
        }

        return [implode(', ', $marcadores), $parametros];
    }
}
