const dialog       = document.getElementById('dialog');
const dialogApagar = document.getElementById('dialogApagar');
const busca        = document.getElementById('ipt');
const vazio        = document.getElementById('vazio');
const vagas        = document.querySelectorAll('.vaga');
const campos       = ['cargo', 'empresa', 'vinculo', 'posicoes', 'modalidade', 'cidade', 'salario', 'descricao'];

let filtro = '';

// modal de vaga: vazio para nova, preenchido para editar
function abrir(dados) {
    dialog.querySelector('form').reset();
    document.getElementById('id').value = dados ? dados.id : '';
    document.getElementById('tituloModal').textContent = dados ? 'Editar vaga' : 'Nova vaga';

    if (dados) {
        campos.forEach(campo => document.getElementById(campo).value = dados[campo] === '0' ? '' : dados[campo]);
    }
    dialog.showModal();
}

document.getElementById('novo').onclick = () => abrir(null);
document.getElementById('cancelar').onclick = () => dialog.close();
document.getElementById('cancelarApagar').onclick = () => dialogApagar.close();

// modal de candidato: vazio para novo, preenchido para editar
const dialogCand = document.getElementById('dialogCand');
const camposCand = ['nome', 'email', 'telefone', 'linkedin', 'origem', 'observacoes'];

function abrirCand(vaga, dados) {
    dialogCand.querySelector('form').reset();
    document.getElementById('candVaga').value = vaga;
    document.getElementById('candId').value = dados ? dados.id : '';
    document.getElementById('tituloCand').textContent = dados ? 'Editar candidato' : 'Adicionar candidato';

    if (dados) {
        camposCand.forEach(campo => document.getElementById('cand' + campo[0].toUpperCase() + campo.slice(1)).value = dados[campo] || '');
    }
    dialogCand.showModal();
}

document.querySelectorAll('.addCand').forEach(botao => botao.onclick = () => abrirCand(botao.dataset.vaga, null));
document.querySelectorAll('.editarCand').forEach(botao => botao.onclick = () => abrirCand(botao.dataset.vaga, botao.dataset));
document.getElementById('cancelarCand').onclick = () => dialogCand.close();

document.querySelectorAll('.editar').forEach(botao => {
    botao.onclick = () => abrir(botao.dataset);
});

document.querySelectorAll('.apagar').forEach(botao => {
    botao.onclick = () => {
        document.getElementById('idApagar').value = botao.dataset.id;
        document.getElementById('nomeApagar').textContent = botao.dataset.nome;
        dialogApagar.showModal();
    };
});

// clique fora fecha
[dialog, dialogApagar, dialogCand].forEach(d => d.addEventListener('click', event => {
    if (event.target === d) d.close();
}));

// pesquisa e filtro de status juntos
function filtrar() {
    const texto = busca.value.trim().toLowerCase();
    let achou = 0;

    vagas.forEach(vaga => {
        vaga.hidden = !vaga.dataset.busca.includes(texto) || (filtro && vaga.dataset.status !== filtro);
        if (!vaga.hidden) achou++;
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

// depois de salvar, volta com a vaga aberta (#vaga-ID)
const aberta = document.querySelector(location.hash || '#_');
if (aberta && aberta.classList.contains('vaga')) {
    aberta.open = true;
    aberta.scrollIntoView({ block: 'nearest' });
}
