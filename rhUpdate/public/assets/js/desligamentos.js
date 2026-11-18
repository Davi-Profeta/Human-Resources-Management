const dialog = document.getElementById('dialog');
const busca  = document.getElementById('ipt');
const vazio  = document.getElementById('vazio');
const itens  = document.querySelectorAll('.desligamento');

// abrir e fechar o modal
document.getElementById('novo').onclick = () => dialog.showModal();
document.getElementById('cancelar').onclick = () => dialog.close();

// pesquisa: esconde o que não combina com o texto digitado
busca.oninput = () => {
    const texto = busca.value.trim().toLowerCase();
    let achou = 0;

    itens.forEach(item => {
        item.hidden = !item.dataset.busca.includes(texto);
        if (!item.hidden) achou++;
    });

    vazio.hidden = achou > 0;
};
