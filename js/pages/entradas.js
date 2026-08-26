document.addEventListener("DOMContentLoaded", () => {
    const custo = document.getElementById("item-custo");
    const lucro = document.getElementById("item-lucro");
    const venda = document.getElementById("item-venda");

    if (!custo || !lucro || !venda) return;

    lucro.addEventListener("input", () => {
        const custoVal = parseFloat(custo.value);
        const lucroVal = parseFloat(lucro.value);
        if (!isNaN(custoVal) && !isNaN(lucroVal)) {
            venda.value = (custoVal * (1 + lucroVal / 100)).toFixed(2);
        }
    });

    venda.addEventListener("input", () => {
        const custoVal = parseFloat(custo.value);
        const vendaVal = parseFloat(venda.value);
        if (!isNaN(custoVal) && custoVal > 0 && !isNaN(vendaVal)) {
            lucro.value = (((vendaVal - custoVal) / custoVal) * 100).toFixed(2);
        }
    });

    custo.addEventListener("input", () => {
        const custoVal = parseFloat(custo.value);
        if (isNaN(custoVal)) return;

        const lucroVal = parseFloat(lucro.value);
        if (!isNaN(lucroVal)) {
            venda.value = (custoVal * (1 + lucroVal / 100)).toFixed(2);
            return;
        }

        const vendaVal = parseFloat(venda.value);
        if (custoVal > 0 && !isNaN(vendaVal)) {
            lucro.value = (((vendaVal - custoVal) / custoVal) * 100).toFixed(2);
        }
    });
});
