<?php

    require_once "../../src/config/conexao.php";
    session_start();

    if($_SERVER ['REQUEST_METHOD'] === "POST"){

        $Acao = $_POST['acao'];

        if($Acao === "new"){

            $nameEnterprice = $_POST['name'];
            $CNPJ = $_POST['cnpj'];
            $Email = $_POST['email'];
            $Tel = $_POST['tel'];
            $Endereco = $_POST['local'];
            $Razao = $_POST['RS'];


        };

    };

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/empresas.css">
    <title>Document</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="top1">

                <div class="img">
                    <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10 12h4" />
                        <path d="M10 8h4" />
                        <path d="M14 21v-3a2 2 0 0 0-4 0v3" />
                        <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2" />
                        <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16" />
                    </svg>
                </div>

                <div class="text">
                    <h3>Empresas</h3>
                    <p>Gerencie as empresas que trabalham diretamente para você</p>
                </div>

            </div>

            <div class="top2">

                <div class="profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>

            </div>

            <button type="button" id="novo">Nova empresa</button>

        </header>

        <section>

            <input placeholder="Pesquise por nome" type="search" id="ipt" aria-label="Pesquisar benefícios">

            <select id="filtroStatus" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>

        </section>

        <main>

            <div class="tabela">

                <table id="tabela">

                    <thead>
                        <tr>
                            <th>Nome Empresa</th>
                            <th>CNPJ</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Endereço</th>
                            <th>Razão Social</th>
                            <th>Colaboradores</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php

                        $listaEmpresas = $pdo->prepare("SELECT *
                        FROM empresas");
                        $listaEmpresas->execute();

                        $empresa = $listaEmpresas->fetch(PDO::FETCH_BOTH);

                        if($empresa){

                            foreach($empresa as $i){

                            echo "<tr>
                                    <td>{$empresa['nome_empresa']}</td>
                                    <td>{$empresa['cnpj']}</td>
                                    <td>{$empresa['email']}</td>
                                    <td>{$empresa['telefone']}</td>
                                    <td>{$empresa['endereco']}</td>
                                    <td>{$empresa['razao_social']}</td>
                                    <td>{$empresa['colaboradores']}</td>
                                    <td>{$empresa['status']}</td>
                                    <td>
                                        <a href='editar.php?id={$empresa['id']}' class='btn-editar'>Editar</a>
                                        <a href='excluir.php?id={$empresa['id']}' class='btn-excluir'>Excluir</a>
                                    </td>
                                </tr>";

                            };

                        } else {

                            echo "<tr><td colspan='9'>Nenhuma empresa encontrada.</td></tr>";

                        };

                    ?>
                    </tbody>

                </table>


            </div>

        </main>
    
    </div>

    <dialog class="newEnterprice">

          <div class="topModal">
            <h3>Cadastrar Empresa</h3>
        </div>
        
        <div class="mainModal">

            <form action="empresa.php" method="post">

                <div class="ipt">

                    <div class="separation">
                        <label for="">Nome Empresa</label>
                        <input required type="text" name="name" id="">
                    </div>

                    <div class="separation">
                        <label for="">CNPJ</label>
                        <input required maxlength="11" type="num" name="cnpj" id="">
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
                        <label for="">Razão Social</label>
                        <input type="text" name="RS" id="">
                    </div>  

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="">Colaboradores</label>
                        <input required type="num" name="password1" id="">
                    </div>

                    <div class="separation">
                        <label for="">Status</label>
                        <select name="" id="">
                            <option value="">Ativo</option>
                            <option value="">Inativa</option>
                        </select>
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
                <input type="hidden" name="acao" value="new">

            </form>

        </div>

    </dialog>
    
</body>
    <script src="../assets/js/empresas.js"></script>
</html>