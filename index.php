<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar · ModaSys</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/pages/login.css">
</head>
<body class="login-body">
    <div class="login-screen">
        <aside class="login-brand">
            <div class="login-brand-mark"><span>ModaSys</span></div>
            <p class="login-tagline">Cada peça tem sua etiqueta. Cada venda, seu registro.</p>
        </aside>

        <main class="login-panel">
            <form class="login-card" action="index.php" method="POST">
                <h1>Entrar</h1>
                <p class="login-sub">Acesse o painel da sua loja.</p>

                <div class="form-group">
                    <label for="login-user">Usuário ou e-mail</label>
                    <input type="text" id="login-user" name="usuario" class="form-control" required placeholder="voce@sualoja.com.br">
                </div>

                <div class="form-group">
                    <label for="login-pass">Senha</label>
                    <input type="password" id="login-pass" name="senha" class="form-control" required placeholder="••••••••">
                </div>

                <div class="login-row">
                    <label class="login-remember">
                        <input type="checkbox" name="lembrar"> Lembrar de mim
                    </label>
                    <a href="#">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn login-submit"><i class="fa-solid fa-right-to-bracket"></i> Entrar</button>
            </form>
        </main>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
