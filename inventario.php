<?php $page_js = 'inventario.js'; include 'includes/header.php'; ?>


<div class="card">
    <h2><i class="fa-solid fa-clipboard-check"></i> Inventário de Estoque</h2>
    <p class="text-muted" style="margin-top: 5px;">Confere a contagem física da loja com o estoque do sistema. Traz todos os produtos cadastrados, mesmo os com saldo zerado.</p>

    <div class="tab-switch" role="tablist" style="margin-top: 18px;">
        <button type="button" class="tab-switch-btn active" data-modo="1">1 Contagem</button>
        <button type="button" class="tab-switch-btn" data-modo="3">3 Contagens</button>
    </div>

    <!-- Modo: 1 contagem -->
    <div id="inventario-modo-1">
        <div class="table-responsive">
            <table class="table-stack-mobile">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Produto</th>
                        <th>Estoque Sistema</th>
                        <th>Contagem</th>
                        <th>Diferença</th>
                    </tr>
                </thead>
                <tbody id="inventario-lista-1">
                    <tr><td colspan="5" class="text-muted">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modo: 3 contagens (contagem cega, a final é a mediana das três) -->
    <div id="inventario-modo-3" style="display: none;">
        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 10px;">A Contagem Final é a mediana das três. Linhas com as três contagens divergentes ficam marcadas para revisão antes de fechar o inventário.</p>
        <div class="table-responsive">
            <table class="table-stack-mobile">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Produto</th>
                        <th>Estoque Sistema</th>
                        <th>Contagem 1</th>
                        <th>Contagem 2</th>
                        <th>Contagem 3</th>
                        <th>Final</th>
                        <th>Diferença</th>
                    </tr>
                </thead>
                <tbody id="inventario-lista-3">
                    <tr><td colspan="8" class="text-muted">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
        <p id="inventario-resumo" class="text-muted" style="font-size: 0.9rem;">Nenhuma contagem lançada ainda.</p>
        <button type="button" class="btn" id="inventario-finalizar-btn"><i class="fa-solid fa-check"></i> Finalizar Inventário</button>
    </div>
    <p id="inventario-mensagem" class="text-rust" style="display: none; margin-top: 10px; font-size: 0.85rem;"></p>
    <p id="inventario-aviso-admin" class="text-muted" style="display: none; margin-top: 10px; font-size: 0.85rem;"><i class="fa-solid fa-lock"></i> Só um administrador pode finalizar o inventário e aplicar os ajustes no estoque.</p>
</div>

<div class="card" id="perda-card">
    <h2><i class="fa-solid fa-heart-crack"></i> Registrar Perda / Avaria</h2>
    <p class="text-muted" style="margin-top: 5px;">Peça danificada, extraviada ou retirada para uso da loja — sai do estoque sem ser venda.</p>
    <form id="perda-form" style="margin-top: 15px;" novalidate>
        <div class="form-grid">
            <div class="form-group">
                <label>Produto</label>
                <select class="form-control" id="perda-produto">
                    <option value="">Selecione o produto...</option>
                </select>
            </div>
            <div class="form-group" style="max-width: 120px;">
                <label>Quantidade</label>
                <input type="number" class="form-control" id="perda-quantidade" value="1" min="1">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Motivo</label>
                <input type="text" class="form-control" id="perda-motivo" maxlength="255" placeholder="Ex: peça manchada no provador">
            </div>
        </div>
        <p id="perda-mensagem" class="text-rust" style="display: none; margin-bottom: 10px; font-size: 0.85rem;"></p>
        <button type="submit" class="btn" id="perda-submit-btn"><i class="fa-solid fa-minus"></i> Registrar Perda</button>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Últimos Ajustes de Estoque</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Origem</th>
                    <th>Peças</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody id="ajustes-lista">
                <tr><td colspan="5" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
