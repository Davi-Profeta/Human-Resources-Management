/* ===================== MENU DO USUÁRIO (barra lateral) ===================== */
const perfil = document.getElementById("perfil");

if (perfil) {

    const barra = perfil.closest("section");   // a barra lateral
    const botao = document.getElementById("perfilBtn");
    const menu = document.getElementById("perfilMenu");
    const dialogConta = document.getElementById("dialogConta");

    function abrir() {
        menu.hidden = false;
        botao.setAttribute("aria-expanded", "true");
        barra.classList.add("menu-aberto"); // mantém a barra aberta enquanto o menu está aberto
    }

    function fechar() {
        menu.hidden = true;
        botao.setAttribute("aria-expanded", "false");
        barra.classList.remove("menu-aberto");
    }

    botao.addEventListener("click", () => {
        menu.hidden ? abrir() : fechar();
    });

    // clicar em qualquer outro lugar da página fecha o menu
    document.addEventListener("click", e => {
        if (!perfil.contains(e.target)) fechar();
    });

    // clicar dentro da tela (iframe) tira o foco desta janela: também fecha
    window.addEventListener("blur", fechar);

    document.addEventListener("keydown", e => {
        if (e.key === "Escape" && !menu.hidden) {
            fechar();
            botao.focus();
        }
    });

    /* ---------- Minha conta ---------- */
    document.getElementById("abrirConta").addEventListener("click", () => {
        fechar();
        dialogConta.showModal();
    });

    document.getElementById("fecharConta").addEventListener("click", () => dialogConta.close());

    // clicar fora da caixa (no fundo escuro) também fecha
    dialogConta.addEventListener("click", e => {
        if (e.target === dialogConta) dialogConta.close();
    });

}

/* ======================= TELA DE SAÍDA ======================= */
const linkSair = document.querySelector(".perfil-item.sair");

linkSair.addEventListener("click", (e) => {
    e.preventDefault();                       // segura a navegação

    // cria a tela de carregamento por cima de tudo (inclusive do iframe)
    const tela = document.createElement("div");
    tela.className = "saindo";
    tela.setAttribute("role", "status");
    tela.innerHTML = '<span class="spinner"></span><p>Saindo...</p>';
    document.body.appendChild(tela);

    setTimeout(() => {
        window.location.href = linkSair.href; // vai para o logout.php
    }, 1500);                                  // 1500 = 1,5 s
});
