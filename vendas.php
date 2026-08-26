<?php $page_css = 'vendas.css'; include 'includes/header.php'; ?>

<?php
// Dados Mockados - Histórico de Vendas
$vendas = [
    ['id' => 1042, 'data' => '2026-03-17 14:30', 'cliente' => 'Mariana Oliveira', 'itens' => '1x Vestido Floral, 2x Cinto Fino', 'pagamento' => 'Cartão de Crédito', 'total' => 259.80],
    ['id' => 1041, 'data' => '2026-03-17 10:15', 'cliente' => 'Cliente Balcão', 'itens' => '1x Camiseta Básica', 'pagamento' => 'PIX', 'total' => 49.90],
    ['id' => 1040, 'data' => '2026-03-16 16:45', 'cliente' => 'Carlos Mendes', 'itens' => '1x Calça Jeans Skinny, 1x Jaqueta PU', 'pagamento' => 'Cartão de Débito', 'total' => 379.80]
];
?>

<div class="pdv-layout">

    <div class="card">
        <h2><i class="fa-solid fa-cart-arrow-down"></i> Frente de Caixa (PDV)</h2>

        <form action="#" method="POST" style="margin-top: 15px;">
            <div class="form-group">
                <label>Cliente</label>
                <div style="display: flex; gap: 10px;">
                    <select class="form-control" style="flex: 1;">
                        <option>Cliente Balcão (Não Identificado)</option>
                        <option>Mariana Oliveira</option>
                        <option>Carlos Mendes</option>
                    </select>
                    <button type="button" class="btn-icon" title="Novo Cliente" style="background: var(--paper); padding: 0 15px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"><i class="fa-solid fa-user-plus"></i></button>
                </div>
            </div>

            <div class="pdv-scan">
                <label style="display: block; margin-bottom: 10px; font-weight: 500;"><i class="fa-solid fa-barcode"></i> Lançar Produto</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" class="form-control" placeholder="Código de barras ou nome do produto..." style="flex: 1;">
                    <input type="number" class="form-control" value="1" min="1" style="width: 80px;" title="Quantidade">
                    <button type="button" class="btn" style="margin-top: 0;">Adicionar</button>
                </div>
            </div>

            <div class="table-responsive" style="margin-bottom: 0;">
                <table style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Vlr. Unit.</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Vestido Floral Verão</td>
                            <td class="mono">1</td>
                            <td class="mono">R$ 159,90</td>
                            <td class="mono">R$ 159,90</td>
                            <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                        <tr>
                            <td>Cinto Fino Couro</td>
                            <td class="mono">2</td>
                            <td class="mono">R$ 49,95</td>
                            <td class="mono">R$ 99,90</td>
                            <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <div class="card" style="display: flex; flex-direction: column;">
        <h2><i class="fa-solid fa-money-bill-wave"></i> Resumo</h2>

        <div style="flex: 1; margin-top: 20px;">
            <div class="pdv-summary-line">
                <span>Subtotal:</span>
                <span class="mono-value">R$ 259,80</span>
            </div>
            <div class="pdv-summary-line">
                <span>Desconto:</span>
                <span class="mono-value">R$ 0,00</span>
            </div>
            <div class="pdv-total-line">
                <span>Total</span>
                <span>R$ 259,80</span>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>Forma de Pagamento</label>
                <select class="form-control">
                    <option>PIX</option>
                    <option>Cartão de Crédito</option>
                    <option>Cartão de Débito</option>
                    <option>Dinheiro</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn" style="width: 100%;"><i class="fa-solid fa-check"></i> Finalizar Venda</button>
    </div>

</div>

<div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Vendas</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Itens</th>
                    <th>Pagamento</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($vendas as $venda): ?>
                <tr>
                    <td class="mono">#<?= $venda['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($venda['data'])) ?></td>
                    <td><strong><?= $venda['cliente'] ?></strong></td>
                    <td><small><?= $venda['itens'] ?></small></td>
                    <td><span class="badge badge-info"><?= $venda['pagamento'] ?></span></td>
                    <td class="mono"><strong>R$ <?= number_format($venda['total'], 2, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
