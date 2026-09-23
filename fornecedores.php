<?php $page_js = 'fornecedores.js'; include 'includes/header.php'; ?>

<div class="card">
    <h2><i class="fa-solid fa-truck-medical"></i> <span id="forn-form-titulo">Novo Fornecedor</span></h2>
    <form id="forn-form" style="margin-top: 15px;">
        <input type="hidden" id="forn-id" value="">

        <div class="form-grid">
            <div class="form-group">
                <label>Nome da Empresa (Razão Social)</label>
                <input type="text" class="form-control" id="forn-razao" required placeholder="Ex: Têxtil Sul S.A.">
            </div>
            <div class="form-group">
                <label>CNPJ</label>
                <input type="text" class="form-control mono-value" id="forn-cnpj" required placeholder="00.000.000/0001-00" maxlength="18" inputmode="numeric">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" class="form-control" id="forn-telefone" required placeholder="(00) 0000-0000" maxlength="15" inputmode="numeric">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" id="forn-email" required placeholder="email@empresa.com">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-map-location-dot"></i> Endereço (opcional)</h4>
        <div class="form-grid">
            <div class="form-group" style="max-width: 160px;">
                <label>CEP</label>
                <input type="text" class="form-control mono-value" id="forn-cep" placeholder="00000-000" maxlength="9" inputmode="numeric">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Rua / Logradouro</label>
                <input type="text" class="form-control" id="forn-logradouro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 130px;">
                <label>Número</label>
                <input type="text" class="form-control" id="forn-numero" placeholder="Ex: 123">
            </div>
            <div class="form-group">
                <label>Complemento</label>
                <input type="text" class="form-control" id="forn-complemento" placeholder="Sala, galpão... (opcional)">
            </div>
            <div class="form-group">
                <label>Bairro</label>
                <input type="text" class="form-control" id="forn-bairro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Cidade</label>
                <input type="text" class="form-control" id="forn-cidade" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 90px;">
                <label>UF</label>
                <input type="text" class="form-control mono-value" id="forn-uf" maxlength="2" placeholder="SP">
            </div>
        </div>

        <p id="forn-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn" id="forn-submit-btn"><i class="fa-solid fa-save"></i> Guardar Fornecedor</button>
            <button type="button" class="btn btn-outline" id="forn-cancelar-btn" style="display: none;">Cancelar edição</button>
        </div>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Lista de Fornecedores</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>CNPJ</th>
                    <th>Contactos</th>
                    <th>Endereço</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="forn-lista">
                <tr><td colspan="7" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>