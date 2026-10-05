<?php

namespace App\Services;

use App\Config\Database;
use App\Repositories\ClienteRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;
use App\Support\ErroDeNegocio;

// Regras de venda num lugar só. Fica fora do controller porque duas
// telas fecham venda: o PDV (vendas.php) e o "Finalizar Venda" das
// condicionais — as duas precisam exatamente das mesmas regras.
class VendaService
{
    public const FORMAS_PAGAMENTO = ['PIX', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro', 'Fiado'];
    public const MAX_PARCELAS_CARTAO = 12;
    public const MAX_PARCELAS_FIADO = 12;

    // $dados:
    //   cliente_id          int|null (null = Cliente Balcão)
    //   forma_pagamento     uma de FORMAS_PAGAMENTO
    //   parcelas_cartao     int, só p/ Cartão de Crédito
    //   parcelas            int, só p/ Fiado
    //   desconto_percentual % de desconto geral da venda
    //   itens               [{produto_id, quantidade, desconto_percentual}]
    //
    // O cliente manda só percentuais e quantidades — preço vem sempre do
    // cadastro do produto, e os valores em reais são calculados aqui.
    // Assim ninguém consegue vender por um preço inventado mandando a
    // requisição direto pra API.
    public function registrar(array $dados, ?int $usuarioId, ?int $condicionalId = null): int
    {
        $this->validarEntrada($dados);

        return Database::transacao(function () use ($dados, $usuarioId, $condicionalId) {
            $vendas = new VendaRepository();
            $formaPagamento = $dados['forma_pagamento'];
            $clienteId = !empty($dados['cliente_id']) ? (int) $dados['cliente_id'] : null;

            if ($clienteId !== null && !(new ClienteRepository())->buscarPorId($clienteId)) {
                throw new ErroDeNegocio('Cliente não encontrado.', 404);
            }

            [$itens, $subtotal] = $this->calcularItens($dados['itens']);

            $descontoGeral = round($subtotal * $this->percentual($dados['desconto_percentual'] ?? 0) / 100, 2);
            $total = round($subtotal - $descontoGeral, 2);

            $qtdParcelas = 0;
            if ($formaPagamento === 'Fiado') {
                $qtdParcelas = (int) ($dados['parcelas'] ?? 1);
                $this->validarFiado($clienteId, $total, $vendas);
            }

            $vendaId = $vendas->criar([
                'cliente_id' => $clienteId,
                'condicional_id' => $condicionalId,
                'usuario_id' => $usuarioId,
                'forma_pagamento' => $formaPagamento,
                'parcelas_cartao' => $formaPagamento === 'Cartão de Crédito' ? (int) ($dados['parcelas_cartao'] ?? 1) : null,
                'valor_subtotal' => $subtotal,
                'valor_desconto' => $descontoGeral,
                'valor_total' => $total,
            ]);

            foreach ($itens as $item) {
                $vendas->adicionarItem($vendaId, $item);
            }

            foreach ($this->gerarParcelas($total, $qtdParcelas) as $numero => $parcela) {
                $vendas->adicionarParcela($vendaId, $numero, $parcela['valor'], $parcela['vencimento']);
            }

            (new DocumentoRepository())->registrar(
                'Saída',
                'Venda',
                $vendaId,
                date('Y-m-d'),
                array_map(fn ($item) => ['produto_id' => $item['produto_id'], 'quantidade' => $item['quantidade']], $itens)
            );

            return $vendaId;
        });
    }

    // Cancelar devolve as peças pro estoque (documento de Entrada com
    // origem "Venda", apontando pra venda cancelada) e apaga as parcelas
    // de fiado. Não deixa cancelar se alguma parcela já foi paga — aí
    // existe dinheiro recebido que precisaria de estorno manual.
    public function cancelar(int $vendaId): void
    {
        Database::transacao(function () use ($vendaId) {
            $vendas = new VendaRepository();
            $venda = $vendas->buscarPorId($vendaId, true);

            if (!$venda) {
                throw new ErroDeNegocio('Venda não encontrada.', 404);
            }
            if ($venda['status'] === 'Cancelada') {
                throw new ErroDeNegocio('Essa venda já está cancelada.', 409);
            }
            foreach ($venda['parcelas'] as $parcela) {
                if ($parcela['status'] === 'Paga') {
                    throw new ErroDeNegocio('Essa venda tem parcela de fiado já paga — não dá pra cancelar automaticamente.', 409);
                }
            }

            $vendas->cancelar($vendaId);

            (new DocumentoRepository())->registrar(
                'Entrada',
                'Venda',
                $vendaId,
                date('Y-m-d'),
                array_map(fn ($item) => ['produto_id' => (int) $item['produto_id'], 'quantidade' => (int) $item['quantidade']], $venda['itens']),
                "Cancelamento da venda #{$vendaId}"
            );
        });
    }

    private function validarEntrada(array $dados): void
    {
        $erros = [];
        $forma = $dados['forma_pagamento'] ?? null;

        if (!in_array($forma, self::FORMAS_PAGAMENTO, true)) {
            $erros[] = 'Forma de pagamento inválida.';
        }
        if ($forma === 'Fiado' && empty($dados['cliente_id'])) {
            $erros[] = 'Venda fiado exige um cliente identificado.';
        }
        if ($forma === 'Fiado' && !$this->inteiroEntre($dados['parcelas'] ?? 1, 1, self::MAX_PARCELAS_FIADO)) {
            $erros[] = 'Número de parcelas do fiado inválido.';
        }
        if ($forma === 'Cartão de Crédito' && !$this->inteiroEntre($dados['parcelas_cartao'] ?? 1, 1, self::MAX_PARCELAS_CARTAO)) {
            $erros[] = 'Número de parcelas do cartão inválido.';
        }
        if (!$this->percentualValido($dados['desconto_percentual'] ?? 0)) {
            $erros[] = 'Desconto geral precisa ficar entre 0% e 100%.';
        }

        $itens = $dados['itens'] ?? null;
        if (!is_array($itens) || !$itens) {
            $erros[] = 'Adicione pelo menos um produto à venda.';
        } else {
            foreach (array_values($itens) as $posicao => $item) {
                $linha = $posicao + 1;
                if (empty($item['produto_id'])) {
                    $erros[] = "Item {$linha}: produto não informado.";
                }
                if (!$this->inteiroEntre($item['quantidade'] ?? 0, 1, PHP_INT_MAX)) {
                    $erros[] = "Item {$linha}: quantidade precisa ser pelo menos 1.";
                }
                if (!$this->percentualValido($item['desconto_percentual'] ?? 0)) {
                    $erros[] = "Item {$linha}: desconto precisa ficar entre 0% e 100%.";
                }
            }
        }

        if ($erros) {
            throw new ErroDeNegocio(implode(' ', $erros), 422);
        }
    }

    // Trava os produtos, confere ativo/estoque e calcula os valores de
    // cada item: valor_total = quantidade * preço - desconto (em reais).
    private function calcularItens(array $itensRecebidos): array
    {
        $ids = array_unique(array_map(fn ($item) => (int) $item['produto_id'], $itensRecebidos));
        $produtos = (new ProdutoRepository())->buscarParaMovimentar($ids);

        // O mesmo produto pode aparecer em duas linhas do carrinho — o
        // estoque precisa ser conferido contra a soma das duas.
        $qtdPorProduto = [];
        foreach ($itensRecebidos as $item) {
            $id = (int) $item['produto_id'];
            $qtdPorProduto[$id] = ($qtdPorProduto[$id] ?? 0) + (int) $item['quantidade'];
        }

        foreach ($qtdPorProduto as $id => $quantidade) {
            $produto = $produtos[$id] ?? null;
            if (!$produto) {
                throw new ErroDeNegocio("Produto #{$id} não encontrado.", 404);
            }
            if (!$produto['ativo']) {
                throw new ErroDeNegocio("\"{$produto['nome']}\" está desativado e não pode ser vendido.", 409);
            }
            if ((int) $produto['estoque_atual'] < $quantidade) {
                throw new ErroDeNegocio("Estoque insuficiente de \"{$produto['nome']}\": há {$produto['estoque_atual']} un.", 409);
            }
        }

        $itens = [];
        $subtotal = 0.0;
        foreach ($itensRecebidos as $item) {
            $produto = $produtos[(int) $item['produto_id']];
            $quantidade = (int) $item['quantidade'];
            $preco = (float) $produto['preco_venda'];
            $bruto = round($quantidade * $preco, 2);
            $desconto = round($bruto * $this->percentual($item['desconto_percentual'] ?? 0) / 100, 2);

            $itens[] = [
                'produto_id' => (int) $produto['id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $preco,
                'valor_desconto' => $desconto,
                'valor_total' => round($bruto - $desconto, 2),
            ];
            $subtotal += $bruto - $desconto;
        }

        return [$itens, round($subtotal, 2)];
    }

    // Fiado só pra cliente com limite_credito definido, e a dívida em
    // aberto + esta venda não pode passar desse limite.
    private function validarFiado(?int $clienteId, float $total, VendaRepository $vendas): void
    {
        $cliente = (new ClienteRepository())->buscarPorId($clienteId);

        if ($cliente['limite_credito'] === null) {
            throw new ErroDeNegocio("{$cliente['nome']} não tem fiado liberado (sem limite de crédito no cadastro).", 409);
        }
        if ($total <= 0) {
            throw new ErroDeNegocio('Venda fiado precisa ter valor maior que zero.', 422);
        }

        $limite = (float) $cliente['limite_credito'];
        $emAberto = $vendas->dividaEmAberto($clienteId);
        if ($emAberto + $total > $limite) {
            $disponivel = number_format(max(0, $limite - $emAberto), 2, ',', '.');
            throw new ErroDeNegocio("Limite de fiado de {$cliente['nome']} estourado: disponível R$ {$disponivel}.", 409);
        }
    }

    // Divide em parcelas mensais. Os centavos que sobram da divisão vão
    // pra última parcela (ex.: 100 / 3 = 33,33 + 33,33 + 33,34), assim a
    // soma das parcelas sempre bate exatamente com o total da venda.
    private function gerarParcelas(float $total, int $quantidade): array
    {
        if ($quantidade < 1) {
            return [];
        }

        $valorBase = floor($total * 100 / $quantidade) / 100;
        $hoje = new \DateTimeImmutable('today');
        $parcelas = [];

        for ($numero = 1; $numero <= $quantidade; $numero++) {
            $valor = $numero === $quantidade ? round($total - $valorBase * ($quantidade - 1), 2) : $valorBase;
            $parcelas[$numero] = ['valor' => $valor, 'vencimento' => $this->somarMeses($hoje, $numero)];
        }

        return $parcelas;
    }

    // +1 mês a partir de 31/01 cai em 28/02 (ou 29), não em 03/03 como
    // faria o modify('+1 month') puro do PHP.
    private function somarMeses(\DateTimeImmutable $data, int $meses): string
    {
        $primeiroDoMes = $data->modify('first day of this month')->modify("+{$meses} month");
        $dia = min((int) $data->format('d'), (int) $primeiroDoMes->format('t'));
        return $primeiroDoMes->setDate((int) $primeiroDoMes->format('Y'), (int) $primeiroDoMes->format('m'), $dia)->format('Y-m-d');
    }

    private function percentual(mixed $valor): float
    {
        return is_numeric($valor) ? (float) $valor : 0.0;
    }

    private function percentualValido(mixed $valor): bool
    {
        return $valor === null || $valor === '' || (is_numeric($valor) && $valor >= 0 && $valor <= 100);
    }

    private function inteiroEntre(mixed $valor, int $minimo, int $maximo): bool
    {
        return filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimo, 'max_range' => $maximo]]) !== false;
    }
}
