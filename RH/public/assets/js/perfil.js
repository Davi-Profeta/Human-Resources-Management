/* ===================== MENU DO USUÁRIO (barra lateral) ===================== */
const perfil = document.getElementById("perfil");

if (perfil) {

    const barra = perfil.closest("section");   // a barra lateral
    const botao = document.getElementById("perfilBtn");
    const menu = document.getElementById("perfilMenu");
    const dialogConta = document.getElementById("dialogConta");
    const dialogTema = document.getElementById("dialogTema");
    const inputsTema = {
        primary: document.getElementById("temaPrimaria"),
        secondary: document.getElementById("temaSecundaria"),
        menu: document.getElementById("temaMenu"),
        background: document.getElementById("temaFundo"),
        surface: document.getElementById("temaSuperficie"),
        card: document.getElementById("temaCartao"),
    };

    const temasProntos = {
        azul: { primary: "#2f5bea", secondary: "#14b8a6", menu: "#0f172a", background: "#f6f8fb", surface: "#ffffff", card: "#162036" },
        rubi: { primary: "#e02f55", secondary: "#f59e0b", menu: "#25111d", background: "#fff6f8", surface: "#ffffff", card: "#351a2a" },
        violeta: { primary: "#8157d9", secondary: "#e879f9", menu: "#1d1633", background: "#faf8ff", surface: "#ffffff", card: "#2b2149" },
        esmeralda: { primary: "#0f9f75", secondary: "#f0b429", menu: "#0c2521", background: "#f3fbf8", surface: "#ffffff", card: "#153b35" },
    };

    let temaAntesDeEditar = null;

    function temaDoFormulario() {
        return Object.fromEntries(Object.entries(inputsTema).map(([key, input]) => [key, input.value]));
    }

    function marcarPresetAtivo(tema) {
        const atual = Object.keys(inputsTema).map((k) => String(tema[k]).toLowerCase()).join();
        document.querySelectorAll(".tema-preset").forEach((botaoPreset) => {
            const p = temasProntos[botaoPreset.dataset.tema];
            const igual = Object.keys(inputsTema).map((k) => p[k].toLowerCase()).join() === atual;
            botaoPreset.classList.toggle("ativo", igual);
            botaoPreset.setAttribute("aria-pressed", igual ? "true" : "false");
        });
    }

    function preencherTema(tema) {
        Object.entries(inputsTema).forEach(([key, input]) => {
            input.value = tema[key];
        });
        marcarPresetAtivo(tema);
    }

    function visualizarTema(tema) {
        RHTheme.apply(tema);
        const frame = document.getElementById("iframe");
        frame?.contentWindow?.postMessage({ type: "rh-theme-apply", theme: tema }, "*");
    }

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

    /* ---------- Personalização ---------- */
    document.getElementById("abrirTema").addEventListener("click", () => {
        fechar();
        temaAntesDeEditar = RHTheme.get();
        preencherTema(temaAntesDeEditar);
        dialogTema.showModal();
    });

    Object.values(inputsTema).forEach((input) => {
        input.addEventListener("input", () => {
            const tema = temaDoFormulario();
            marcarPresetAtivo(tema);
            visualizarTema(tema);
        });
    });

    document.querySelectorAll(".tema-preset").forEach((botaoPreset) => {
        botaoPreset.addEventListener("click", () => {
            const tema = temasProntos[botaoPreset.dataset.tema];
            preencherTema(tema);
            visualizarTema(tema);
        });
    });

    document.getElementById("salvarTema").addEventListener("click", () => {
        RHTheme.save(temaDoFormulario());
        dialogTema.close();
    });

    document.getElementById("cancelarTema").addEventListener("click", () => {
        visualizarTema(temaAntesDeEditar || RHTheme.get());
        dialogTema.close();
    });

    document.getElementById("resetarTema").addEventListener("click", () => {
        const tema = RHTheme.reset();
        preencherTema(tema);
        dialogTema.close();
    });

    document.getElementById("fecharTema").addEventListener("click", () => {
        visualizarTema(temaAntesDeEditar || RHTheme.get());
        dialogTema.close();
    });

    dialogTema.addEventListener("click", e => {
        if (e.target === dialogTema) {
            visualizarTema(temaAntesDeEditar || RHTheme.get());
            dialogTema.close();
        }
    });

}

/* ======================= TELA DE SAÍDA ======================= */
const linkSair = document.querySelector(".perfil-item.sair");

linkSair?.addEventListener("click", (e) => {
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
