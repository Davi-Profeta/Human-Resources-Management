<?php

    require_once "../../src/config/conexao.php";
    session_start();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../assets/css/all.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/colaboradores.css">
    <title>Colaboradores</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="top1">

                <div class="img">
                    <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <circle cx="9" cy="7" r="4" />
                    </svg>
                </div>

                <div class="text">
                    <h3>Colaboradores</h3>
                    <p>Gerencie os colaboradores da empresa</p>
                </div>

            </div>

            <div class="top2">

                <div class="profile" title="<?= htmlspecialchars($_SESSION['User'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>

                <button type="button" id="novo">Novo colaborador</button>

            </div>

        </header>

        <section>

            <input placeholder="Pesquise por nome, cargo ou empresa" type="search" id="ipt" aria-label="Pesquisar colaboradores">

            <select id="filtroStatus" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>

        </section>

        <main>

            <div class="colaboradores">

                <!-- <?php

                    $stmt = $pdo->prepare("SELECT *
                    FROM colaboradores
                    INNER JOIN vinculo_trabalista ON colaboradores.id_trabalista = vinculo_trabalista.id_trabalista
                    INNER JOIN statuss ON colaboradores.id_status = statuss.id_status
                    INNER JOIN empresas ON empresas.id_empresas = colaboradores.id_empresa;");
                    $stmt->execute();

                    $colaboradores = $stmt->fetchAll(PDO::FETCH_BOTH);

                    foreach ($colaboradores as $i){

                        echo "
                            <div class='person' tabindex='0'>

                            <div class='photo'>
                                <span>{$i['img']}</span>
                            </div>

                            <div class='dados'>
                                <h3>{$i['nome_user']}</h3>
                                <p class='cargo'>{$i['cargo']}</p>
                                <p class='empresa'>{$i['nome_empresas']}</p>
                                <span class='status'>{$i['status_agora']}</span>
                            </div>

                        </div>
                        ";

                    };

                   

                ?> -->

                <div class="person" tabindex="0" data-status="ativo">

                    <div class="photo">
                        <span>AL</span>
                    </div>

                    <div class="dados">
                        <h3>Ana Beatriz Lima</h3>
                        <p class="cargo">Analista de RH</p>
                        <p class="empresa">Horizonte Construtora</p>
                        <span class="status ativo">Ativo</span>
                    </div>

                </div>

                <div class="person" tabindex="0" data-status="ativo">

                    <div class="photo">
                        <span>CS</span>
                    </div>

                    <div class="dados">
                        <h3>Carlos Eduardo Souza</h3>
                        <p class="cargo">Engenheiro civil</p>
                        <p class="empresa">Horizonte Construtora</p>
                        <span class="status ativo">Ativo</span>
                    </div>

                </div>

                <div class="person" tabindex="0" data-status="inativo">

                    <div class="photo">
                        <span>FR</span>
                    </div>

                    <div class="dados">
                        <h3>Fernanda Rocha</h3>
                        <p class="cargo">Assistente financeiro</p>
                        <p class="empresa">Tech Mineira</p>
                        <span class="status inativo">Desligado</span>
                    </div>

                </div>

                <div class="person" tabindex="0" data-status="ativo">

                    <div class="photo">
                        <span>JA</span>
                    </div>

                    <div class="dados">
                        <h3>João Pedro Alves</h3>
                        <p class="cargo">Desenvolvedor</p>
                        <p class="empresa">Tech Mineira</p>
                        <span class="status ativo">Ativo</span>
                    </div>

                </div>

                <div class="person" tabindex="0" data-status="inativo">

                    <div class="photo">
                        <span>MC</span>
                    </div>

                    <div class="dados">
                        <h3>Mariana Costa</h3>
                        <p class="cargo">Sem cargo</p>
                        <p class="empresa">Sem empresa</p>
                        <span class="status sem">Sem vínculo</span>
                    </div>

                </div>

                <div class="person" tabindex="0" data-status="ativo">

                    <div class="photo">
                        <span>RM</span>
                    </div>

                    <div class="dados">
                        <h3>Rafael Mendes</h3>
                        <p class="cargo">Analista de ponto</p>
                        <p class="empresa">Horizonte Construtora</p>
                        <span class="status ativo">Ativo</span>
                    </div>

                </div>

                <div class="person" tabindex="0" data-status="ativo">

                    <div class="photo">
                        <span>RM</span>
                    </div>

                    <div class="dados">
                        <h3>Rafael Mendes</h3>
                        <p class="cargo">Analista de ponto</p>
                        <p class="empresa">Horizonte Construtora</p>
                        <span class="status ativo">Ativo</span>
                    </div>

                </div>
                

                <p class="vazio" hidden>Nenhum colaborador encontrado.</p>

            </div>

        </main>
    
    </div>


    <dialog class="create">

        <div class="title">
            <h3>Criar colaborador</h3>
        </div>

        <form action="colaboradores.php" method="post" enctype="multipart/form-data">

            <div class="abaIMG">
                <label for="">Foto colaborador</label>
                <input type="file" name="photo" id="">
            </div>

            <div class="aba">

                <div class="inputs">
                    <label for="">Nome colaorador</label>
                    <input type="text" name="name" id="">
                </div>

                <div class="inputs">
                    <label for="">CPF</label>
                    <input type="number" name="cpf" id="">
                </div>

            </div>

            <div class="aba">

                <div class="inputs">
                    <label for="">Email</label>
                    <input type="text" name="email" id="">
                </div>

                <div class="inputs">
                    <label for="">Endereço</label>
                    <input type="text" name="local" id="">
                </div>

            </div>

            <div class="aba">

                <div class="inputs">
                    <label for="">Telefone</label>
                    <input type="number" name="tel" id="">
                </div>

                <div class="inputs">
                    <label for="">Empresa</label>
                    <select name="empresa" id="">
                        <option selected disabled value="">Selecione uma empresa</option>
                       
                        <?php

                            $stmtE = $pdo->prepare("SELECT *
                            FROM empresas");
                            $stmtE->execute();

                            $empresas = $stmtE->fetchAll(PDO::FETCH_BOTH);

                            foreach($empresas as $i){

                                echo "
                                    <option name='{$i['id_empresas']}'> {$i['nome_empresas']} </option>
                                ";

                            };

                        ?>

                    </select>
                </div>

            </div>

            <div class="aba">

                <div class="inputs">
                    <label for="">Benefícios</label>
                    <select name="beneficio" id="">
                        <option value="">Selecione um benefício</option>

                        <?php

                            $stmtB = $pdo->prepare("SELECT *
                            FROM beneficios");
                            $stmtB->execute();

                            $beneficios = $stmtB->fetchAll(PDO::FETCH_BOTH);

                            foreach($beneficios as $i){

                                echo "
                                    <option name='{$i['id_beneficios']}'> {$i['nome_beneficios']} </option>
                                ";

                            };

                        ?>

                    </select>
                </div>

                <div class="inputs">
                    <label for="">Status</label>
                    <select name="status" id="">
                        <option selected disabled value="">Selecione um status</option>
                        <option value="1">Ativo</option>
                        <option value="2">Inativo</option>
                    </select>
                </div>

            </div>

             <div class="aba">

                <div class="inputs">
                    <label for="">Vínculo</label>
                    <select name="vinculo" id="">
                        <option value="">Selecione um vínculo</option>

                        <?php

                            $stmtV = $pdo->prepare("SELECT *
                            FROM vinculo_trabalista");
                            $stmtV->execute();

                            $vinculo = $stmtV->fetchAll(PDO::FETCH_BOTH);

                            foreach($vinculo as $i){

                                echo "
                                    <option name='{$i['id_vinculo']}'> {$i['cargo']} </option>
                                ";

                            };

                        ?>

                    </select>
                </div>

                <div class="inputs">
                    <label for="">Status</label>
                    <select name="status" id="">
                        <option selected disabled value="">Selecione um status</option>
                        <option value="1">Ativo</option>
                        <option value="2">Inativo</option>
                    </select>
                </div>

            </div>


        </form>

    </dialog>

</body>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../assets/js/colaboradores.js"></script>
</html>
