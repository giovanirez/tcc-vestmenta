<?php $page_js = 'inventario.js'; include 'includes/header.php'; ?>

<?php
// Dados Mockados - Estoque atual de todos os produtos (os mesmos
// valores que o gatilho de documentos calcularia no banco real)
$produtos_inventario = [
    ['codigo' => 'PROD-001', 'nome' => 'Camiseta Básica de Algodão', 'estoque' => 133],
    ['codigo' => 'PROD-002', 'nome' => 'Calça Jeans Skinny',         'estoque' => 29],
    ['codigo' => 'PROD-003', 'nome' => 'Jaqueta de Couro PU',        'estoque' => 14],
    ['codigo' => 'PROD-004', 'nome' => 'Vestido Floral Verão',       'estoque' => 18],
    ['codigo' => 'PROD-005', 'nome' => 'Cinto Fino Couro',           'estoque' => 27],
    ['codigo' => 'PROD-006', 'nome' => 'Camisa Social Azul',         'estoque' => 8],
    ['codigo' => 'PROD-007', 'nome' => 'Saia Plissada',              'estoque' => 7],
];
?>

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
                <tbody>
                    <?php foreach ($produtos_inventario as $p): ?>
                    <tr data-sistema="<?= $p['estoque'] ?>">
                        <td class="mono" data-label="Código"><?= $p['codigo'] ?></td>
                        <td data-label="Produto"><?= $p['nome'] ?></td>
                        <td class="mono" data-label="Estoque Sistema"><?= $p['estoque'] ?></td>
                        <td data-label="Contagem"><input type="number" class="form-control contagem-input" min="0" placeholder="0" style="width: 90px;"></td>
                        <td class="mono diferenca-cell" data-label="Diferença">—</td>
                    </tr>
                    <?php endforeach; ?>
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
                <tbody>
                    <?php foreach ($produtos_inventario as $p): ?>
                    <tr data-sistema="<?= $p['estoque'] ?>">
                        <td class="mono" data-label="Código"><?= $p['codigo'] ?></td>
                        <td data-label="Produto"><?= $p['nome'] ?></td>
                        <td class="mono" data-label="Estoque Sistema"><?= $p['estoque'] ?></td>
                        <td data-label="Contagem 1"><input type="number" class="form-control contagem-c1" min="0" placeholder="0" style="width: 80px;"></td>
                        <td data-label="Contagem 2"><input type="number" class="form-control contagem-c2" min="0" placeholder="0" style="width: 80px;"></td>
                        <td data-label="Contagem 3"><input type="number" class="form-control contagem-c3" min="0" placeholder="0" style="width: 80px;"></td>
                        <td class="mono final-cell" data-label="Final">—</td>
                        <td class="mono diferenca-cell" data-label="Diferença">—</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
        <p id="inventario-resumo" class="text-muted" style="font-size: 0.9rem;">Nenhuma contagem lançada ainda.</p>
        <button type="button" class="btn"><i class="fa-solid fa-check"></i> Finalizar Inventário</button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
