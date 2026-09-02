<?php
// Dados Mockados - Vendas, com itens detalhados.
// Compartilhado entre dashboard.php, vendas.php e recibo.php, pra não
// existir dois lugares com números diferentes pro mesmo pedido.
$vendas = [
    1042 => [
        'data' => '2026-03-17 14:30',
        'cliente' => 'Mariana Oliveira',
        'pagamento' => 'Cartão de Crédito',
        'parcelas_cartao' => 3,
        'desconto' => 0,
        'status' => 'Concluída',
        'itens' => [
            ['produto' => 'Vestido Floral Verão', 'qtd' => 1, 'preco' => 159.90, 'desconto_item' => 0],
            ['produto' => 'Cinto Fino Couro',      'qtd' => 2, 'preco' => 49.95,  'desconto_item' => 0],
        ],
    ],
    1041 => [
        'data' => '2026-03-17 10:15',
        'cliente' => 'Cliente Balcão',
        'pagamento' => 'PIX',
        'parcelas_cartao' => null,
        'desconto' => 0,
        'status' => 'Concluída',
        'itens' => [
            ['produto' => 'Camiseta Básica de Algodão', 'qtd' => 1, 'preco' => 49.90, 'desconto_item' => 0],
        ],
    ],
    1040 => [
        'data' => '2026-03-16 16:45',
        'cliente' => 'Carlos Mendes',
        'pagamento' => 'Cartão de Débito',
        'parcelas_cartao' => null,
        'desconto' => 16.81,
        'status' => 'Concluída',
        'itens' => [
            ['produto' => 'Calça Jeans Skinny',  'qtd' => 1, 'preco' => 129.90, 'desconto_item' => 12.99],
            ['produto' => 'Jaqueta de Couro PU',  'qtd' => 1, 'preco' => 249.90, 'desconto_item' => 0],
        ],
    ],
    1039 => [
        'data' => '2026-03-16 17:20',
        'cliente' => 'Carlos Mendes',
        'pagamento' => 'Fiado',
        'parcelas_cartao' => null,
        'desconto' => 0,
        'status' => 'Concluída',
        'itens' => [
            ['produto' => 'Camiseta Básica de Algodão', 'qtd' => 6, 'preco' => 49.90, 'desconto_item' => 0],
        ],
    ],
    1038 => [
        'data' => '2026-03-14 09:50',
        'cliente' => 'Ana Paula Silva',
        'pagamento' => 'PIX',
        'parcelas_cartao' => null,
        'desconto' => 0,
        'status' => 'Concluída',
        // Compra grande de propósito: prova que o recibo cresce além
        // do 1/4 de página quando tem mais itens que o normal.
        'itens' => [
            ['produto' => 'Camiseta Básica de Algodão', 'qtd' => 1, 'preco' => 49.90,  'desconto_item' => 0],
            ['produto' => 'Calça Jeans Skinny',         'qtd' => 1, 'preco' => 129.90, 'desconto_item' => 0],
            ['produto' => 'Jaqueta de Couro PU',        'qtd' => 1, 'preco' => 249.90, 'desconto_item' => 0],
            ['produto' => 'Vestido Floral Verão',       'qtd' => 1, 'preco' => 159.90, 'desconto_item' => 0],
            ['produto' => 'Cinto Fino Couro',           'qtd' => 1, 'preco' => 49.95,  'desconto_item' => 0],
            ['produto' => 'Camisa Social Azul',         'qtd' => 1, 'preco' => 89.90,  'desconto_item' => 0],
            ['produto' => 'Saia Plissada',              'qtd' => 1, 'preco' => 99.90,  'desconto_item' => 0],
        ],
    ],
];

// Deriva subtotal/total a partir dos itens, pra nunca existir um
// número digitado duas vezes que possa ficar dessincronizado.
foreach ($vendas as $id => &$venda) {
    $subtotal = 0;
    foreach ($venda['itens'] as &$item) {
        $item['subtotal'] = ($item['qtd'] * $item['preco']) - $item['desconto_item'];
        $subtotal += $item['subtotal'];
    }
    unset($item);
    $venda['subtotal'] = $subtotal;
    $venda['total'] = $subtotal - $venda['desconto'];
}
unset($venda);
