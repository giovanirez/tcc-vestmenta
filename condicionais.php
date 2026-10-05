<?php $page_css = 'vendas.css'; $page_js = 'condicionais.js'; include 'includes/header.php'; ?>

<div class="card">
    <h2><i class="fa-solid fa-bag-shopping"></i> Nova Condicional</h2>
    <p style="color: var(--ink-muted); margin-top: 5px;">As peças saem do estoque enquanto estão com o cliente e voltam se forem devolvidas.</p>

    <form id="cond-form" style="margin-top: 15px;" novalidate>
        <div class="form-group" style="max-width: 420px;">
            <label>Cliente</label>
            <select class="form-control" id="cond-cliente">
                <option value="">Selecione o cliente...</option>
            </select>
        </div>

        <div class="pdv-scan">
            <label style="display: block; margin-bottom: 10px; font-weight: 500;"><i class="fa-solid fa-barcode"></i> Adicionar Peça</label>
            <div class="item-add-grid" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" class="form-control" id="cond-produto-busca" list="cond-produtos" autocomplete="off" placeholder="Código ou nome do produto..." style="flex: 1; min-width: 180px;">
                <datalist id="cond-produtos"></datalist>
                <input type="number" class="form-control" id="cond-produto-qtd" value="1" min="1" style="width: 80px;" title="Quantidade">
                <button type="button" class="btn" id="cond-adicionar-btn" style="margin-top: 0;">Adicionar</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-stack-mobile" style="margin-top: 0;">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Vlr. Unit.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cond-itens">
                    <tr><td colspan="4" class="text-muted">Nenhuma peça adicionada.</td></tr>
                </tbody>
            </table>
        </div>

        <p id="cond-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>
        <button type="submit" class="btn" id="cond-submit-btn"><i class="fa-solid fa-truck-fast"></i> Registrar Saída</button>
    </form>
</div>

<div class="card" id="fin-card" style="display: none;">
    <h2><i class="fa-solid fa-cash-register"></i> <span id="fin-titulo">Finalizar Venda</span></h2>
    <p class="text-muted" style="margin-top: 5px; font-size: 0.85rem;">Informe quantas peças o cliente ficou. O que não for comprado volta pro estoque automaticamente.</p>

    <div class="table-responsive" style="margin-top: 15px;">
        <table class="table-stack-mobile" style="margin-top: 0;">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Levou</th>
                    <th>Comprou</th>
                    <th>Vlr. Unit.</th>
                    <th>% Desc.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="fin-itens"></tbody>
        </table>
    </div>

    <div class="form-grid" style="margin-top: 15px;">
        <div class="form-group">
            <label>Desconto Geral (%)</label>
            <input type="number" class="form-control" id="fin-desconto-geral" value="0" min="0" max="100" step="0.01">
        </div>
        <div class="form-group">
            <label>Forma de Pagamento</label>
            <select class="form-control" id="fin-forma-pagamento">
                <option value="PIX">PIX</option>
                <option value="Cartão de Crédito">Cartão de Crédito</option>
                <option value="Cartão de Débito">Cartão de Débito</option>
                <option value="Dinheiro">Dinheiro</option>
                <option value="Fiado">Fiado (Crediário Próprio)</option>
            </select>
        </div>
        <div class="form-group" id="fin-cartao-detalhes" style="display: none;">
            <label>Parcelado em</label>
            <select class="form-control" id="fin-parcelas-cartao">
                <option value="1">À vista (1x)</option>
                <option value="2">2x</option>
                <option value="3">3x</option>
                <option value="4">4x</option>
                <option value="5">5x</option>
                <option value="6">6x</option>
            </select>
        </div>
        <div class="form-group" id="fin-fiado-detalhes" style="display: none;">
            <label>Parcelar em</label>
            <select class="form-control" id="fin-parcelas">
                <option value="1">1x</option>
                <option value="2">2x</option>
                <option value="3" selected>3x</option>
                <option value="4">4x</option>
            </select>
        </div>
    </div>

    <div style="text-align: right;">
        <h3 style="margin-bottom: 15px;">Total: <span class="text-moss mono-value" id="fin-total">R$ 0,00</span></h3>
        <p id="fin-mensagem" class="text-rust" style="display: none; margin-bottom: 10px; font-size: 0.85rem;"></p>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn btn-outline" id="fin-cancelar-btn">Voltar</button>
            <button type="button" class="btn" id="fin-confirmar-btn"><i class="fa-solid fa-check"></i> Confirmar Venda</button>
        </div>
    </div>
</div>

<div class="card">
    <h2><i class="fa-solid fa-person-booth"></i> Gestão de Condicionais (Malotes)</h2>
    <p style="color: var(--ink-muted); margin-top: 5px;">Controle de peças enviadas para experimentação dos clientes.</p>
    <p id="cond-lista-mensagem" class="text-moss" style="display: none; margin-top: 10px; font-size: 0.9rem;"></p>

    <div class="table-responsive" style="margin-top: 20px;">
        <table>
            <thead>
                <tr>
                    <th>Cód</th>
                    <th>Data Saída</th>
                    <th>Cliente</th>
                    <th>Peças</th>
                    <th>Status</th>
                    <th>Conclusão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="cond-lista">
                <tr><td colspan="7" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
