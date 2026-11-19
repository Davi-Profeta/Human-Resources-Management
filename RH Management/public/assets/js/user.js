const user = document.querySelector(".createUser");
const drop = document.querySelector(".delete");
const personDelete = document.querySelector("#idExcluir");
const btnUser = document.querySelector("#new");
const btnCancel = document.getElementById("cancel");

/* ========================= CADASTRAR USER ========================= */

btnUser.addEventListener("click", e => {

    user.showModal();

});

btnCancel.addEventListener("click", e => {

    user.close();

});

/* ========================= MESMA SENHA ========================= */

const form = document.querySelector(".createUser form");
const erro = document.getElementById("erro");

form.addEventListener("submit", e => {

    const senha1 = form.elements["password1"].value;
    const senha2 = form.elements["password2"].value;

    if (senha1 !== senha2) {

        e.preventDefault();
        erro.textContent = "As senhas não coincidem.";
        form.elements["password2"].focus();

    }

});

form.elements["password2"].addEventListener("input", () => {

    erro.textContent = "";

});

/* ========================= EXCLUIR USER ========================= */

const btnSair = document.querySelector("#cancelarExcluir");
const corpo = document.querySelector("#corpoTabela");

corpo.addEventListener("click", e => {

    const btn = e.target.closest(".btn-excluir");

    if (!btn) return;

    personDelete.value = btn.dataset.id;

    drop.showModal();

});

btnSair?.addEventListener("click", e => {

    drop.close();

});

/* ========================= PESQUISAR ========================= */

const pesquisa = document.querySelector("#ipt");

function escapar(texto) {

    const div = document.createElement("div");
    div.textContent = texto ?? "";

    return div.innerHTML;

}

function criarLinha(u) {

    return `
        <tr>
            <td>${escapar(u.nome_user)}</td>
            <td>${escapar(u.CPF)}</td>
            <td>${escapar(u.email)}</td>
            <td>${escapar(u.telefone)}</td>
            <td>${escapar(u.endereco)}</td>

            <td>
                <div class="acoes">

                    <button type="button"
                        class="btn-acao btn-editar"
                        data-id="${Number(u.id_acesso)}"
                        data-nivel="${[1, 2].includes(Number(u.nivel)) ? Number(u.nivel) : ''}"
                        title="Editar usuário">

                        <svg viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true">

                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />

                        </svg>

                    </button>

                    <button type="button"
                        class="btn-acao btn-excluir"
                        data-id="${Number(u.id_acesso)}"
                        title="Apagar usuário"
                        aria-label="Apagar usuário">

                        <svg viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true">

                            <path d="M3 6h18" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />

                        </svg>

                    </button>

                </div>
            </td>
        </tr>
    `;

}

pesquisa.addEventListener("input", _.throttle(async e => {

    try {

        const { data } = await axios.get(
            "../../src/APIs/buscarUser.php", {

                params: { user: e.target.value }

            }
        );

        corpo.innerHTML = data.length
            ? data.map(criarLinha).join("")
            : `<tr><td colspan="6">Nenhum usuário encontrado.</td></tr>`;

    } catch (error) {

        console.log(error);

    }

}, 300));

/* ========================= EDITAR USER ========================= */

const edit = document.querySelector(".user");
const idEditar = document.querySelector("#idEditar");
const cancelarEditar = document.querySelector("#cancelarEditar");

corpo.addEventListener("click", e => {

    const btn = e.target.closest(".btn-editar");

    if (!btn) return;

    const campos = edit.querySelector("form").elements;
    const linha = btn.closest("tr").cells;

    // ID do usuário
    idEditar.value = btn.dataset.id;

    // Preencher os campos do formulário
    campos.name.value = linha[0].textContent;
    campos.CPF.value = linha[1].textContent;
    campos.email.value = linha[2].textContent;
    campos.tel.value = linha[3].textContent;
    campos.local.value = linha[4].textContent;
    campos.nivel.value = btn.dataset.nivel || "";

    // Limpar campos de senha
    campos.password1.value = "";
    campos.password2.value = "";

    document.querySelector("#erroEditar").textContent = "";

    // Abrir modal
    edit.showModal();

});

/* ========================= CANCELAR EDIÇÃO ========================= */

cancelarEditar.addEventListener("click", () => {

    edit.close();

});

/* ========================= VALIDAR SENHA EDIÇÃO ========================= */

const formEditar = edit.querySelector("form");

formEditar.addEventListener("submit", e => {

    if (formEditar.elements.password1.value !==
        formEditar.elements.password2.value) {

        e.preventDefault();

        document.querySelector("#erroEditar").textContent =
            "As senhas não coincidem.";

    }

});
