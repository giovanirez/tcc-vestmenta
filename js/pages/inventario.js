document.addEventListener("DOMContentLoaded", () => {
    const abas = document.querySelectorAll(".tab-switch-btn");
    const modo1 = document.getElementById("inventario-modo-1");
    const modo3 = document.getElementById("inventario-modo-3");
    const lista1 = document.getElementById("inventario-lista-1");
    const lista3 = document.getElementById("inventario-lista-3");
    const resumo = document.getElementById("inventario-resumo");
    const finalizarBtn = document.getElementById("inventario-finalizar-btn");
    const mensagemEl = document.getElementById("inventario-mensagem");

    if (!abas.length || !lista1) return;

    const usuario = ModaSysAuth.obterUsuario();
    const ehAdmin = usuario && usuario.papel === "admin";

    // Ajustar estoque é só pra admin (o backend também bloqueia) — pro
    // vendedor a tela continua servindo pra contar e conferir.
    if (!ehAdmin) {
        finalizarBtn.style.display = "none";
        document.getElementById("inventario-aviso-admin").style.display = "block";
        document.getElementById("perda-card").style.display = "none";
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    function mostrar(el, texto, classe = "text-rust") {
        el.className = classe;
        el.textContent = texto;
        el.style.display = texto ? "block" : "none";
    }

    async function requisitarJson(caminho, opcoes) {
        const resposta = await ModaSysAuth.requisitar(`/${caminho}`, opcoes);
        const dados = await resposta.json();
        if (!resposta.ok) throw new Error(dados.erro || "Falha na operação.");
        return dados;
    }

    abas.forEach(botao => {
        botao.addEventListener("click", () => {
            abas.forEach(b => b.classList.remove("active"));
            botao.classList.add("active");
            const modo3ativo = botao.dataset.modo === "3";
            modo1.style.display = modo3ativo ? "none" : "block";
            modo3.style.display = modo3ativo ? "block" : "none";
            atualizarResumo();
        });
    });

    function modo3Ativo() {
        return modo3.style.display !== "none";
    }

    function formatarDiferenca(diferenca) {
        if (diferenca === 0) return `<span class="text-moss">0</span>`;
        const sinal = diferenca > 0 ? "+" : "";
        return `<span class="text-rust">${sinal}${diferenca}</span>`;
    }

    function mediana(a, b, c) {
        return [a, b, c].sort((x, y) => x - y)[1];
    }

    // ---- Montagem das tabelas a partir dos produtos do banco ----
    function rotuloProduto(p) {
        return `${escapar(p.nome)}${p.ativo ? "" : ' <span class="badge badge-neutral">Inativo</span>'}`;
    }

    function renderizarProdutos(produtos) {
        if (!produtos.length) {
            lista1.innerHTML = '<tr><td colspan="5" class="text-muted">Nenhum produto cadastrado.</td></tr>';
            lista3.innerHTML = '<tr><td colspan="8" class="text-muted">Nenhum produto cadastrado.</td></tr>';
            return;
        }

        lista1.innerHTML = produtos.map(p => `
            <tr data-produto="${p.id}" data-sistema="${p.estoque_atual}">
                <td class="mono" data-label="Código">${escapar(p.codigo_interno)}</td>
                <td data-label="Produto">${rotuloProduto(p)}</td>
                <td class="mono" data-label="Estoque Sistema">${p.estoque_atual}</td>
                <td data-label="Contagem"><input type="number" class="form-control contagem-input" min="0" placeholder="0" style="width: 90px;"></td>
                <td class="mono diferenca-cell" data-label="Diferença">—</td>
            </tr>`).join("");

        lista3.innerHTML = produtos.map(p => `
            <tr data-produto="${p.id}" data-sistema="${p.estoque_atual}">
                <td class="mono" data-label="Código">${escapar(p.codigo_interno)}</td>
                <td data-label="Produto">${rotuloProduto(p)}</td>
                <td class="mono" data-label="Estoque Sistema">${p.estoque_atual}</td>
                <td data-label="Contagem 1"><input type="number" class="form-control contagem-c1" min="0" placeholder="0" style="width: 80px;"></td>
                <td data-label="Contagem 2"><input type="number" class="form-control contagem-c2" min="0" placeholder="0" style="width: 80px;"></td>
                <td data-label="Contagem 3"><input type="number" class="form-control contagem-c3" min="0" placeholder="0" style="width: 80px;"></td>
                <td class="mono final-cell" data-label="Final">—</td>
                <td class="mono diferenca-cell" data-label="Diferença">—</td>
            </tr>`).join("");
    }

    // Lê a contagem final de uma linha (null = não contada) e se as três
    // contagens divergiram (só no modo 3).
    function lerLinha(linha, modo3) {
        if (modo3) {
            const valores = [".contagem-c1", ".contagem-c2", ".contagem-c3"].map(s => linha.querySelector(s).value);
            if (valores.some(v => v === "")) return { final: null, divergente: false };
            const [v1, v2, v3] = valores.map(v => parseInt(v, 10) || 0);
            return { final: mediana(v1, v2, v3), divergente: !(v1 === v2 && v2 === v3) };
        }
        const valor = linha.querySelector(".contagem-input").value;
        return { final: valor === "" ? null : (parseInt(valor, 10) || 0), divergente: false };
    }

    // ---- Recalcular (delegado: as linhas são criadas dinamicamente) ----
    lista1.addEventListener("input", () => {
        lista1.querySelectorAll("tr[data-produto]").forEach(linha => {
            const { final } = lerLinha(linha, false);
            linha.querySelector(".diferenca-cell").innerHTML =
                final === null ? "—" : formatarDiferenca(final - parseInt(linha.dataset.sistema, 10));
        });
        atualizarResumo();
    });

    lista3.addEventListener("input", () => {
        lista3.querySelectorAll("tr[data-produto]").forEach(linha => {
            const { final, divergente } = lerLinha(linha, true);
            const celulaFinal = linha.querySelector(".final-cell");
            const celulaDiferenca = linha.querySelector(".diferenca-cell");

            if (final === null) {
                celulaFinal.innerHTML = "—";
                celulaDiferenca.innerHTML = "—";
                return;
            }
            celulaFinal.innerHTML = divergente
                ? `${final} <span class="badge badge-warning" style="margin-left: 4px;">Revisar</span>`
                : `${final}`;
            celulaDiferenca.innerHTML = formatarDiferenca(final - parseInt(linha.dataset.sistema, 10));
        });
        atualizarResumo();
    });

    function coletarContagens() {
        const modo3 = modo3Ativo();
        const container = modo3 ? lista3 : lista1;
        const contagens = [];
        let divergencias = 0;
        let ajusteTotal = 0;
        let paraRevisar = 0;

        container.querySelectorAll("tr[data-produto]").forEach(linha => {
            const { final, divergente } = lerLinha(linha, modo3);
            if (final === null) return;

            contagens.push({ produto_id: Number(linha.dataset.produto), contagem: final });
            const diferenca = final - parseInt(linha.dataset.sistema, 10);
            if (diferenca !== 0) {
                divergencias++;
                ajusteTotal += diferenca;
            }
            if (divergente) paraRevisar++;
        });

        return { contagens, divergencias, ajusteTotal, paraRevisar };
    }

    function atualizarResumo() {
        const { contagens, divergencias, ajusteTotal } = coletarContagens();
        if (!contagens.length) {
            resumo.textContent = "Nenhuma contagem lançada ainda.";
            return;
        }
        const sinalAjuste = ajusteTotal > 0 ? "+" : "";
        resumo.textContent = `${contagens.length} produto(s) contado(s) • ${divergencias} com divergência • ajuste total: ${sinalAjuste}${ajusteTotal} un.`;
    }

    // ---- Finalizar: só os produtos contados entram. O backend calcula
    // a diferença contra o saldo do momento e grava os ajustes. ----
    finalizarBtn.addEventListener("click", async () => {
        const { contagens, divergencias, paraRevisar } = coletarContagens();
        if (!contagens.length) return mostrar(mensagemEl, "Nenhuma contagem lançada.");
        mostrar(mensagemEl, "");

        let texto = `Finalizar o inventário com ${contagens.length} produto(s) contado(s)? ` +
            (divergencias ? `${divergencias} terão o estoque ajustado para o valor contado.` : "Nenhuma divergência — nada será ajustado.");
        if (paraRevisar) texto += ` Atenção: ${paraRevisar} linha(s) ainda estão marcadas para revisar.`;

        const confirmado = await ModaSysModal.confirmar(texto, { textoConfirmar: "Finalizar inventário" });
        if (!confirmado) return;

        finalizarBtn.disabled = true;
        try {
            const resultado = await requisitarJson("inventario", {
                method: "POST",
                body: JSON.stringify({ contagens }),
            });
            mostrar(mensagemEl,
                `Inventário finalizado: ${resultado.produtos_ajustados} produto(s) ajustado(s) ` +
                `(+${resultado.unidades_a_mais} / -${resultado.unidades_a_menos} un.).`, "text-moss");
            await Promise.all([carregarProdutos(), carregarAjustes()]);
            atualizarResumo();
        } catch (erro) {
            mostrar(mensagemEl, erro.message);
        } finally {
            finalizarBtn.disabled = false;
        }
    });

    // ---- Perda / avaria ----
    const perdaForm = document.getElementById("perda-form");
    const perdaProduto = document.getElementById("perda-produto");
    const perdaQuantidade = document.getElementById("perda-quantidade");
    const perdaMotivo = document.getElementById("perda-motivo");
    const perdaMensagem = document.getElementById("perda-mensagem");
    const perdaSubmit = document.getElementById("perda-submit-btn");

    perdaForm.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        if (!perdaProduto.value) return mostrar(perdaMensagem, "Selecione o produto.");
        if (!(parseInt(perdaQuantidade.value, 10) >= 1)) return mostrar(perdaMensagem, "Quantidade precisa ser pelo menos 1.");
        if (!perdaMotivo.value.trim()) return mostrar(perdaMensagem, "Descreva o motivo.");

        const nome = perdaProduto.selectedOptions[0].textContent;
        const confirmado = await ModaSysModal.confirmar(
            `Baixar ${perdaQuantidade.value} un. de ${nome} do estoque como perda/avaria?`,
            { textoConfirmar: "Registrar perda" }
        );
        if (!confirmado) return;

        perdaSubmit.disabled = true;
        try {
            await requisitarJson("inventario/perdas", {
                method: "POST",
                body: JSON.stringify({
                    produto_id: perdaProduto.value,
                    quantidade: perdaQuantidade.value,
                    observacao: perdaMotivo.value.trim(),
                }),
            });
            perdaForm.reset();
            mostrar(perdaMensagem, "Perda registrada.", "text-moss");
            await Promise.all([carregarProdutos(), carregarAjustes()]);
        } catch (erro) {
            mostrar(perdaMensagem, erro.message);
        } finally {
            perdaSubmit.disabled = false;
        }
    });

    // ---- Carregamento ----
    async function carregarProdutos() {
        try {
            const produtos = await requisitarJson("produtos");
            renderizarProdutos(produtos);

            perdaProduto.innerHTML = '<option value="">Selecione o produto...</option>' + produtos
                .filter(p => p.estoque_atual > 0)
                .map(p => `<option value="${p.id}">${escapar(p.codigo_interno)} — ${escapar(p.nome)} (${p.estoque_atual} un.)</option>`)
                .join("");
        } catch (erro) {
            lista1.innerHTML = `<tr><td colspan="5" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
            lista3.innerHTML = `<tr><td colspan="8" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    async function carregarAjustes() {
        const listaAjustes = document.getElementById("ajustes-lista");
        try {
            const ajustes = await requisitarJson("inventario/ajustes");
            listaAjustes.innerHTML = ajustes.length
                ? ajustes.map(a => `
                    <tr>
                        <td>${new Date(a.data + "T00:00:00").toLocaleDateString("pt-BR")}</td>
                        <td><span class="badge ${a.tipo === "Entrada" ? "badge-success" : "badge-danger"}">${a.tipo === "Entrada" ? "+" : "−"}${a.total_unidades} un.</span></td>
                        <td>${escapar(a.origem)}</td>
                        <td><small>${escapar(a.itens_resumo)}</small></td>
                        <td><small>${escapar(a.observacao || "")}</small></td>
                    </tr>`).join("")
                : '<tr><td colspan="5" class="text-muted">Nenhum ajuste registrado ainda.</td></tr>';
        } catch (erro) {
            listaAjustes.innerHTML = `<tr><td colspan="5" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    carregarProdutos();
    carregarAjustes();
});
