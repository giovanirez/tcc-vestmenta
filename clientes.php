<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Clientes
$clientes = [
    ['id' => 101, 'nome' => 'Mariana Oliveira', 'cpf' => '111.222.333-44', 'telefone' => '(11) 98888-7777', 'email' => 'mariana.oliveira@email.com', 'endereco' => 'Av. Central, 45, Apto 302 - Centro'],
    ['id' => 102, 'nome' => 'Carlos Mendes', 'cpf' => '222.333.444-55', 'telefone' => '(11) 97777-6666', 'email' => 'carlos.mendes@email.com', 'endereco' => 'Rua das Flores, 128 - Bairro Alto'],
    ['id' => 103, 'nome' => 'Ana Paula Silva', 'cpf' => '333.444.555-66', 'telefone' => '(11) 96666-5555', 'email' => 'anapaula.silva@email.com', 'endereco' => 'Praça da Liberdade, 10 - Bela Vista']
];
?>

<div class="card">
    <h2><i class="fa-solid fa-user-plus"></i> Novo Cliente</h2>
    <form action="#" method="POST" style="margin-top: 15px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" class="form-control" required placeholder="Ex: Maria João">
            </div>
            <div class="form-group">
                <label>CPF</label>
                <input type="text" class="form-control" required placeholder="000.000.000-00">
            </div>
            <div class="form-group">
                <label>Telemóvel</label>
                <input type="text" class="form-control" required placeholder="(00) 90000-0000">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Endereço Completo</label>
                <input type="text" class="form-control" required placeholder="Rua, Número, Complemento, Bairro - Cidade/Estado">
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Guardar Cliente</button>
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
            <tbody>
                <?php foreach($clientes as $cli): ?>
                <tr>
                    <td>#<?= $cli['id'] ?></td>
                    <td><strong><?= $cli['nome'] ?></strong></td>
                    <td><?= $cli['cpf'] ?></td>
                    <td>
                        <div><i class="fa-solid fa-mobile-screen" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?= $cli['telefone'] ?></div>
                        <?php if(!empty($cli['email'])): ?>
                            <div><i class="fa-solid fa-envelope" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?= $cli['email'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td><small><?= $cli['endereco'] ?></small></td>
                    <td>
                        <button class="btn-icon" title="Editar" style="color: var(--accent-color);"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon" title="Ver Histórico" style="color: #2ecc71;"><i class="fa-solid fa-clock-rotate-left"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>