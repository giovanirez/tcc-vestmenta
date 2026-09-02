<?php
$hora = (int) date('G');
if ($hora < 12) { $saudacao = 'Bom dia'; }
elseif ($hora < 18) { $saudacao = 'Boa tarde'; }
else { $saudacao = 'Boa noite'; }

$dias_semana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$data_extenso = $dias_semana[date('w')] . ', ' . date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModaSys · Gestão para Loja de Roupas</title>

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#221F1C">
    <link rel="icon" href="icons/icon-192.png">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ModaSys">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <?php if (!empty($page_css)): ?>
    <link rel="stylesheet" href="css/pages/<?= $page_css ?>">
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <header class="topbar">
                <button id="sidebarToggle" class="btn-icon">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="greeting">
                    <?= $saudacao ?>, Admin
                    <span class="date"><?= $data_extenso ?></span>
                </div>
                <div class="user-info">
                    <i class="fa-solid fa-circle-user" style="font-size: 1.4rem;"></i>
                </div>
            </header>
            <main class="content-area">