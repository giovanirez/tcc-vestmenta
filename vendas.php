<?php $page_css = 'vendas.css'; $page_js = 'vendas.js'; include 'includes/header.php'; ?>

<?php require 'includes/mock-vendas.php'; ?>

<div class="pdv-layout">

    <div class="card">
        <h2><i class="fa-solid fa-cart-arrow-down"></i> Frente de Caixa (PDV)</h2>

        <form action="#" method="POST" style="margin-top: 15px;">
            <div class="form-group">
                <label>Cliente</label>
                <div style="display: flex; gap: 10px;">
                    <select class="form-control" id="pdv-cliente" style="flex: 1;">
                        <option value="balcao">Cliente Balcão (Não Identificado)</option>
                        <option value="1">Mariana Oliveira</option>
                        <option value="2">Carlos Mendes</option>
                    </select>
                    <button type="button" class="btn-icon" title="Novo Cliente" style="background: var(--paper); padding: 0 15px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"><i class="fa-solid fa-user-plus"></i></button>
                </div>
                <small id="pdv-cliente-aviso" class="text-rust" style="display: none; margin-top: 6px;">Venda fiado exige um cliente identificado — selecione um cliente cadastrado.</small>
            </div>

            <div class="pdv-scan">
                <label style="display: block; margin-bottom: 10px; font-weight: 500;"><i class="fa-solid fa-barcode"></i> Lançar Produto</label>
                <div class="item-add-grid" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="text" class="form-control" placeholder="Código de barras ou nome do produto..." style="flex: 1; min-width: 180px;">
                    <input type="number" class="form-control" value="1" min="1" style="width: 80px;" title="Quantidade">
                    <button type="button" class="btn" style="margin-top: 0;">Adicionar</button>
                </div>
            </div>

            <div class="table-responsive" style="margin-bottom: 0;">
                <table class="table-stack-mobile" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Vlr. Unit.</th>
                            <th>% Desc.</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="pdv-carrinho">
                        <tr data-qtd="1" data-preco="159.90">
                            <td data-label="Produto">Vestido Floral Verão</td>
                            <td class="mono" data-label="Qtd">1</td>
                            <td class="mono" data-label="Vlr. Unit.">R$ 159,90</td>
                            <td data-label="% Desc."><input type="number" class="form-control item-desconto" value="0" min="0" max="100" step="0.01" style="width: 75px; padding: 6px 8px;"></td>
                            <td class="mono item-subtotal" data-label="Subtotal">R$ 159,90</td>
                            <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                        <tr data-qtd="2" data-preco="49.95">
                            <td data-label="Produto">Cinto Fino Couro</td>
                            <td class="mono" data-label="Qtd">2</td>
                            <td class="mono" data-label="Vlr. Unit.">R$ 49,95</td>
                            <td data-label="% Desc."><input type="number" class="form-control item-desconto" value="0" min="0" max="100" step="0.01" style="width: 75px; padding: 6px 8px;"></td>
                            <td class="mono item-subtotal" data-label="Subtotal">R$ 99,90</td>
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
                <span class="mono-value" id="pdv-subtotal">R$ 259,80</span>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label style="font-size: 0.85rem;">Desconto Geral da Venda (%)</label>
                <input type="number" class="form-control" id="pdv-desconto-geral" value="0" min="0" max="100" step="0.01" style="max-width: 120px;">
            </div>

            <div class="pdv-summary-line">
                <span>Valor do Desconto:</span>
                <span class="mono-value" id="pdv-valor-desconto">R$ 0,00</span>
            </div>
            <div class="pdv-total-line">
                <span>Total</span>
                <span id="pdv-total-valor">R$ 259,80</span>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>Forma de Pagamento</label>
                <select class="form-control" id="pdv-forma-pagamento">
                    <option>PIX</option>
                    <option value="Cartão de Crédito">Cartão de Crédito</option>
                    <option>Cartão de Débito</option>
                    <option>Dinheiro</option>
                    <option value="Fiado">Fiado (Crediário Próprio)</option>
                </select>
            </div>

            <div id="pdv-cartao-detalhes" class="form-group" style="display: none;">
                <label>Parcelado em</label>
                <select class="form-control" id="pdv-parcelas-cartao">
                    <option value="1">À vista (1x)</option>
                    <option value="2">2x</option>
                    <option value="3">3x</option>
                    <option value="4">4x</option>
                    <option value="5">5x</option>
                    <option value="6">6x</option>
                </select>
            </div>

            <div id="pdv-fiado-detalhes" style="display: none;">
                <div class="form-group">
                    <label>Parcelar em</label>
                    <select class="form-control" id="pdv-parcelas">
                        <option value="1">1x (à vista)</option>
                        <option value="2">2x</option>
                        <option value="3" selected>3x</option>
                        <option value="4">4x</option>
                    </select>
                </div>
                <div id="pdv-parcelas-preview" class="pdv-scan" style="font-size: 0.85rem;"></div>
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
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($vendas as $id => $venda): ?>
                <?php $itens_texto = implode(', ', array_map(fn($i) => "{$i['qtd']}x {$i['produto']}", $venda['itens'])); ?>
                <tr>
                    <td class="mono">#<?= $id ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($venda['data'])) ?></td>
                    <td><strong><?= $venda['cliente'] ?></strong></td>
                    <td><small><?= $itens_texto ?></small></td>
                    <td><span class="badge <?= $venda['pagamento'] === 'Fiado' ? 'badge-warning' : 'badge-info' ?>"><?= $venda['pagamento'] ?></span></td>
                    <td class="mono"><strong>R$ <?= number_format($venda['total'], 2, ',', '.') ?></strong></td>
                    <td>
                        <a class="btn-icon btn-icon--view" title="Imprimir Recibo" href="recibo.php?venda=<?= $id ?>" target="_blank" style="text-decoration: none;"><i class="fa-solid fa-file-invoice"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
