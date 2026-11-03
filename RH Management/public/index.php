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
                $erro = "Usuário ou senha incorretos";
            } else {

                $Nivel = $usuario['nivel'];

                if($Nivel == 1){

                    $_SESSION['User'] = $User;
                    $_SESSION['Nivel'] = $Nivel;
                    header("Location: mainAdm.php");
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
    <title>Document</title>

</head>

<body>

    <main>

        <header>
            <img src="" alt="">
        </header>

        <section>

            <div class="container">
                
                <div class="boas">
                    <h3> Bem-vindo! </h3>
                    <p>Faça seu login para acessar o sistema!</p>
                </div>

                <form class="form" action="index.php" method="post">

                    <div class="input">
                        <label for="">Usuário</label>
                        <input class="ipt" type="text" name="user" id="">
                    </div>

                    <div class="input">
                        <label for="">Senha</label>
                        <input class="ipt" type="password" name="password" id="">
                    </div>

                    <div class="options">
                        <a href="">Esqueceu a senha?</a>
                        <?php 

                            if($erro){
                                echo "<p class='mensagem-erro'> {$erro} </p>";
                            };
                            
                        ?>
                    </div>

                    <div class="confirm">
                        <button id="btn"> Entrar </button>
                    </div>

                </form>

            </div>

        </section>
    
    </main>
    
</body>

</html>
