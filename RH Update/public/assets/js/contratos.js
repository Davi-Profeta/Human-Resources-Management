const busca = document.getElementById('ipt');
const vazio = document.getElementById('vazio');
const itens = document.querySelectorAll('.vaga');

let filtro = '';

// pesquisa e filtro de status juntos
function filtrar() {
    const texto = busca.value.trim().toLowerCase();
    let achou = 0;

    itens.forEach(item => {
        item.hidden = !item.dataset.busca.includes(texto) || (filtro && item.dataset.status !== filtro);
        if (!item.hidden) achou++;
    });

    vazio.hidden = achou > 0;
}

busca.oninput = filtrar;

document.querySelectorAll('.chip').forEach(chip => {
    chip.onclick = () => {
        document.querySelector('.chip.ativo').classList.remove('ativo');
        chip.classList.add('ativo');
        filtro = chip.dataset.filtro;
        filtrar();
    };
});
