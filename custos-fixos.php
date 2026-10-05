<?php $page_js = 'custos-fixos.js'; include 'includes/header.php'; ?>


<div class="dashboard-cards">
    <div class="tag-card tag-card--rust">
        <div class="kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="kpi-info">
            <h3 id="cf-total-mensal">R$ 0,00</h3>
            <p>Total Fixo Mensal</p>
        </div>
    </div>
    <div class="tag-card tag-card--denim">
        <div class="kpi-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="kpi-info">
            <h3 id="cf-qtd-ativos">0</h3>
            <p>Custos Ativos</p>
        </div>
    </div>
</div>

<div class="card">
    <h2><i class="fa-solid fa-money-bill-transfer"></i> <span id="cf-form-titulo">Novo Custo Fixo</span></h2>
    <form id="cf-form" style="margin-top: 15px;">
        <input type="hidden" id="cf-id" value="">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome do Custo</label>
                <input type="text" class="form-control" id="cf-nome" required maxlength="120" placeholder="Ex: Aluguel da Loja">
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <select class="form-control" id="cf-categoria">
                    <option value="">Selecione...</option>
                    <option value="Ocupação">Ocupação</option>
                    <option value="Utilidades">Utilidades</option>
                    <option value="Pessoal">Pessoal</option>
                    <option value="Serviços">Serviços</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>
            <div class="form-group">
                <label>Valor (R$)</label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="cf-valor" required placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Dia de Vencimento</label>
                <input type="number" class="form-control" id="cf-dia" min="1" max="31" required placeholder="Ex: 10">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Observação</label>
                <input type="text" class="form-control" id="cf-observacao" maxlength="255" placeholder="Opcional">
            </div>
        </div>
        <p id="cf-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn" id="cf-submit-btn"><i class="fa-solid fa-save"></i> Salvar Custo Fixo</button>
            <button type="button" class="btn btn-outline" id="cf-cancelar-btn" style="display: none;">Cancelar edição</button>
        </div>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Custos Fixos Cadastrados</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="cf-lista">
                <tr><td colspan="6" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
