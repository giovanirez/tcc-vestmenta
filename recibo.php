<?php
require 'includes/mock-vendas.php';

$id = isset($_GET['venda']) ? (int) $_GET['venda'] : 0;
$venda = $vendas[$id] ?? null;

// A folha impressa é sempre A4 normal — a maioria das impressoras e
// diálogos de impressão ignora tamanho de papel customizado e cai pra
// A4 de qualquer jeito. O que muda de tamanho é o BLOCO do recibo
// dentro da folha: 105mm de largura (1/4 da largura de A4... na
// verdade metade; a altura é que fecha o 1/4 de área) fixos, com a
// altura crescendo conforme a quantidade de itens. Ele fica ancorado
// no topo da página — é isso que permite, no futuro, encaixar um
// segundo recibo do lado ou embaixo, na mesma folha A4.
$LARGURA_MM = 105;
$ALTURA_BASE_MM = 148.5;
$ITENS_QUE_CABEM_NA_BASE = 6;
$ALTURA_POR_ITEM_EXTRA_MM = 9;

$qtd_itens = $venda ? count($venda['itens']) : 0;
$itens_extras = max(0, $qtd_itens - $ITENS_QUE_CABEM_NA_BASE);
$altura_mm = $ALTURA_BASE_MM + ($itens_extras * $ALTURA_POR_ITEM_EXTRA_MM);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo <?= $venda ? '#' . $id : '' ?> · ModaSys</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <style>
        @page { size: A4 portrait; margin: 8mm; }

        body { background: #d8d5cc; padding: 24px 0; display: flex; flex-direction: column; align-items: center; }

        .recibo {
            width: <?= $LARGURA_MM ?>mm;
            min-height: <?= $altura_mm ?>mm;
            background: var(--surface);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 6mm;
            font-size: 0.78rem;
        }

        .recibo-cabecalho { text-align: center; border-bottom: 1.5px solid var(--ink); padding-bottom: 8px; margin-bottom: 10px; }
        .recibo-marca { font-family: var(--font-display); font-size: 1.15rem; font-weight: 600; }
        .recibo-marca::before { content: ""; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--thread); margin-right: 6px; vertical-align: middle; }
        .recibo-rotulo { font-family: var(--font-mono); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-muted); margin-top: 4px; }
        .recibo-rotulo strong { color: var(--ink); }

        .recibo-meta { font-size: 0.75rem; margin-bottom: 10px; }
        .recibo-meta div { display: flex; justify-content: space-between; gap: 8px; padding: 1px 0; }
        .recibo-meta span:first-child { color: var(--ink-muted); }

        .recibo-linha-pontilhada { border-top: 1px dashed var(--border-color); margin: 8px 0; }

        .recibo-item { margin-bottom: 7px; }
        .recibo-item .nome { font-size: 0.78rem; }
        .recibo-item .calculo { display: flex; justify-content: space-between; font-family: var(--font-mono); font-size: 0.72rem; color: var(--ink-muted); }
        .recibo-item .calculo strong { color: var(--ink); font-weight: 600; }
        .recibo-item .desconto-item { font-size: 0.68rem; color: var(--rust); }

        .recibo-totais div { display: flex; justify-content: space-between; padding: 2px 0; font-size: 0.78rem; }
        .recibo-totais .total-final { border-top: 1.5px solid var(--ink); margin-top: 6px; padding-top: 6px; font-family: var(--font-display); font-size: 1rem; }

        .recibo-rodape { margin-top: 14px; text-align: center; color: var(--ink-muted); font-size: 0.62rem; line-height: 1.4; }

        .acoes-tela { margin: 0 auto 16px; display: flex; gap: 10px; justify-content: center; }

        @media print {
            html, body { display: block; background: none; padding: 0; margin: 0; }
            .recibo { box-shadow: none; margin: 0; padding: 5mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<?php if (!$venda): ?>

    <div class="recibo" style="text-align: center;">
        <p><i class="fa-solid fa-circle-exclamation text-rust"></i> Recibo não encontrado para o pedido #<?= $id ?>.</p>
    </div>

<?php else: ?>

    <div class="no-print acoes-tela">
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
        <button class="btn btn-outline" onclick="window.close()">Fechar</button>
    </div>

    <div class="recibo">
        <div class="recibo-cabecalho">
            <div class="recibo-marca">ModaSys</div>
            <div class="recibo-rotulo">Comprovante de Venda <strong>#<?= $id ?></strong></div>
        </div>

        <div class="recibo-meta">
            <div><span>Data</span><span><?= date('d/m/Y H:i', strtotime($venda['data'])) ?></span></div>
            <div><span>Cliente</span><span><?= $venda['cliente'] ?></span></div>
            <div>
                <span>Pagamento</span>
                <span><?= $venda['pagamento'] ?><?php if (!empty($venda['parcelas_cartao'])): ?> (<?= $venda['parcelas_cartao'] ?>x)<?php endif; ?></span>
            </div>
        </div>

        <div class="recibo-linha-pontilhada"></div>

        <?php foreach ($venda['itens'] as $item): ?>
        <div class="recibo-item">
            <div class="nome"><?= $item['produto'] ?></div>
            <div class="calculo">
                <span><?= $item['qtd'] ?> x R$ <?= number_format($item['preco'], 2, ',', '.') ?></span>
                <strong>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></strong>
            </div>
            <?php if ($item['desconto_item'] > 0): ?>
            <div class="desconto-item">desconto: -R$ <?= number_format($item['desconto_item'], 2, ',', '.') ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="recibo-linha-pontilhada"></div>

        <div class="recibo-totais">
            <div><span>Subtotal</span><span class="mono-value">R$ <?= number_format($venda['subtotal'], 2, ',', '.') ?></span></div>
            <?php if ($venda['desconto'] > 0): ?>
            <div><span>Desconto</span><span class="mono-value text-rust">- R$ <?= number_format($venda['desconto'], 2, ',', '.') ?></span></div>
            <?php endif; ?>
            <div class="total-final"><span>Total</span><span class="mono-value"><strong>R$ <?= number_format($venda['total'], 2, ',', '.') ?></strong></span></div>
        </div>

        <div class="recibo-rodape">
            Comprovante gerado pelo ModaSys em <?= date('d/m/Y \à\s H:i') ?><br>
            não possui valor fiscal.
        </div>
    </div>

<?php endif; ?>

</body>
</html>
