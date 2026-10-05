// Versão do cache. Com o stale-while-revalidate abaixo, mudanças de
// CSS/JS chegam sozinhas (no carregamento seguinte) — só é preciso subir
// a versão quando a LISTA de arquivos abaixo mudar, ou pra forçar todo
// mundo a descartar o cache antigo de uma vez.
const CACHE_VERSION = "modasys-v6";

// Baixados na instalação do service worker. Se UM deles falhar (404), a
// instalação inteira falha — por isso só entra aqui o que com certeza
// existe no servidor.
const ARQUIVOS_APP_SHELL = [
    "offline.html",
    "manifest.json",
    "icons/icon-192.png",
    "icons/icon-512.png",
    "icons/icon-512-maskable.png",
    "css/base.css",
    "css/pages/index.css",
    "css/pages/vendas.css",
    "css/pages/relatorios.css",
    "js/main.js",
    "js/pages/login.js",
    "js/pages/dashboard.js",
    "js/pages/fornecedores.js",
    "js/pages/produtos.js",
    "js/pages/clientes.js",
    "js/pages/entradas.js",
    "js/pages/inventario.js",
    "js/pages/vendas.js",
    "js/pages/condicionais.js",
    "js/pages/contas-a-receber.js",
    "js/pages/custos-fixos.js",
    "js/pages/relatorios.js",
    "js/pages/configuracoes.js",
];

self.addEventListener("install", (evento) => {
    evento.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(ARQUIVOS_APP_SHELL))
    );
    self.skipWaiting();
});

self.addEventListener("activate", (evento) => {
    evento.waitUntil(
        caches.keys().then((nomes) =>
            Promise.all(
                nomes
                    .filter((nome) => nome !== CACHE_VERSION)
                    .map((nome) => caches.delete(nome))
            )
        )
    );
    self.clients.claim();
});

function guardarNoCache(requisicao, resposta) {
    // Só guarda resposta completa e bem-sucedida — um 404/500 em cache
    // ficaria sendo servido pra sempre.
    if (resposta && resposta.ok && resposta.type === "basic") {
        const copia = resposta.clone();
        caches.open(CACHE_VERSION).then((cache) => cache.put(requisicao, copia));
    }
    return resposta;
}

self.addEventListener("fetch", (evento) => {
    const requisicao = evento.request;
    if (requisicao.method !== "GET") return;

    // Chamadas à API nunca passam pelo cache do service worker — sempre
    // refletem o estado atual do banco. Em produção a API fica em outro
    // domínio (outro serviço), então tudo que não é da origem do front
    // (API, ViaCEP, BrasilAPI, fontes) vai direto pra rede. Localmente,
    // no XAMPP, a API fica em /backend/ na mesma origem.
    const url = new URL(requisicao.url);
    if (url.origin !== self.location.origin || url.pathname.includes("/backend/")) {
        return;
    }

    // Páginas: rede primeiro (o layout vem sempre atualizado). Sem
    // internet, usa a última cópia daquela página; se nunca foi aberta,
    // mostra a página de "sem conexão".
    if (requisicao.mode === "navigate") {
        evento.respondWith(
            fetch(requisicao)
                .then((resposta) => guardarNoCache(requisicao, resposta))
                .catch(() =>
                    caches.match(requisicao, { ignoreSearch: true })
                        .then((emCache) => emCache || caches.match("offline.html"))
                )
        );
        return;
    }

    // CSS, JS, ícones: stale-while-revalidate — responde na hora com o
    // que está em cache (rápido, funciona offline) e, em paralelo, busca
    // a versão nova no servidor pra guardar. Depois de um deploy, a
    // mudança aparece no carregamento seguinte, sem precisar mexer na
    // CACHE_VERSION.
    evento.respondWith(
        caches.match(requisicao).then((emCache) => {
            const daRede = fetch(requisicao)
                .then((resposta) => guardarNoCache(requisicao, resposta))
                .catch(() => emCache);
            // Mantém o service worker vivo até a atualização terminar,
            // mesmo depois de já ter respondido com a cópia do cache.
            evento.waitUntil(daRede);
            return emCache || daRede;
        })
    );
});
