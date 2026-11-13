<?php
    require_once "../src/config/conexao.php";
    session_start();

    $erro = "";

    if($_SERVER ['REQUEST_METHOD'] === "POST"){

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

            if(!$usuario){
                $erro = "Usuário ou senha incorretos!";
            } else {

                $Nivel = $usuario['nivel'];

                if($Nivel == 1){

                    $_SESSION['User'] = $User;
                    $_SESSION['Nivel'] = $Nivel;
                    $_SESSION['Email'] = $usuario['email'];
                    header("Location: mainAdm.php");
                    exit();

                } else if ($Nivel == 2){

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Login</title>

</head>

<body>

    <main>

        <!-- Espaço reservado para o carrossel (fica em branco por enquanto) -->
        <aside class="carrossel" aria-hidden="true"></aside>

        <section class="login">

            <div class="container">
                
                <div class="boas">
                    <div class="selo" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3> Bem-vindo! </h3>
                    <p>Faça login para acessar o sistema!</p>
                </div>

                <form class="form" action="index.php" method="post">

                    <div class="input">
                        <label for="user">Usuário</label>
                        <input class="ipt" type="text" name="user" id="user" maxlength="100" required autofocus autocomplete="username"
                               value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">
                    </div>

                    <div class="input">
                        <label for="password">Senha</label>
                        <input class="ipt" type="password" name="password" id="password" required autocomplete="current-password">
                    </div>

                    <div class="options">
                        <a href="#">Esqueceu a senha?</a>
                        <?php

                            if($erro){
                                echo "<p class='mensagem-erro' role='alert'>" . htmlspecialchars($erro) . "</p>";
                            };

                        ?>
                    </div>

                    <div class="confirm">
                        <button id="btn" type="submit">Entrar</button>
                    </div>

                </form>

            </div>
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
    
</body>

</html>
