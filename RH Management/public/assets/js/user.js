const user = document.querySelector(".createUser");
const drop = document.querySelector(".delete");
const personDelete = document.querySelector("#idExcluir");
const btnUser = document.querySelector("#new");
const btnCancel = document.getElementById("cancel");

btnUser.addEventListener("click", e => {

    user.showModal();
    user.style.display = "flex";

});

btnCancel.addEventListener("click", e => {

    user.close();
    user.style.display = "none";

});

/* ========================= MESMA SENHA ================================ */
const form = document.querySelector(".createUser form");
const erro = document.getElementById("erro");

form.addEventListener("submit", (e) => {
    const senha1 = form.elements["password1"].value;
    const senha2 = form.elements["password2"].value;

    if (senha1 !== senha2) {
        e.preventDefault(); // não deixa fechar
        erro.textContent = "As senhas não coincidem.";
        form.elements["password2"].focus();
    }
});

form.elements["password2"].addEventListener("input", () => {
    erro.textContent = "";
});

/* ============================= EXCLUIR USER ======================== */
const btnSair = document.querySelector("#cancelarExcluir");
const corpo = document.querySelector("#corpoTabela");

// um único listener no tbody (funciona para linhas do PHP e da busca)
corpo.addEventListener("click", e => {

    const btn = e.target.closest(".btn-excluir");
    if (!btn) return;

    personDelete.value = btn.dataset.id;
    drop.showModal();
    drop.style.display = "flex";

});

btnSair.addEventListener("click", e => {

    drop.close();
    drop.style.display = "none";

});

/*====================== PESQUISAR ======================*/
const pesquisa = document.querySelector("#ipt");

// evita injetar HTML vindo do banco
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
                    <a class="btn-acao btn-editar" href="user.php?editar=${u.id_acesso}" title="Editar usuário">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                    </a>
                    <button type="button" class="btn-acao btn-excluir" data-id="${u.id_acesso}" title="Apagar usuário" aria-label="Apagar usuário">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                    </button>
                </div>
            </td>
        </tr>`;
}

pesquisa.addEventListener("input", _.throttle(async e => {

    try {

        const { data } = await axios.get("../../src/APIs/buscarUser.php", {
            params: { user: e.target.value }
        });

        corpo.innerHTML = data.length
            ? data.map(criarLinha).join("")
            : `<tr><td colspan="6">Nenhum usuário encontrado.</td></tr>`;

    } catch (error) {///
        console.log(error);
    }

}, 300)); // espera 300ms depois que você para de digitar

/* ============================ USER LOGADO ============================ */
const profile = document.querySelector(".profile");
const dialogUser = document.querySelector("dialog.user");

profile.addEventListener("click", async e => {

    try {
        const res = await axios.get("../../src/APIs/loginUser.php");
        dialogUser.innerHTML = res.data;
        dialogUser.showModal();

    } catch (error) {
        console.log("Erro ao buscar login");
    };

});

dialogUser.addEventListener("click", e => {
    if (e.target === dialogUser || e.target.id === "closeUser") {
        dialogUser.close();
    }
    if (e.target.id === "logout") {
        window.top.location.href = new URL(
            "logout.php",
            window.top.location.href
        ).href;
    }
});

