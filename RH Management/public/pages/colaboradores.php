<?php

    require_once "../../src/config/conexao.php";
    session_start();

    if($_SERVER ['REQUEST_METHOD'] === 'POST'){

        $Acao = $_POST['acao'];

        if($Acao === "create"){

            $Name = $_POST['name'];
            $CPF = $_POST['cpf'];
            $Email = $_POST['email'];
            $Endereco = $_POST['local'];
            $Tel = $_POST['tel'];

            $Photo = null;

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {

                    $pasta = "../assets/uploads/";
                    if (!is_dir($pasta)) mkdir($pasta, 0777, true);

                    $Photo = uniqid() . "." . $ext; // nome único, evita sobrescrever
                    move_uploaded_file($_FILES['photo']['tmp_name'], $pasta . $Photo);

                }

            }

            $stmtC = $pdo->prepare("INSERT INTO colaboradores(nome_user, CPF, email, endereço, telefone, img)
            VALUES (?, ?, ?, ?, ?, ?)");
            $stmtC->execute([$Name, $CPF, $Email, $Endereco, $Tel, $Photo]);

            

        };


        // ===== NOVO: atualizar colaborador =====
        if($Acao === "update"){

            $Id = $_POST['id'];
            $Name = $_POST['name'];
            $CPF = $_POST['cpf'];
            $Email = $_POST['email'];
            $Endereco = $_POST['local'];
            $Tel = $_POST['tel'];

            $stmtU = $pdo->prepare("UPDATE colaboradores
            SET nome_user = ?, CPF = ?, email = ?, endereço = ?, telefone = ?
            WHERE id_colaboradores = ?");
            $stmtU->execute([$Name, $CPF, $Email, $Endereco, $Tel, $Id]);

            // só troca a foto se uma nova foi enviada
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {

                    $pasta = "../assets/uploads/";
                    if (!is_dir($pasta)) mkdir($pasta, 0777, true);

                    $Photo = uniqid() . "." . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $pasta . $Photo);

                    $stmtP = $pdo->prepare("UPDATE colaboradores SET img = ? WHERE id_colaboradores = ?");
                    $stmtP->execute([$Photo, $Id]);

                }

            }

        };


    };

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

            <input placeholder="Pesquise por nome" type="search" id="ipt" aria-label="Pesquisar colaboradores">

            <select id="filtroStatus" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>

        </section>

        <main>

            <div class="colaboradores">

                <?php

                   $stmt = $pdo->prepare("SELECT colaboradores.*,
                        vinculo_trabalista.cargo,
                        empresas.nome_empresas,
                        statuss.status_agora
                    FROM colaboradores
                    LEFT JOIN vinculo_trabalista ON colaboradores.id_trabalista = vinculo_trabalista.id_trabalista
                    LEFT JOIN statuss ON colaboradores.id_status = statuss.id_status
                    LEFT JOIN empresas ON empresas.id_empresas = colaboradores.id_empresa;");
                    $stmt->execute();

                    $colaboradores = $stmt->fetchAll(PDO::FETCH_BOTH);

                    foreach ($colaboradores as $i){

                        $nome    = $i['nome_user'];
                        $cargo   = $i['cargo'] ?? 'Sem cargo';
                        $empresa = $i['nome_empresas'] ?? 'Sem empresa';
                        $status  = $i['status_agora'] ?? 'Sem vínculo';
                        $Id = $i['id_colaboradores'];

                        $classe = 'inativo';
                        if (strtolower($status) === 'ativo') $classe = 'ativo';
                        if (!isset($i['status_agora']))      $classe = 'sem';

                        $foto = $i['img']
                            ? "<img src='../assets/uploads/{$i['img']}' alt=''>"
                            : "<span>" . strtoupper(substr($i['nome_user'], 0, 2)) . "</span>";

                        $dNome  = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
                        $dCpf   = htmlspecialchars($i['CPF'] ?? '', ENT_QUOTES, 'UTF-8');
                        $dEmail = htmlspecialchars($i['email'] ?? '', ENT_QUOTES, 'UTF-8');
                        $dLocal = htmlspecialchars($i['endereço'] ?? '', ENT_QUOTES, 'UTF-8');
                        $dTel   = htmlspecialchars($i['telefone'] ?? '', ENT_QUOTES, 'UTF-8');

                        echo "
                            <div class='person' tabindex='0' data-id='{$Id}' data-status='{$classe}' data-nome='{$dNome}' data-cpf='{$dCpf}' data-email='{$dEmail}' data-local='{$dLocal}' data-tel='{$dTel}'>

                                <div class='photo'>{$foto}</div>

                                <div class='dados'>
                                    <h3>{$nome}</h3>
                                    <p class='cargo'>{$cargo}</p>
                                    <p class='empresa'>{$empresa}</p>
                                    <span class='status {$classe}'>{$status}</span>
                                </div>

                            </div>
                        ";

                    }

                ?> 

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
                <input type="file" name="photo" id="" accept="image/*">
            </div>

            <div class="aba">

                <div class="inputs">
                    <label for="">Nome colaorador</label>
                    <input type="text" name="name" id="">
                </div>

                <div class="inputs">
                    <label for="">CPF</label>
                    <input maxlength="11" type="number" name="cpf" id="">
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
                    <input maxlength="13" type="number" name="tel" id="">
                </div>

            </div>

            <div class="aba">

                <p class='erro' id="erro">  </p>
                <input type="hidden" name="id" id="idColaborador">

                <div class="btns">
                    <button type="submit">Confirmar</button>
                    <button type="button" id="cancelColaborador">Cancelar</button>
                </div>

            </div>

            <!-- input para ação no PHP -->
            <input type="hidden" name="acao" value="create">

        </form>

    </dialog>


    <dialog class="edit create">

         <div class="title">
            <h3>Editar colaborador</h3>
        </div>

        <form action="colaboradores.php" method="post" enctype="multipart/form-data">

            <div class="abaIMG">
                <label for="">Foto colaborador</label>
                <input type="file" name="photo" id="" accept="image/*">
            </div>

            <div class="aba">

                <div class="inputs">
                    <label for="">Nome colaorador</label>
                    <input type="text" name="name" id="">
                </div>

                <div class="inputs">
                    <label for="">CPF</label>
                    <input maxlength="11" type="num" name="cpf" id="">
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
                    <input maxlength="13" type="number" name="tel" id="">
                </div>

            </div>

            <div class="aba">

                <p class='erro' id="erro">  </p>
                <input type="hidden" name="id" id="idColaborador">

                <div class="btns">
                    <button type="submit">Confirmar</button>
                    <button type="button" id="cancelEdit">Cancelar</button>
                </div>

            </div>

           
            <input type="hidden" name="acao" value="update">

        </form>

    </dialog>

</body>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../assets/js/colaboradores.js"></script>
</html>
