<?php $page_js = 'clientes.js'; include 'includes/header.php'; ?>

<div class="card">
    <h2><i class="fa-solid fa-user-plus"></i> <span id="cli-form-titulo">Novo Cliente</span></h2>
    <form id="cli-form" style="margin-top: 15px;">
        <input type="hidden" id="cli-id" value="">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" class="form-control" id="cli-nome" required placeholder="Ex: Maria João">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" class="form-control" id="cli-telefone" required placeholder="(00) 90000-0000" maxlength="15" inputmode="numeric">
            </div>
            <div class="form-group">
                <label>CPF</label>
                <input type="text" class="form-control mono-value" id="cli-cpf" placeholder="Opcional" maxlength="14" inputmode="numeric">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" id="cli-email" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>Limite de Crédito p/ Fiado (R$)</label>
                <input type="number" step="0.01" class="form-control" id="cli-limite-credito" placeholder="Deixe em branco se não vende fiado">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-map-location-dot"></i> Endereço (opcional)</h4>
        <div class="form-grid">
            <div class="form-group" style="max-width: 160px;">
                <label>CEP</label>
                <input type="text" class="form-control mono-value" id="cli-cep" placeholder="00000-000" maxlength="9" inputmode="numeric">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Rua / Logradouro</label>
                <input type="text" class="form-control" id="cli-logradouro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 130px;">
                <label>Número</label>
                <input type="text" class="form-control" id="cli-numero" placeholder="Ex: 45">
            </div>
            <div class="form-group">
                <label>Complemento</label>
                <input type="text" class="form-control" id="cli-complemento" placeholder="Apto, bloco... (opcional)">
            </div>
            <div class="form-group">
                <label>Bairro</label>
                <input type="text" class="form-control" id="cli-bairro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Cidade</label>
                <input type="text" class="form-control" id="cli-cidade" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 90px;">
                <label>UF</label>
                <input type="text" class="form-control mono-value" id="cli-uf" maxlength="2" placeholder="SP">
            </div>
        </div>

        <p id="cli-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn" id="cli-submit-btn"><i class="fa-solid fa-save"></i> Guardar Cliente</button>
            <button type="button" class="btn btn-outline" id="cli-cancelar-btn" style="display: none;">Cancelar edição</button>
        </div>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-users"></i> Carteira de Clientes</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Contactos</th>
                    <th>Endereço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="cli-lista">
                <tr><td colspan="6" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
