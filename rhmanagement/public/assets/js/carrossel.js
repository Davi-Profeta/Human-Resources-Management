(() => {

    const input = document.getElementById("fotosInput");
    const fotos = document.getElementById("fotos");
    const indicadores = document.getElementById("indicadores");
    const status = document.getElementById("statusFotos");
    const limpar = document.getElementById("limparFotos");

    const chave = "rhs-fotos-login-v1";

    let imagens = [];
    let atual = 0;
    let timer;
    let ocupado = false;

    /* ====================== MOSTRAR FOTO ====================== */

    function mostrar(i) {

        atual = i;

        [...fotos.children].forEach((el, j) => {

            el.classList.toggle("ativa", j === i);

        });

        [...indicadores.children].forEach((el, j) => {

            el.setAttribute("aria-current", String(j === i));

        });

    }

    /* ====================== TRANSIÇÃO AUTOMÁTICA ====================== */

    function iniciar() {

        clearInterval(timer);

        if (imagens.length > 1 &&
            !matchMedia("(prefers-reduced-motion: reduce)").matches) {

            timer = setInterval(() => {

                mostrar((atual + 1) % imagens.length);

            }, 5000);

        }

    }

    /* ====================== RENDERIZAR CARROSSEL ====================== */

    function renderizar() {

        fotos.replaceChildren();
        indicadores.replaceChildren();

        imagens.forEach((src, i) => {

            const img = document.createElement("img");

            img.src = src;
            img.alt = "";

            fotos.append(img);

            const btn = document.createElement("button");

            btn.type = "button";
            btn.setAttribute("aria-label", `Foto ${i + 1}`);

            btn.onclick = () => {

                mostrar(i);
                iniciar();

            };

            indicadores.append(btn);

        });

        limpar.hidden = !imagens.length;

        mostrar(0);
        iniciar();

        status.textContent = 
            `${imagens.length} de 3 fotos adicionadas.`;

    }

    /* ====================== PREPARAR IMAGEM ====================== */

    async function preparar(file) {

        const formatos = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!formatos.includes(file.type) ||
            file.size > 15 * 1024 * 1024) {

            throw Error(
                "Use imagens JPG, PNG ou WebP de até 15 MB."
            );

        }

        const url = URL.createObjectURL(file);

        try {

            const img = new Image();

            img.src = url;

            await img.decode();

            const escala = Math.min(
                1,
                1600 / Math.max(img.width, img.height)
            );

            const canvas = document.createElement("canvas");

            canvas.width = Math.max(
                1, Math.round(img.width * escala)
            );

            canvas.height = Math.max(
                1, Math.round(img.height * escala)
            );

            const ctx = canvas.getContext("2d");

            ctx.fillStyle = "#101725";

            ctx.fillRect(
                0, 0, canvas.width, canvas.height
            );

            ctx.drawImage(
                img, 0, 0, canvas.width, canvas.height
            );

            return canvas.toDataURL("image/jpeg", 0.75);

        } finally {

            URL.revokeObjectURL(url);

        }

    }

    /* ====================== ADICIONAR FOTOS ====================== */

    input.addEventListener("change", async () => {

        const arquivos = [...input.files];

        input.value = "";

        if (!arquivos.length || ocupado) return;

        if (imagens.length + arquivos.length > 3) {

            status.textContent =
                "Você pode adicionar no máximo 3 fotos. Remova as atuais para trocar.";

            return;

        }

        ocupado = true;

        input.disabled = true;
        limpar.disabled = true;

        try {

            const novas = await Promise.all(
                arquivos.map(preparar)
            );

            imagens = [...imagens, ...novas];

            renderizar();

            try {

                localStorage.setItem(
                    chave,
                    JSON.stringify(imagens)
                );

            } catch {

                status.textContent =
                    "Fotos adicionadas, mas não foi possível salvá-las neste navegador.";

            }

        } catch (e) {

            status.textContent =
                e.message || "Não foi possível abrir a imagem.";

        } finally {

            ocupado = false;

            input.disabled = false;
            limpar.disabled = false;

        }

    });

    /* ====================== REMOVER FOTOS ====================== */

    limpar.onclick = () => {

        imagens = [];

        renderizar();

        try {

            localStorage.removeItem(chave);

        } catch {}

    };

    /* ====================== CARREGAR FOTOS SALVAS ====================== */

    try {

        const salvas = JSON.parse(
            localStorage.getItem(chave) || "[]"
        );

        if (Array.isArray(salvas)) {

            imagens = salvas.filter(s =>

                typeof s === "string" &&
                s.startsWith("data:image/jpeg;base64,")

            ).slice(0, 3);

        }

    } catch {}

    renderizar();

})();