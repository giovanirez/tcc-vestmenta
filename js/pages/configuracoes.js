document.addEventListener("DOMContentLoaded", () => {
    const usuarioLogado = ModaSysAuth.obterUsuario();
    const semPermissaoEl = document.getElementById("config-sem-permissao");
    const conteudoEl = document.getElementById("config-conteudo");

    // O backend já bloqueia (403) quem não é admin — isso aqui é só pra
    // não deixar a tela vazia/quebrada se alguém entrar direto pela URL.
    if (!usuarioLogado || usuarioLogado.papel !== "admin") {
        semPermissaoEl.style.display = "block";
        return;
    }
    conteudoEl.style.display = "block";

    const form = document.getElementById("usr-form");
    const idInput = document.getElementById("usr-id");
    const nome = document.getElementById("usr-nome");
    const email = document.getElementById("usr-email");
    const papel = document.getElementById("usr-papel");
    const senha = document.getElementById("usr-senha");
    const senhaLabel = document.getElementById("usr-senha-label");
    const tituloForm = document.getElementById("usr-form-titulo");
    const submitBtn = document.getElementById("usr-submit-btn");
    const cancelarBtn = document.getElementById("usr-cancelar-btn");
    const mensagemEl = document.getElementById("usr-mensagem");
    const listaEl = document.getElementById("usr-lista");

    let usuariosCache = [];

    function linhaUsuario(u) {
        const ehVoceMesmo = u.id === usuarioLogado.id;
        const botaoAlternar = ehVoceMesmo
            ? ""
            : `<button type="button" class="btn-icon btn-icon--delete" data-acao="alternar" title="${u.ativo ? "Desativar" : "Reativar"}"><i class="fa-solid ${u.ativo ? "fa-ban" : "fa-rotate-left"}"></i></button>`;

        return `
            <tr data-id="${u.id}">
                <td class="mono">#${u.id}</td>
                <td><strong>${u.nome}</strong>${ehVoceMesmo ? ' <span class="text-muted">(você)</span>' : ""}</td>
                <td>${u.email}</td>
                <td><span class="badge ${u.papel === "admin" ? "badge-info" : "badge-neutral"}">${u.papel === "admin" ? "Admin" : "Vendedor"}</span></td>
                <td><span class="badge ${u.ativo ? "badge-success" : "badge-neutral"}">${u.ativo ? "Ativo" : "Inativo"}</span></td>
                <td>
                    <button type="button" class="btn-icon btn-icon--edit" data-acao="editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                    ${botaoAlternar}
                </td>
            </tr>`;
    }

    async function carregarLista() {
        try {
            const resposta = await ModaSysAuth.requisitar("backend/public/index.php/usuarios");
            usuariosCache = await resposta.json();

            listaEl.innerHTML = usuariosCache.length
                ? usuariosCache.map(linhaUsuario).join("")
                : '<tr><td colspan="6" class="text-muted">Nenhum usuário cadastrado ainda.</td></tr>';
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="6" class="text-rust">Falha ao carregar: ${erro.message}</td></tr>`;
        }
    }

    function entrarModoEdicao(usuario) {
        idInput.value = usuario.id;
        nome.value = usuario.nome;
        email.value = usuario.email;
        papel.value = usuario.papel;
        senha.value = "";
        senha.removeAttribute("required");
        senha.placeholder = "Deixe em branco para manter a atual";
        senhaLabel.textContent = "Nova Senha (opcional)";

        tituloForm.textContent = `Editando: ${usuario.nome}`;
        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Atualizar Usuário';
        cancelarBtn.style.display = "inline-block";
        form.scrollIntoView({ behavior: "smooth" });
    }

    function sairModoEdicao() {
        idInput.value = "";
        form.reset();
        senha.placeholder = "Mínimo 8 caracteres";
        senhaLabel.textContent = "Senha";
        tituloForm.textContent = "Novo Usuário";
        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Criar Usuário';
        cancelarBtn.style.display = "none";
        mensagemEl.style.display = "none";
    }

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();

        const emEdicao = idInput.value !== "";
        if (!emEdicao) senha.setAttribute("required", "required");
        if (!form.checkValidity()) return;

        mensagemEl.style.display = "none";

        try {
            if (emEdicao) {
                const resposta = await ModaSysAuth.requisitar(`backend/public/index.php/usuarios/${idInput.value}`, {
                    method: "PUT",
                    body: JSON.stringify({ nome: nome.value, email: email.value, papel: papel.value }),
                });
                const dados = await resposta.json();
                if (!resposta.ok) throw new Error(dados.erro || "Não foi possível salvar.");

                if (senha.value) {
                    const respostaSenha = await ModaSysAuth.requisitar(`backend/public/index.php/usuarios/${idInput.value}/senha`, {
                        method: "PATCH",
                        body: JSON.stringify({ senha: senha.value }),
                    });
                    const dadosSenha = await respostaSenha.json();
                    if (!respostaSenha.ok) throw new Error(dadosSenha.erro || "Não foi possível redefinir a senha.");
                }
            } else {
                const resposta = await ModaSysAuth.requisitar("backend/public/index.php/usuarios", {
                    method: "POST",
                    body: JSON.stringify({ nome: nome.value, email: email.value, senha: senha.value, papel: papel.value }),
                });
                const dados = await resposta.json();
                if (!resposta.ok) throw new Error(dados.erro || "Não foi possível criar.");
            }

            sairModoEdicao();
            await carregarLista();
        } catch (erro) {
            mensagemEl.textContent = erro.message;
            mensagemEl.style.display = "block";
        }
    });

    cancelarBtn.addEventListener("click", sairModoEdicao);

    listaEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest("button[data-acao]");
        if (!botao) return;

        const id = botao.closest("tr").dataset.id;
        const usuario = usuariosCache.find(u => String(u.id) === id);

        if (botao.dataset.acao === "editar") {
            entrarModoEdicao(usuario);
            return;
        }

        if (botao.dataset.acao === "alternar") {
            const acaoTexto = usuario.ativo ? "desativar" : "reativar";
            const confirmado = await ModaSysModal.confirmar(
                `Tem certeza que quer ${acaoTexto} "${usuario.nome}"?`,
                { textoConfirmar: usuario.ativo ? "Desativar" : "Reativar" }
            );
            if (!confirmado) return;

            try {
                await ModaSysAuth.requisitar(`backend/public/index.php/usuarios/${id}/ativo`, { method: "PATCH" });
                await carregarLista();
            } catch (erro) {
                alert(erro.message);
            }
        }
    });

    carregarLista();
});
