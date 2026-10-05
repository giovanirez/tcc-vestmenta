document.addEventListener("DOMContentLoaded", async () => {
    const ultimasEl = document.getElementById("dash-ultimas-vendas");
    if (!ultimasEl) return;

    function paraReais(valor) {
        return "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    try {
        const resposta = await ModaSysAuth.requisitar("/dashboard");
        const dados = await resposta.json();
        if (!resposta.ok) throw new Error(dados.erro || "Falha ao carregar.");

        document.getElementById("kpi-vendas-mes").textContent = paraReais(dados.vendas_mes);
        document.getElementById("kpi-qtd-vendas").textContent = `(${dados.qtd_vendas_mes})`;
        document.getElementById("kpi-condicionais").textContent = dados.condicionais_abertas;
        document.getElementById("kpi-produtos-baixa").textContent = dados.produtos_baixa.length;
        document.getElementById("kpi-novos-clientes").textContent = dados.novos_clientes;

        ultimasEl.innerHTML = dados.ultimas_vendas.length
            ? dados.ultimas_vendas.map(v => `
                <tr>
                    <td class="mono">#${v.id}</td>
                    <td>${new Date(v.data_venda).toLocaleDateString("pt-BR")}</td>
                    <td>${escapar(v.cliente_nome || "Cliente Balcão")}</td>
                    <td><strong class="price">${paraReais(v.valor_total)}</strong></td>
                    <td><span class="badge ${v.status === "Cancelada" ? "badge-neutral" : "badge-success"}">${v.status}</span></td>
                    <td>
                        <a class="btn-icon btn-icon--view" title="Ver Recibo" href="recibo.php?venda=${v.id}" target="_blank" style="text-decoration: none;"><i class="fa-solid fa-file-invoice"></i></a>
                    </td>
                </tr>`).join("")
            : '<tr><td colspan="6" class="text-muted">Nenhuma venda registrada ainda.</td></tr>';

        if (dados.produtos_baixa.length) {
            document.getElementById("dash-baixa-limite").textContent = `(${dados.estoque_baixo_limite} un. ou menos)`;
            document.getElementById("dash-baixa-lista").innerHTML = dados.produtos_baixa.map(p => `
                <tr>
                    <td class="mono">${escapar(p.codigo_interno)}</td>
                    <td>${escapar(p.nome)}</td>
                    <td class="mono ${p.estoque_atual <= 0 ? "text-rust" : ""}">${p.estoque_atual} un.</td>
                </tr>`).join("");
            document.getElementById("dash-baixa-card").style.display = "block";
        }
    } catch (erro) {
        ultimasEl.innerHTML = `<tr><td colspan="6" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
    }
});
