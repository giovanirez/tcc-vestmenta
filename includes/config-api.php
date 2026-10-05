<?php
// Endereço do backend. Front e back rodam em serviços separados no
// Cloud Run, então o front precisa saber a URL da API — ela vem da
// variável de ambiente API_URL definida no deploy do front
// (ex.: https://modasys-api-xxxxx.run.app). Sem a variável (XAMPP
// local, tudo na mesma pasta), cai no caminho relativo de sempre.
$api_url = rtrim(getenv('API_URL') ?: 'backend/public/index.php', '/');
// No Render a URL do outro serviço chega só como domínio
// (modasys-api.onrender.com) — completa com https://.
if (getenv('API_URL') && !preg_match('#^https?://#', $api_url)) {
    $api_url = 'https://' . $api_url;
}
?>
<script>window.MODASYS_API_URL = <?= json_encode($api_url, JSON_UNESCAPED_SLASHES) ?>;</script>
