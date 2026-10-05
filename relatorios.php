<?php $page_css = 'relatorios.css'; $page_js = 'relatorios.js'; include 'includes/header.php'; ?>

<div class="card">
    <h2><i class="fa-solid fa-filter"></i> Filtros de Relatório</h2>
    <form id="rel-form" class="filter-bar" style="margin-top: 15px;" novalidate>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Data Inicial</label>
            <input type="date" class="form-control" id="rel-inicio">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Data Final</label>
            <input type="date" class="form-control" id="rel-fim">
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
            <label>Tipo de Relatório</label>
            <select class="form-control" id="rel-tipo">
                <option value="geral">Visão Geral de Vendas</option>
                <option value="produtos">Desempenho de Produtos</option>
                <option value="clientes">Histórico de Clientes</option>
            </select>
        </div>
        <button type="submit" class="btn" id="rel-gerar-btn" style="height: 40px;"><i class="fa-solid fa-search"></i> Gerar</button>
    </form>
    <p id="rel-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>
</div>

<!-- Visão geral -->
<div data-secao="geral">
    <div class="dashboard-cards">
        <div class="tag-card tag-card--moss">
            <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="kpi-info">
                <h3 id="rel-faturamento">R$ 0,00</h3>
                <p>Faturamento</p>
            </div>
        </div>
        <div class="tag-card tag-card--denim">
            <div class="kpi-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="kpi-info">
                <h3 id="rel-qtd-vendas">0</h3>
                <p>Vendas <small class="text-muted" id="rel-pecas"></small></p>
            </div>
        </div>
        <div class="tag-card tag-card--brass">
            <div class="kpi-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="kpi-info">
                <h3 id="rel-ticket">R$ 0,00</h3>
                <p>Ticket Médio</p>
            </div>
        </div>
        <div class="tag-card tag-card--rust">
            <div class="kpi-icon"><i class="fa-solid fa-tags"></i></div>
            <div class="kpi-info">
                <h3 id="rel-descontos">R$ 0,00</h3>
                <p>Descontos Concedidos</p>
            </div>
        </div>
    </div>
    <p class="text-muted" id="rel-observacoes" style="font-size: 0.85rem; margin: -6px 0 20px;"></p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    <div class="card" data-secao="geral produtos">
        <h3><i class="fa-solid fa-chart-pie"></i> Vendas por Categoria</h3>
        <div id="rel-categorias" style="margin-top: 20px;"></div>
    </div>

    <div class="card" data-secao="geral">
        <h3><i class="fa-solid fa-credit-card"></i> Vendas por Forma de Pagamento</h3>
        <div id="rel-pagamentos" style="margin-top: 20px;"></div>
    </div>

    <div class="card" data-secao="produtos">
        <h3><i class="fa-solid fa-trophy text-brass"></i> Produtos Mais Vendidos (Receita)</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th>Qtd. Vendida</th>
                        <th>Receita Total</th>
                    </tr>
                </thead>
                <tbody id="rel-top-produtos"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" data-secao="clientes">
    <h3><i class="fa-solid fa-users"></i> Clientes que Mais Compraram</h3>
    <p class="text-muted" style="font-size: 0.85rem; margin-top: 4px;">Vendas de Cliente Balcão não entram aqui. "Fiado em aberto" é o saldo devedor atual do cliente.</p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Compras</th>
                    <th>Total no Período</th>
                    <th>Última Compra</th>
                    <th>Fiado em Aberto</th>
                </tr>
            </thead>
            <tbody id="rel-top-clientes"></tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
