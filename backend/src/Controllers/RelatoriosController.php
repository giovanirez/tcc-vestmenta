<?php

namespace App\Controllers;

use App\Middleware\Auth;
use App\Repositories\CustoFixoRepository;
use App\Repositories\RelatorioRepository;
use App\Support\Request;
use App\Support\Response;
use DateTimeImmutable;

class RelatoriosController
{
    // Com esse saldo ou menos, o produto entra no cartão "Produtos em Baixa".
    private const ESTOQUE_BAIXO = 3;

    public function dashboard(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $inicioMes = new DateTimeImmutable('first day of this month 00:00');
        $inicio = $inicioMes->format('Y-m-d H:i:sP');
        $fim = $inicioMes->modify('+1 month')->format('Y-m-d H:i:sP');

        $relatorios = new RelatorioRepository();
        $resumo = $relatorios->resumoVendas($inicio, $fim);

        Response::json([
            'vendas_mes' => $resumo['faturamento'],
            'qtd_vendas_mes' => $resumo['qtd_vendas'],
            'condicionais_abertas' => $relatorios->contarCondicionaisAbertas(),
            'estoque_baixo_limite' => self::ESTOQUE_BAIXO,
            'produtos_baixa' => $relatorios->produtosComEstoqueBaixo(self::ESTOQUE_BAIXO),
            'novos_clientes' => $relatorios->contarClientesNovos($inicio, $fim),
            'ultimas_vendas' => $relatorios->ultimasVendas(8),
        ]);
    }

    // GET /relatorios?inicio=AAAA-MM-DD&fim=AAAA-MM-DD (fim inclusivo pra
    // quem usa; internamente vira o dia seguinte, exclusivo).
    public function gerar(Request $requisicao): void
    {
        Auth::exigirLogin($requisicao);

        $inicioDia = $this->data((string) $requisicao->query('inicio', ''));
        $fimDia = $this->data((string) $requisicao->query('fim', ''));

        if (!$inicioDia || !$fimDia) {
            Response::erro('Informe data inicial e final válidas.', 422);
        }
        if ($inicioDia > $fimDia) {
            Response::erro('A data inicial não pode ser depois da final.', 422);
        }
        if ($inicioDia->diff($fimDia)->days > 366 * 3) {
            Response::erro('Período muito longo — use no máximo 3 anos.', 422);
        }

        $inicio = $inicioDia->format('Y-m-d H:i:sP');
        $fim = $fimDia->modify('+1 day')->format('Y-m-d H:i:sP');
        $relatorios = new RelatorioRepository();

        Response::json([
            'periodo' => ['inicio' => $inicioDia->format('Y-m-d'), 'fim' => $fimDia->format('Y-m-d')],
            'resumo' => $relatorios->resumoVendas($inicio, $fim),
            'custos_fixos_mensais' => (new CustoFixoRepository())->totalMensalAtivo(),
            'por_categoria' => $relatorios->vendasPorCategoria($inicio, $fim),
            'por_pagamento' => $relatorios->vendasPorPagamento($inicio, $fim),
            'top_produtos' => $relatorios->topProdutos($inicio, $fim),
            'top_clientes' => $relatorios->topClientes($inicio, $fim),
        ]);
    }

    private function data(string $valor): ?DateTimeImmutable
    {
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $data && $data->format('Y-m-d') === $valor ? $data : null;
    }
}
