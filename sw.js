const CACHE_VERSION = "modasys-v2";

const ARQUIVOS_APP_SHELL = [
    "css/base.css",
    "css/pages/index.css",
    "css/pages/vendas.css",
    "css/pages/relatorios.css",
    "js/main.js",
    "js/pages/entradas.js",
    "js/pages/clientes.js",
    "js/pages/fornecedores.js",
    "js/pages/vendas.js",
    "js/pages/inventario.js",
    "manifest.json",
    "icons/icon-192.png",
    "icons/icon-512.png",
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

self.addEventListener("fetch", (evento) => {
    const requisicao = evento.request;
    if (requisicao.method !== "GET") return;

    // Chamadas à API (backend/) nunca passam pelo cache do service
    // worker — sempre refletem o estado atual do banco. Sem isso, uma
    // vez que uma resposta de /produtos fosse cacheada, o app nunca
    // mais veria uma atualização, mesmo depois de salvar algo novo.
    const ehChamadaDeApi = new URL(requisicao.url).pathname.includes("/backend/");
    if (ehChamadaDeApi) {
        evento.respondWith(fetch(requisicao));
        return;
    }

    const ehNavegacao = requisicao.mode === "navigate";

    if (ehNavegacao) {
        // Páginas PHP: tenta a rede primeiro (dado sempre fresco), cai
        // pro cache só se estiver offline.
        evento.respondWith(
            fetch(requisicao)
                .then((resposta) => {
                    const copia = resposta.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(requisicao, copia));
                    return resposta;
                })
                .catch(() => caches.match(requisicao))
        );
        return;
    }

    // CSS, JS, ícones: cache primeiro (mais rápido), busca na rede se
    // não tiver em cache ainda.
    evento.respondWith(
        caches.match(requisicao).then((respostaCache) => {
            return (
                respostaCache ||
                fetch(requisicao).then((resposta) => {
                    const copia = resposta.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(requisicao, copia));
                    return resposta;
                })
            );
        })
    );
});
