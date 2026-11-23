<?php

    require_once "../../src/config/conexao.php";
    require_once "../../src/config/gravarHistorico.php";
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
            $idNovo = $pdo->lastInsertId();
            gravarHistorico($pdo, $idNovo, 'colaboradores', 'cadastro', '', $Name);

            // veio de uma vaga (botão Contratar): cria o vínculo, marca o candidato e encerra a vaga se completou
            $idCand = (int)($_POST['candidato'] ?? 0);

            $cand = $pdo->prepare("SELECT ca.etapa, v.id_vaga, v.id_empresa, v.id_vinculo, v.salario, v.posicoes, cg.nome_cargo
                FROM candidatos ca JOIN vagas v ON v.id_vaga = ca.id_vaga JOIN cargos cg ON cg.id_cargo = v.id_cargo
                WHERE ca.id_candidato = ? AND ca.etapa <> 'Contratado'");
            $cand->execute([$idCand]);
            $cand = $idCand ? $cand->fetch(PDO::FETCH_ASSOC) : false;

            if ($cand) {

                $idAtivo = (int)$pdo->query("SELECT id_status FROM statuss WHERE status_agora = 'Ativo'")->fetchColumn();

                $pdo->prepare("INSERT INTO vinculo_trabalista (id_empresa, id_status, cargo, data_inicio, salario) VALUES (?, ?, ?, CURDATE(), ?)")
                    ->execute([$cand['id_empresa'], $idAtivo, $cand['nome_cargo'], $cand['salario'] ?? 0]);
                $idVinculo = $pdo->lastInsertId();

                $pdo->prepare("UPDATE colaboradores SET id_trabalista = ?, id_empresa = ?, id_vinculo = ?, id_status = ? WHERE id_colaboradores = ?")
                    ->execute([$idVinculo, $cand['id_empresa'], $cand['id_vinculo'], $idAtivo, $idNovo]);
                gravarHistorico($pdo, $idVinculo, 'vinculo_trabalista', 'cadastro', '', $Name . ' - ' . $cand['nome_cargo'], $cand['id_empresa']);

                $pdo->prepare("UPDATE candidatos SET etapa = 'Contratado' WHERE id_candidato = ?")->execute([$idCand]);
                gravarHistorico($pdo, $idCand, 'candidatos', 'etapa', $cand['etapa'], 'Contratado', $cand['id_empresa']);

                $contratados = $pdo->prepare("SELECT COUNT(*) FROM candidatos WHERE id_vaga = ? AND etapa = 'Contratado'");
                $contratados->execute([$cand['id_vaga']]);

                if ((int)$contratados->fetchColumn() >= (int)$cand['posicoes']) {
                    $pdo->prepare("UPDATE vagas SET status = 'Encerrada' WHERE id_vaga = ?")->execute([$cand['id_vaga']]);
                    gravarHistorico($pdo, $cand['id_vaga'], 'vagas', 'status', 'Aberta', 'Encerrada', $cand['id_empresa']);
                }

                // abre o contrato já pronto
                header("Location: contrato.php?id=" . $idVinculo . "&novo=1");
                exit;
            }

            

        };


        // ===== NOVO: atualizar colaborador =====
        if($Acao === "update"){

            $Id = $_POST['id'];
            $Name = $_POST['name'];
            $CPF = $_POST['cpf'];
            $Email = $_POST['email'];
            $Endereco = $_POST['local'];
            $Tel = $_POST['tel'];

            // guarda os valores antigos para registrar só o que mudou
            $antigo = $pdo->prepare("SELECT nome_user, CPF, email, endereço, telefone FROM colaboradores WHERE id_colaboradores = ?");
            $antigo->execute([$Id]);
            $antigo = $antigo->fetch(PDO::FETCH_ASSOC);

            $stmtU = $pdo->prepare("UPDATE colaboradores
            SET nome_user = ?, CPF = ?, email = ?, endereço = ?, telefone = ?
            WHERE id_colaboradores = ?");
            $stmtU->execute([$Name, $CPF, $Email, $Endereco, $Tel, $Id]);

            $novo = ['nome_user' => $Name, 'CPF' => $CPF, 'email' => $Email, 'endereço' => $Endereco, 'telefone' => $Tel];

            foreach ($novo as $campo => $valor) {
                if ($antigo && (string)$antigo[$campo] !== (string)$valor) {
                    gravarHistorico($pdo, $Id, 'colaboradores', $campo, $antigo[$campo], $valor);
                }
            }

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
                    gravarHistorico($pdo, $Id, 'colaboradores', 'foto', '', 'nova foto');

                }

            }

        };


    };

    // botão Contratar da página de vagas: abre o cadastro já com os dados do candidato
    $contratar = null;

    if (isset($_GET['contratar'])) {
        $q = $pdo->prepare("SELECT ca.id_candidato, ca.nome, ca.email, ca.telefone, cg.nome_cargo, e.nome_empresas
            FROM candidatos ca JOIN vagas v ON v.id_vaga = ca.id_vaga
            JOIN cargos cg ON cg.id_cargo = v.id_cargo JOIN empresas e ON e.id_empresas = v.id_empresa
            WHERE ca.id_candidato = ? AND ca.etapa <> 'Contratado'");
        $q->execute([(int)$_GET['contratar']]);
        $contratar = $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Fundo aplicado antes dos estilos externos: evita tela branca no iframe. -->
    <style>html, body { background-color: #0f172a; }</style>

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
            <?php if ($contratar): ?>
                <p>Contratação para <?= htmlspecialchars($contratar['nome_cargo'] . ' - ' . $contratar['nome_empresas'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
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
            <input type="hidden" name="candidato" id="candidatoContratado" value="">

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
                    <button type="button" id="cancelEdit">Cancelar</button>
                </div>

            </div>

           
            <input type="hidden" name="acao" value="update">

        </form>

    </dialog>

</body>
    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>const CONTRATAR = <?= json_encode($contratar, JSON_UNESCAPED_UNICODE) ?>;</script>
    <script src="../assets/js/colaboradores.js?v=<?= filemtime(__DIR__ . '/../assets/js/colaboradores.js') ?>"></script>
</html>
