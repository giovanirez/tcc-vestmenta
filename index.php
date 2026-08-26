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
    <link rel="stylesheet" href="css/pages/index.css">
</head>
<body class="login-body">
    <div class="login-screen">
        <aside class="login-brand">
            <div class="login-brand-mark">
                <svg class="login-brand-illustration" viewBox="0 0 260 150" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Cabideiro com roupas">
                    <rect x="14" y="10" width="232" height="5" rx="2.5" fill="rgba(255,255,255,0.55)" />
                    <circle cx="14" cy="12.5" r="5" fill="rgba(255,255,255,0.55)" />
                    <circle cx="246" cy="12.5" r="5" fill="rgba(255,255,255,0.55)" />

                    <g>
                        <path d="M65 15 V26" stroke="rgba(255,255,255,0.45)" stroke-width="2" stroke-linecap="round" />
                        <path d="M43 34 L65 20 L87 34" stroke="rgba(255,255,255,0.45)" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M43 34 L34 48 L44 55 L44 138 Q65 146 86 138 L86 55 L96 48 L87 34 L65 42 Z" fill="var(--thread)" />
                    </g>

                    <g>
                        <path d="M130 15 V24" stroke="rgba(255,255,255,0.45)" stroke-width="2" stroke-linecap="round" />
                        <path d="M112 30 L130 18 L148 30" stroke="rgba(255,255,255,0.45)" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M112 30 L104 42 L113 48 L113 122 Q130 129 147 122 L147 48 L156 42 L148 30 L130 37 Z" fill="var(--brass)" />
                    </g>

                    <g>
                        <path d="M195 15 V25" stroke="rgba(255,255,255,0.45)" stroke-width="2" stroke-linecap="round" />
                        <path d="M175 32 L195 19 L215 32" stroke="rgba(255,255,255,0.45)" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M175 32 L166 45 L176 51 L176 132 Q195 140 214 132 L214 51 L224 45 L215 32 L195 40 Z" fill="var(--denim)" />
                        <path d="M195 40 V132" stroke="rgba(0,0,0,0.18)" stroke-width="1.5" />
                    </g>
                </svg>
                <span>ModaSys</span>
            </div>
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
