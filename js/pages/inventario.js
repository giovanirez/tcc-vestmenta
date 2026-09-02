document.addEventListener("DOMContentLoaded", () => {
    const abas = document.querySelectorAll(".tab-switch-btn");
    const modo1 = document.getElementById("inventario-modo-1");
    const modo3 = document.getElementById("inventario-modo-3");
    const resumo = document.getElementById("inventario-resumo");

    if (!abas.length) return;

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

    function formatarDiferenca(diferenca) {
        if (diferenca === 0) return `<span class="text-moss">0</span>`;
        const sinal = diferenca > 0 ? "+" : "";
        return `<span class="text-rust">${sinal}${diferenca}</span>`;
    }

    // ---- Modo 1 Contagem ----
    function recalcularModo1() {
        modo1.querySelectorAll("tbody tr").forEach(linha => {
            const sistema = parseInt(linha.dataset.sistema, 10);
            const input = linha.querySelector(".contagem-input");
            const celulaDiferenca = linha.querySelector(".diferenca-cell");
            if (input.value === "") {
                celulaDiferenca.innerHTML = "—";
                return;
            }
            const contagem = parseInt(input.value, 10) || 0;
            celulaDiferenca.innerHTML = formatarDiferenca(contagem - sistema);
        });
        atualizarResumo();
    }

    modo1.querySelectorAll(".contagem-input").forEach(input => {
        input.addEventListener("input", recalcularModo1);
    });

    // ---- Modo 3 Contagens (mediana) ----
    function mediana(a, b, c) {
        return [a, b, c].sort((x, y) => x - y)[1];
    }

    function recalcularModo3() {
        modo3.querySelectorAll("tbody tr").forEach(linha => {
            const sistema = parseInt(linha.dataset.sistema, 10);
            const c1 = linha.querySelector(".contagem-c1");
            const c2 = linha.querySelector(".contagem-c2");
            const c3 = linha.querySelector(".contagem-c3");
            const celulaFinal = linha.querySelector(".final-cell");
            const celulaDiferenca = linha.querySelector(".diferenca-cell");

            if (c1.value === "" || c2.value === "" || c3.value === "") {
                celulaFinal.innerHTML = "—";
                celulaDiferenca.innerHTML = "—";
                return;
            }

            const v1 = parseInt(c1.value, 10) || 0;
            const v2 = parseInt(c2.value, 10) || 0;
            const v3 = parseInt(c3.value, 10) || 0;
            const final = mediana(v1, v2, v3);
            const divergente = !(v1 === v2 && v2 === v3);

            celulaFinal.innerHTML = divergente
                ? `${final} <span class="badge badge-warning" style="margin-left: 4px;">Revisar</span>`
                : `${final}`;
            celulaDiferenca.innerHTML = formatarDiferenca(final - sistema);
        });
        atualizarResumo();
    }

    modo3.querySelectorAll(".contagem-c1, .contagem-c2, .contagem-c3").forEach(input => {
        input.addEventListener("input", recalcularModo3);
    });

    // ---- Resumo geral ----
    function atualizarResumo() {
        const modo3ativo = modo3.style.display !== "none";
        const container = modo3ativo ? modo3 : modo1;
        let divergencias = 0;
        let ajusteTotal = 0;
        let contadas = 0;

        container.querySelectorAll("tbody tr").forEach(linha => {
            const sistema = parseInt(linha.dataset.sistema, 10);
            let valorFinal = null;

            if (modo3ativo) {
                const c1 = linha.querySelector(".contagem-c1").value;
                const c2 = linha.querySelector(".contagem-c2").value;
                const c3 = linha.querySelector(".contagem-c3").value;
                if (c1 !== "" && c2 !== "" && c3 !== "") {
                    valorFinal = mediana(parseInt(c1, 10) || 0, parseInt(c2, 10) || 0, parseInt(c3, 10) || 0);
                }
            } else {
                const input = linha.querySelector(".contagem-input").value;
                if (input !== "") valorFinal = parseInt(input, 10) || 0;
            }

            if (valorFinal !== null) {
                contadas++;
                const diferenca = valorFinal - sistema;
                if (diferenca !== 0) {
                    divergencias++;
                    ajusteTotal += diferenca;
                }
            }
        });

        if (contadas === 0) {
            resumo.textContent = "Nenhuma contagem lançada ainda.";
        } else {
            const sinalAjuste = ajusteTotal > 0 ? "+" : "";
            resumo.textContent = `${contadas} produto(s) contado(s) • ${divergencias} com divergência • ajuste total: ${sinalAjuste}${ajusteTotal} un.`;
        }
    }
});
