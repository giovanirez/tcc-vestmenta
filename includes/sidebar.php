<?php $pagina_atual = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span>ModaSys</span>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= $pagina_atual === 'dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a></li>

        <li class="nav-title">Cadastros</li>
        <li><a href="fornecedores.php" class="<?= $pagina_atual === 'fornecedores.php' ? 'active' : '' ?>"><i class="fa-solid fa-truck"></i> <span>Fornecedores</span></a></li>
        <li><a href="produtos.php" class="<?= $pagina_atual === 'produtos.php' ? 'active' : '' ?>"><i class="fa-solid fa-tags"></i> <span>Produtos</span></a></li>
        <li><a href="clientes.php" class="<?= $pagina_atual === 'clientes.php' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> <span>Clientes</span></a></li>

        <li class="nav-title">Operacional</li>
        <li><a href="entradas.php" class="<?= $pagina_atual === 'entradas.php' ? 'active' : '' ?>"><i class="fa-solid fa-box-open"></i> <span>Entradas</span></a></li>
        <li><a href="inventario.php" class="<?= $pagina_atual === 'inventario.php' ? 'active' : '' ?>"><i class="fa-solid fa-clipboard-check"></i> <span>Inventário</span></a></li>
        <li><a href="vendas.php" class="<?= $pagina_atual === 'vendas.php' ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping"></i> <span>Vendas</span></a></li>
        <li><a href="condicionais.php" class="<?= $pagina_atual === 'condicionais.php' ? 'active' : '' ?>"><i class="fa-solid fa-person-booth"></i> <span>Condicionais</span></a></li>
        <li><a href="contas-a-receber.php" class="<?= $pagina_atual === 'contas-a-receber.php' ? 'active' : '' ?>"><i class="fa-solid fa-hand-holding-dollar"></i> <span>Contas a Receber</span></a></li>

        <li class="nav-title">Administrativo</li>
        <li><a href="custos-fixos.php" class="<?= $pagina_atual === 'custos-fixos.php' ? 'active' : '' ?>"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Custos Fixos</span></a></li>
        <li><a href="relatorios.php" class="<?= $pagina_atual === 'relatorios.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> <span>Relatórios</span></a></li>
        <li id="item-configuracoes"><a href="configuracoes.php" class="<?= $pagina_atual === 'configuracoes.php' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> <span>Configurações</span></a></li>
    </ul>
</aside>
