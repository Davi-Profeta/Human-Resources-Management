const dialog = document.getElementById('dialog');

// abrir e fechar o modal de cargo
document.getElementById('novo').onclick = () => {
    dialog.querySelector('form').reset();
    dialog.showModal();
};
document.getElementById('cancelar').onclick = () => dialog.close();
dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
});

const pendentes = new Map();

// redesenha o cartão (selo, barra, botão) e os totais do topo
function atualizar(card) {
    const atual = +card.dataset.atual;
    const ideal = +card.dataset.ideal;
    const vagas = +card.dataset.vagas;
    const falta = ideal - atual;
    const selo  = card.querySelector('.selo');
    const botao = card.querySelector('.solicitar');

    card.querySelector('.valor').textContent = ideal;
    card.querySelector('.barra span').style.width = (ideal > 0 ? Math.min(100, atual / ideal * 100) : 100) + '%';

    selo.className = 'selo ' + (falta > 0 ? 'falta' : falta === 0 ? 'ok' : 'sobra');
    selo.textContent = falta > 0 ? falta + ' necessária(s)' : falta === 0 ? 'Completo' : -falta + ' excedente(s)';

    botao.hidden   = falta <= 0;
    botao.disabled = vagas > 0;
    botao.textContent = vagas > 0 ? 'Vaga em andamento (' + vagas + ')' : 'Solicitar contratação';

    let totalAtual = 0, totalIdeal = 0, totalFalta = 0;
    document.querySelectorAll('.cargo').forEach(c => {
        totalAtual += +c.dataset.atual;
        totalIdeal += +c.dataset.ideal;
        totalFalta += Math.max(0, c.dataset.ideal - c.dataset.atual);
    });
    document.getElementById('totalAtual').textContent = totalAtual;
    document.getElementById('totalIdeal').textContent = totalIdeal;
    document.getElementById('totalFalta').textContent = totalFalta;
}

// grava o ideal no banco
function salvar(card) {
    const dados = new FormData();
    dados.append('acao', 'ideal');
    dados.append('empresa', EMPRESA);
    dados.append('cargo', card.dataset.cargo);
    dados.append('ideal', card.dataset.ideal);
    dados.append('antes', card.dataset.salvo);
    card.dataset.salvo = card.dataset.ideal;
    return fetch('planejamento.php', { method: 'POST', body: dados });
}

document.querySelectorAll('.cargo').forEach(card => {

    card.dataset.salvo = card.dataset.ideal;
    atualizar(card);

    // − e +: muda na hora e grava depois de uma pausa (um só registro no histórico)
    const mudar = passo => {
        card.dataset.ideal = Math.max(0, Math.min(999, +card.dataset.ideal + passo));
        atualizar(card);
        clearTimeout(pendentes.get(card));
        pendentes.set(card, setTimeout(() => {
            pendentes.delete(card);
            salvar(card);
        }, 500));
    };
    card.querySelector('.menos').onclick = () => mudar(-1);
    card.querySelector('.mais').onclick  = () => mudar(1);

    // antes de solicitar, garante que o ideal novo já foi gravado
    let liberado = false;
    card.querySelector('form').addEventListener('submit', async event => {
        if (liberado || !pendentes.has(card)) return;
        event.preventDefault();
        clearTimeout(pendentes.get(card));
        pendentes.delete(card);
        await salvar(card);
        liberado = true;
        event.target.requestSubmit(event.submitter);
    });
});
