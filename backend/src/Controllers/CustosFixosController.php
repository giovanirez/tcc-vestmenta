<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\CustoFixoRepository;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

class CustosFixosController
{
    public function listar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);
        Response::json((new CustoFixoRepository())->listarTodos());
    }

    public function criar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $dados = $requisicao->todaEntrada();
        $this->validar($dados);

        Response::json((new CustoFixoRepository())->criar($dados), 201);
    }

    public function atualizar(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $repositorio = new CustoFixoRepository();
        if (!$repositorio->buscarPorId((int) $id)) {
            Response::erro('Custo fixo não encontrado.', 404);
        }

        $dados = $requisicao->todaEntrada();
        $this->validar($dados);

        Response::json($repositorio->atualizar((int) $id, $dados));
    }

    public function alternarAtivo(Request $requisicao, string $id): void
    {
        Auth::exigirLogin($requisicao);

        $repositorio = new CustoFixoRepository();
        $custo = $repositorio->buscarPorId((int) $id);
        if (!$custo) {
            Response::erro('Custo fixo não encontrado.', 404);
        }

        Response::json($repositorio->alternarAtivo((int) $id, !$custo['ativo']));
    }

    private function validar(array $dados): void
    {
        $validador = (new Validator())
            ->obrigatorio($dados, 'nome', 'Nome')
            ->obrigatorio($dados, 'valor', 'Valor')
            ->obrigatorio($dados, 'dia_vencimento', 'Dia de vencimento')
            ->dentroDaLista($dados, 'categoria', ['Ocupação', 'Utilidades', 'Pessoal', 'Serviços', 'Outros'], 'Categoria');
        $erros = $validador->erros();

        if (isset($dados['valor']) && (!is_numeric($dados['valor']) || $dados['valor'] <= 0)) {
            $erros[] = 'Valor precisa ser maior que zero.';
        }
        $dia = filter_var($dados['dia_vencimento'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]);
        if (!empty($dados['dia_vencimento']) && $dia === false) {
            $erros[] = 'Dia de vencimento precisa ser entre 1 e 31.';
        }

        if ($erros) {
            Response::erro(implode(' ', $erros), 422);
        }
    }
}
