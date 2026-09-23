<?php $page_js = 'produtos.js'; include 'includes/header.php'; ?>

<div class="info-banner">
    <i class="fa-solid fa-circle-info"></i>
    <p>Produtos novos são cadastrados ao registrar a <a href="entradas.php">Entrada</a> em que chegaram — assim nenhuma peça fica no catálogo sem ter dado entrada de fato no estoque. Aqui você só consulta e edita o que já existe.</p>
</div>

<div class="card" id="prod-form-card" style="display: none;">
    <h2><i class="fa-solid fa-pen-to-square"></i> <span id="prod-form-titulo">Editar Produto</span></h2>
    <form id="prod-form" style="margin-top: 15px;">
        <input type="hidden" id="prod-id" value="">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome</label>
                <input type="text" class="form-control" id="prod-nome" required>
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <select class="form-control" id="prod-categoria">
                    <option value="">Sem categoria</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fornecedor</label>
                <select class="form-control" id="prod-fornecedor">
                    <option value="">Sem fornecedor</option>
                </select>
            </div>
            <div class="form-group">
                <label>Preço de Venda (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="prod-preco-venda" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Descrição</label>
                <input type="text" class="form-control" id="prod-descricao">
            </div>
        </div>

        <p id="prod-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
            <button type="button" class="btn btn-outline" id="prod-cancelar-btn">Cancelar</button>
        </div>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Lista de Produtos</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Fornecedor</th>
                    <th>Estoque</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="prod-lista">
                <tr><td colspan="8" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
