<?php
require_once "../src/config/conexao.php";
session_start();

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $User = $_POST['user'];
    $Senha = $_POST['password'];
    $Nivel = "";

    if ($User === '' || $Senha === '') {

        $erro = "Preencha o usuário e a senha.";
    } else {

        $buscar = ("SELECT *
            FROM acessos
            WHERE nome_user = ? AND senha = ?");

        $stmtB = $pdo->prepare($buscar);
        $stmtB->execute([$User, $Senha]);
        $usuario = $stmtB->fetch(PDO::FETCH_BOTH);

        if (!$usuario) {
            $erro = "Usuário ou senha incorretos!";
        } else {

            $Nivel = $usuario['nivel'];

            if ($Nivel == 1) {

                $_SESSION['User'] = $User;
                $_SESSION['Nivel'] = $Nivel;
                $_SESSION['Email'] = $usuario['email'];
                header("Location: mainAdm.php");
                exit();
            } else if ($Nivel == 2) {

                $_SESSION['User'] = $User;
                $_SESSION['Nivel'] = $Nivel;
                header("Location: main.php");
                exit();
            };
        }
    };
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Entrar | RH Management</title>

    <link rel="stylesheet" href="assets/css/animacoes.css">
    <script src="assets/js/animacoes.js" defer></script>
</head>

<body>

    <main>

        <!-- Área reservada para o futuro carrossel de imagens da empresa. -->
        <aside class="carrossel" aria-labelledby="titulo-carrossel">
            <div class="fotos" id="fotos" aria-hidden="true"></div>
            <div class="carrossel-conteudo">
                <span class="carrossel-tag">PERSONALIZE SEU ESPAÇO</span>
                <h2 id="titulo-carrossel">O site pode ter sua cara</h2>
                <p>Adicione até 3 fotos que representem sua empresa.</p>
                <label class="escolher-fotos" for="fotosInput">+ Escolher fotos</label>
                <input type="file" id="fotosInput" accept="image/png,image/jpeg,image/webp" multiple>
                <button type="button" id="limparFotos" hidden>Remover fotos</button>
                <p id="statusFotos" role="status" aria-live="polite"></p>
                <div id="indicadores" aria-label="Selecionar foto"></div>
            </div>
        </aside>

        <section class="login" aria-labelledby="titulo-login">

            <div class="container">

                <div class="boas">
                    <div class="marca">
                        <div class="selo" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <span>Human Resources </span>
                    </div>
                    <span class="sobretitulo">SEU ESPAÇO DE TRABALHO</span>
                    <h1 id="titulo-login">Bom ter você por aqui.</h1>
                    <p>Acesse sua conta para continuar.</p>
                </div>

                <form class="form" action="index.php" method="post">

                    <div class="input">
                        <label for="user">Usuário</label>
                        <input class="ipt" type="text" name="user" id="user" maxlength="100" required autofocus
                            autocomplete="username" value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">
                    </div>


                    <div class="input">
                        <label for="password">Senha</label>

                        <div class="campo-senha">
                            <input class="ipt" type="password" name="password" id="password" required
                                autocomplete="current-password">

                            <button type="button" id="mostrarSenha" aria-label="Mostrar senha" aria-pressed="false">

                                <!-- olho aberto -->
                                <svg class="icone-aberto" width="22" height="22" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>

                                <!-- olho cortado -->
                                <svg class="icone-cortado" width="22" height="22" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                    <line x1="3" y1="3" x2="21" y2="21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="options">
                        <?php

                        if ($erro) {
                            echo "<p class='mensagem-erro' role='alert'>" . htmlspecialchars($erro) . "</p>";
                        };

                        ?>
                    </div>

                    <div class="confirm">
                        <button id="btn" type="submit">Entrar na minha conta</button>
                    </div>

                </form>

                <p class="ajuda">Precisa de acesso? Fale com o administrador.</p>

            </div>
            <p class="rodape">Human Recources · Gestão de pessoas</p>
        </section>

        <script>
            const senha = document.getElementById("password");
            const botao = document.getElementById("mostrarSenha");

            botao.addEventListener("click", () => {
                const mostrar = senha.type === "password";

                senha.type = mostrar ? "text" : "password";

                botao.setAttribute(
                    "aria-label",
                    mostrar ? "Ocultar senha" : "Mostrar senha"
                );

                botao.setAttribute("aria-pressed", String(mostrar));
            });
        </script>

    </main>
    <div class="carregando" id="carregando" hidden role="status" aria-live="polite">
        <span class="spinner"></span>
        <p>Entrando...</p>
    </div>
    <script>
        const formLogin = document.querySelector("form");
        const tela = document.getElementById("carregando");

        formLogin.addEventListener("submit", (e) => {
            if (!formLogin.checkValidity()) return;

            e.preventDefault();      
            tela.hidden = false;     

            setTimeout(() => formLogin.submit(), 2000);
        });

        window.addEventListener("pageshow", () => tela.hidden = true);
</script>
    <script src="assets/js/carrossel.js" defer></script>
</body>

</html>
