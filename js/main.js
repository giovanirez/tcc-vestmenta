if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("sw.js").catch((erro) => {
            console.error("Falha ao registrar o service worker:", erro);
        });
    });
}

// ---- Autenticação: token fica só no navegador (localStorage se
// "Lembrar de mim" foi marcado, sessionStorage se não). Toda página
// protegida passa por aqui antes de renderizar qualquer coisa. ----
const ModaSysAuth = {
    obterToken() {
        return localStorage.getItem("modasys_token") || sessionStorage.getItem("modasys_token");
    },

    obterUsuario() {
        const bruto = localStorage.getItem("modasys_usuario") || sessionStorage.getItem("modasys_usuario");
        return bruto ? JSON.parse(bruto) : null;
    },

    sair() {
        localStorage.removeItem("modasys_token");
        localStorage.removeItem("modasys_usuario");
        sessionStorage.removeItem("modasys_token");
        sessionStorage.removeItem("modasys_usuario");
        window.location.href = "index.php";
    },

    // Fetch com o token já anexado. Se o backend responder 401 (token
    // ausente/expirado), desloga automaticamente — não deixa a página
    // continuar como se ainda estivesse autenticada.
    async requisitar(caminho, opcoes = {}) {
        const token = this.obterToken();

        const resposta = await fetch(caminho, {
            ...opcoes,
            headers: {
                "Content-Type": "application/json",
                ...(opcoes.headers || {}),
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
        });

        if (resposta.status === 401) {
            this.sair();
            throw new Error("Sessão expirada. Faça login novamente.");
        }

        return resposta;
    },
};

window.ModaSysAuth = ModaSysAuth;

// ---- Máscaras de campo reaproveitáveis em qualquer formulário ----
const ModaSysMascaras = {
    cep(valor) {
        return valor.replace(/\D/g, "").slice(0, 8).replace(/(\d{5})(\d)/, "$1-$2");
    },

    cnpj(valor) {
        return valor.replace(/\D/g, "").slice(0, 14)
            .replace(/(\d{2})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1/$2")
            .replace(/(\d{4})(\d)/, "$1-$2");
    },

    cpf(valor) {
        return valor.replace(/\D/g, "").slice(0, 11)
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1-$2");
    },

    // Fixo (10 dígitos) vira (00) 0000-0000; celular (11) vira (00) 00000-0000
    telefone(valor) {
        const limpo = valor.replace(/\D/g, "").slice(0, 11);
        if (limpo.length <= 10) {
            return limpo.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{4})(\d)/, "$1-$2");
        }
        return limpo.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2");
    },

    uf(valor) {
        return valor.toUpperCase().replace(/[^A-Z]/g, "").slice(0, 2);
    },

    // Liga um <input> a uma das funções acima, reaplicando a máscara
    // a cada tecla digitada e mantendo o cursor no lugar certo (sem
    // isso, o cursor pula pro final do campo a cada caractere).
    aplicar(input, mascara) {
        if (!input) return;
        input.addEventListener("input", () => {
            const posicaoAntes = input.selectionStart;
            const tamanhoAntes = input.value.length;

            input.value = ModaSysMascaras[mascara](input.value);

            const diferenca = input.value.length - tamanhoAntes;
            const novaPosicao = Math.max(0, posicaoAntes + diferenca);
            input.setSelectionRange(novaPosicao, novaPosicao);
        });
    },
};

window.ModaSysMascaras = ModaSysMascaras;

// ---- Modal de confirmação padrão (substitui o confirm() do navegador) ----
const ModaSysModal = {
    confirmar(mensagem, opcoes = {}) {
        return new Promise((resolve) => {
            const overlay = document.getElementById("modal-confirmacao");

            // Se por algum motivo a página não incluiu o modal, não
            // trava a ação — cai de volta pro confirm() nativo.
            if (!overlay) {
                resolve(confirm(mensagem));
                return;
            }

            const textoEl = document.getElementById("modal-confirmacao-texto");
            const btnConfirmar = document.getElementById("modal-confirmacao-confirmar");
            const btnCancelar = document.getElementById("modal-confirmacao-cancelar");

            textoEl.textContent = mensagem;
            btnConfirmar.textContent = opcoes.textoConfirmar || "Confirmar";
            btnCancelar.textContent = opcoes.textoCancelar || "Cancelar";
            overlay.style.display = "flex";

            function encerrar(resultado) {
                overlay.style.display = "none";
                btnConfirmar.removeEventListener("click", aoConfirmar);
                btnCancelar.removeEventListener("click", aoCancelar);
                overlay.removeEventListener("click", aoClicarFora);
                document.removeEventListener("keydown", aoTeclar);
                resolve(resultado);
            }

            function aoConfirmar() { encerrar(true); }
            function aoCancelar() { encerrar(false); }
            function aoClicarFora(evento) { if (evento.target === overlay) encerrar(false); }
            function aoTeclar(evento) { if (evento.key === "Escape") encerrar(false); }

            btnConfirmar.addEventListener("click", aoConfirmar);
            btnCancelar.addEventListener("click", aoCancelar);
            overlay.addEventListener("click", aoClicarFora);
            document.addEventListener("keydown", aoTeclar);
        });
    },
};

window.ModaSysModal = ModaSysModal;

document.addEventListener("DOMContentLoaded", () => {
    // A tela de login é a única página que não exige token — em
    // qualquer outra, sem token vai direto pro login.
    const ehPaginaDeLogin = /(^|\/)index\.php$/.test(window.location.pathname);

    if (!ehPaginaDeLogin && !ModaSysAuth.obterToken()) {
        window.location.href = "index.php";
        return;
    }

    const usuario = ModaSysAuth.obterUsuario();
    const nomeEl = document.getElementById("usuario-logado-nome");
    if (usuario && nomeEl) {
        nomeEl.textContent = usuario.nome;
    }
    const saudacaoNomeEl = document.getElementById("saudacao-nome");
    if (usuario && saudacaoNomeEl) {
        saudacaoNomeEl.textContent = usuario.nome.split(" ")[0];
    }

    // "Configurações" só faz sentido pra quem administra — o backend já
    // bloqueia (403), isso aqui só evita mostrar um link que vai dar erro.
    const itemConfiguracoes = document.getElementById("item-configuracoes");
    if (usuario && itemConfiguracoes && usuario.papel !== "admin") {
        itemConfiguracoes.style.display = "none";
    }

    const botaoSair = document.getElementById("botao-sair");
    if (botaoSair) {
        botaoSair.addEventListener("click", () => ModaSysAuth.sair());
    }

    // Toggle Sidebar
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle("active");
            } else {
                sidebar.classList.toggle("collapsed");
            }
        });
    }

    // Validação básica de formulário (feedback visual só — quem
    // decide se aceita ou não é sempre o backend)
    const forms = document.querySelectorAll("form");
    forms.forEach(form => {
        form.addEventListener("submit", (e) => {
            let isValid = true;
            const requiredFields = form.querySelectorAll("[required]");

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add("is-invalid");
                } else {
                    field.classList.remove("is-invalid");
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert("Por favor, preencha todos os campos obrigatórios.");
            }
        });
    });
});
