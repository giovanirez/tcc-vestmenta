document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".login-card");
    if (!form) return;

    const botao = form.querySelector(".login-submit");
    const rotuloBotaoOriginal = botao.innerHTML;

    const erroEl = document.createElement("p");
    erroEl.className = "text-rust";
    erroEl.style.cssText = "font-size:0.85rem; text-align:center; margin-top:10px; display:none;";
    botao.insertAdjacentElement("beforebegin", erroEl);

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        erroEl.style.display = "none";

        const email = document.getElementById("login-user").value;
        const senha = document.getElementById("login-pass").value;
        const lembrar = form.querySelector('input[name="lembrar"]').checked;

        botao.disabled = true;
        botao.textContent = "Entrando...";

        try {
            const resposta = await fetch("backend/public/index.php/auth/login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, senha }),
            });

            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || "Não foi possível entrar.");
            }

            // "Lembrar de mim" decide onde o token fica: localStorage
            // sobrevive fechar o navegador, sessionStorage some ao fechar.
            const armazenamento = lembrar ? localStorage : sessionStorage;
            armazenamento.setItem("modasys_token", dados.token);
            armazenamento.setItem("modasys_usuario", JSON.stringify(dados.usuario));

            window.location.href = "dashboard.php";
        } catch (erro) {
            erroEl.textContent = erro.message;
            erroEl.style.display = "block";
            botao.disabled = false;
            botao.innerHTML = rotuloBotaoOriginal;
        }
    });
});
