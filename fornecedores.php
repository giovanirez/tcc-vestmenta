<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Fornecedores
$fornecedores = [
    ['id' => 1, 'nome' => 'Têxtil Sul S.A.', 'cnpj' => '12.345.678/0001-90', 'telefone' => '(11) 3333-4444', 'email' => 'contato@textilsul.com.br', 'endereco' => 'Rua das Malhas, 123 - São Paulo/SP'],
    ['id' => 2, 'nome' => 'Jeans & Cia Distribuidora', 'cnpj' => '98.765.432/0001-10', 'telefone' => '(47) 3222-1111', 'email' => 'vendas@jeanscia.com.br', 'endereco' => 'Av. Industrial, 450 - Blumenau/SC'],
    ['id' => 3, 'nome' => 'Couro Fino Importações', 'cnpj' => '45.678.901/0001-55', 'telefone' => '(51) 3444-5555', 'email' => 'import@courofino.com', 'endereco' => 'Rodovia BR-116, Km 12 - Novo Hamburgo/RS']
];
?>

<div class="card">
    <h2><i class="fa-solid fa-truck-medical"></i> Novo Fornecedor</h2>
    <form action="#" method="POST" style="margin-top: 15px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Nome da Empresa (Razão Social)</label>
                <input type="text" class="form-control" required placeholder="Ex: Têxtil Sul S.A.">
            </div>
            <div class="form-group">
                <label>CNPJ</label>
                <input type="text" class="form-control" required placeholder="00.000.000/0001-00">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" class="form-control" required placeholder="(00) 0000-0000">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" required placeholder="email@empresa.com">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Endereço Completo</label>
                <input type="text" class="form-control" required placeholder="Rua, Número, Bairro - Cidade/Estado">
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Guardar Fornecedor</button>
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
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($fornecedores as $forn): ?>
                <tr>
                    <td class="mono">#<?= $forn['id'] ?></td>
                    <td><strong><?= $forn['nome'] ?></strong></td>
                    <td class="mono"><?= $forn['cnpj'] ?></td>
                    <td>
                        <div><i class="fa-solid fa-phone text-muted" style="font-size: 0.8rem;"></i> <?= $forn['telefone'] ?></div>
                        <div><i class="fa-solid fa-envelope text-muted" style="font-size: 0.8rem;"></i> <?= $forn['email'] ?></div>
                    </td>
                    <td><small><?= $forn['endereco'] ?></small></td>
                    <td>
                        <button class="btn-icon btn-icon--edit"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-icon--delete"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>