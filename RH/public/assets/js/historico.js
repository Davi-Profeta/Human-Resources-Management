const busca  = document.getElementById('ipt');
const filtro = document.getElementById('filtro');
const vazio  = document.getElementById('vazio');
const itens  = document.querySelectorAll('.registro');

// mostra só o que combina com o texto digitado E com o tipo escolhido
function filtrar() {
    const texto = busca.value.trim().toLowerCase();
    let achou = 0;

    itens.forEach(item => {
        const bateTexto = item.dataset.busca.includes(texto);
        const bateTipo  = filtro.value === '' || item.dataset.tipo === filtro.value;

        item.hidden = !(bateTexto && bateTipo);
        if (!item.hidden) achou++;
    });

    vazio.hidden = achou > 0;
}

busca.oninput = filtrar;
filtro.onchange = filtrar;
