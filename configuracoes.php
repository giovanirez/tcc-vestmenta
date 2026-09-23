<?php $page_js = 'configuracoes.js'; include 'includes/header.php'; ?>

<div id="config-sem-permissao" class="card" style="display: none; text-align: center;">
    <i class="fa-solid fa-lock text-rust" style="font-size: 2rem; margin-bottom: 10px;"></i>
    <p>Esta área é só para administradores. Fale com um admin da loja se precisar de algo aqui.</p>
</div>

<div id="config-conteudo" style="display: none;">
    <div class="card">
        <h2><i class="fa-solid fa-user-plus"></i> <span id="usr-form-titulo">Novo Usuário</span></h2>
        <form id="usr-form" style="margin-top: 15px;">
            <input type="hidden" id="usr-id" value="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" class="form-control" id="usr-nome" required placeholder="Ex: Mariana Oliveira">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" class="form-control" id="usr-email" required placeholder="pessoa@modasys.com.br">
                </div>
                <div class="form-group">
                    <label>Papel</label>
                    <select class="form-control" id="usr-papel" required>
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="usr-senha-label">Senha</label>
                    <input type="password" class="form-control" id="usr-senha" placeholder="Mínimo 8 caracteres" minlength="8">
                </div>
            </div>

            <p id="usr-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn" id="usr-submit-btn"><i class="fa-solid fa-save"></i> Criar Usuário</button>
                <button type="button" class="btn btn-outline" id="usr-cancelar-btn" style="display: none;">Cancelar edição</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2><i class="fa-solid fa-users-gear"></i> Usuários do Sistema</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Papel</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="usr-lista">
                    <tr><td colspan="6" class="text-muted">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
