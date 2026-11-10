<?php

    require_once "../../src/config/conexao.php";
    session_start();

    if (empty($_SESSION['User']) || $_SESSION['Nivel'] != 1) {
        header("Location: index.php");
        exit;
    }

    $Erro = "";

    if($_SERVER ['REQUEST_METHOD'] === "POST"){

        $Acao = $_POST['acao'];

        if($Acao === 'cadastrar'){

            $Name = $_POST['name'];
            $CPF = $_POST['CPF'];
            $Email = $_POST['email'];
            $Tel = $_POST['tel'];
            $Local = $_POST['local'];
            $Nivel = $_POST['nivel'];
            $Pass1 = $_POST['password1'];
            $Pass2 = $_POST['password2'];

            $add = ("INSERT INTO acessos(nome_user, CPF, email, telefone, endereco, senha, nivel)
            VALUES (?,?,?,?,?,?,?)");

            $stmt = $pdo->prepare($add);
            $stmt->execute([$Name, $CPF, $Email, $Tel, $Local, $Pass1, $Nivel]);

        };

        if($Acao === "apagar"){

            $Id = $_POST['id'];
            
            $deletar = ("DELETE FROM acessos
            WHERE id_acesso = ?");

            $stmtA = $pdo->prepare($deletar);
            $stmtA->execute([$Id]);

        };

    };

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/user.css">
    <title>Usuários</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="top1">

                <div class="img">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>

                <div class="text">
                    <h3>Usuários</h3>
                    <p>Gerencie os usuários do sistema</p>
                </div>

            </div>

            <div class="top2">

                <div class="profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>

                <div class="newUser">
                    <button id="new">Novo usuário</button>
                </div>

            </div>

        </header>

        <section>

            <input placeholder="Pesquise por nome" type="text" name="" id="ipt">

        </section>

        <main>

            <table>

                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody id="corpoTabela">

                <?php

                    $users = ("SELECT *
                    FROM acessos ");

                    $stmt = $pdo->prepare($users);
                    $stmt->execute();
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach($usuarios as $resultado){
                        $nome     = htmlspecialchars($resultado['nome_user']);
                        $cpf      = htmlspecialchars($resultado['CPF']);
                        $email    = htmlspecialchars($resultado['email']);
                        $telefone = htmlspecialchars($resultado['telefone']);
                        $endereco = htmlspecialchars($resultado['endereco']);

                        echo "
                            <tr>
                                <td>{$nome}</td>
                                <td>{$cpf}</td>
                                <td>{$email}</td>
                                <td>{$telefone}</td>
                                <td>{$endereco}</td>
                                <td>
                                    <div class=\"acoes\">
                                        <a class='btn-acao btn-editar' href='user.php?editar={$resultado['id_acesso']}' title='Editar usuário'>
                                            <svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.75\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">
                                                <path d=\"M12 20h9\" />
                                                <path d=\"M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z\" />
                                            </svg>
                                        </a>
                                        <button type='button' class='btn-acao btn-excluir'
                                                data-id='{$resultado['id_acesso']}'
                                                title='Apagar usuário' aria-label='Apagar usuário'>
                                            <svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>
                                                <path d='M3 6h18' />
                                                <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6' />
                                                <path d='M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' />
                                                <line x1='10' y1='11' x2='10' y2='17' />
                                                <line x1='14' y1='11' x2='14' y2='17' />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ";
                    }

                ?>

                </tbody>

            </table>

        </main>

    </div>


    <dialog class="createUser">

        <div class="topModal">
            <h3>Cadastrar Usuário</h3>
        </div>
        
        <div class="mainModal">

            <form action="user.php" method="post">

                <div class="ipt">

                    <div class="separation">
                        <label for="">Nome Completo</label>
                        <input required type="text" name="name" id="">
                    </div>

                    <div class="separation">
                        <label for="">CPF</label>
                        <input required maxlength="11" type="num" name="CPF" id="">
                    </div>
                    
                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="">Email</label>
                        <input required type="text" name="email" id="">
                    </div>

                     <div class="separation">
                        <label for="">Telefone</label>
                        <input required maxlength="13" type="tel" name="tel" id="">
                    </div>
                    
                </div>

                <div class="ipt">
                    
                    <div class="separation">
                        <label for="">Endereço</label>
                        <input required type="text" name="local" id="">
                    </div>

                    <div class="separation">
                        <label for="">Nivel de Usuário</label>
                        <select name="nivel" id="">
                            <option value="1"> Administrador </option>
                            <option value="2"> Gerente RH </option>
                        </select>
                    </div>  

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="">Senha</label>
                        <input required type="password" name="password1" id="">
                    </div>

                    <div class="separation">
                        <label for="">Confirmar Senha</label>
                        <input required type="password" name="password2" id="">
                    </div>

                </div>

                <div class="ipt">  
                    
                    <p class='erro' id="erro">  </p>

                    <div class="btns">
                        <button type="submit">Confirmar</button>
                        <button type="button" id="cancel">Cancelar</button>
                    </div>

                </div>
                
                <!-- input para ação no PHP -->
                <input type="hidden" name="acao" value="cadastrar">

            </form>

        </div>
        
    </dialog>

    <dialog class="delete">

        <form action="user.php" method="post">
            <h3>Apagar usuário</h3>
            <p>Tem certeza que deseja apagar este usuário?</p>

            <input type="hidden" name="id" id="idExcluir">
            <input type="hidden" name="acao" value="apagar">

            <div class="dialog-botoes">
                <button type="button" id="cancelarExcluir">Cancelar</button>
                <button type="submit" class="excluir">Apagar</button>
            </div>
        </form>

    </dialog>

    <dialog class="user">


    </dialog>
    
</body>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../assets/js/user.js"></script>
</html>
