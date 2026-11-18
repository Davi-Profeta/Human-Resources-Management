<?php

require_once "../../src/config/conexao.php";
session_start();

if (empty($_SESSION['User']) || $_SESSION['Nivel'] != 1) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $Acao = $_POST['acao'];

    if ($Acao === 'new') {

        $nameEnterprice = $_POST['name'];
        $CNPJ = $_POST['cnpj'];
        $Email = $_POST['email'];
        $Tel = $_POST['tel'];
        $Endereco = $_POST['local'];
        $Razao = $_POST['RS'];
        $Stats = $_POST['status'];

        $Enterprice = $pdo->prepare("INSERT INTO empresas(nome_empresas, CNPJ, email, telefone, endereco, razao_social, id_stats)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $Enterprice->execute([$nameEnterprice, $CNPJ, $Email, $Tel, $Endereco, $Razao, $Stats]);
    } else if ($Acao === 'apagar') {

        $Id = $_POST['id'];

        $deleteEnterprice = $pdo->prepare("DELETE FROM empresas
            WHERE id_empresas = ?");
        $deleteEnterprice->execute([$Id]);
    } else if ($Acao === "edit") {

        $nameEnterprice = $_POST['name'];
        $CNPJ = $_POST['cnpj'];
        $Email = $_POST['email'];
        $Tel = $_POST['tel'];
        $Endereco = $_POST['local'];
        $Razao = $_POST['RS'];
        $Stats = $_POST['status'];

        $Id = $_POST['id'];

        $Enterprice = $pdo->prepare("UPDATE empresas
            SET nome_empresas = ?, CNPJ = ?, email = ?, telefone = ?, endereco = ?, razao_social = ?, id_stats = ?
            WHERE id_empresas = ?");
        $Enterprice->execute([$nameEnterprice, $CNPJ, $Email, $Tel, $Endereco, $Razao, $Stats, $Id]);
    };
};

// escapa o texto antes de mostrar na tela (evita XSS)
function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// 12345678000190 -> 12.345.678/0001-90
function formatarCnpj($cnpj)
{
    $d = preg_replace('/\D/', '', (string)$cnpj);

    return strlen($d) === 14
        ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d)
        : $cnpj;
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
    <link rel="stylesheet" href="../assets/css/empresas.css?v=<?= filemtime(__DIR__ . '/../assets/css/empresas.css') ?>">
    <title>Empresas</title>

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

            <input placeholder="Pesquise por nome, CNPJ ou e-mail" type="search" id="ipt" aria-label="Pesquisar empresas">

            <select id="filtroStatus" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="Ativo">Ativos</option>
                <option value="Inativo">Inativos</option>
            </select>

        </section>

        <main>

            <div class="lista-empresas" id="lista">

                <?php

                // a subconsulta conta quantos colaboradores pertencem a cada empresa
                $listaEmpresas = $pdo->prepare("SELECT id_empresas, nome_empresas, CNPJ, email, telefone, endereco, razao_social, id_stats, status_agora,
                    (SELECT COUNT(*) FROM colaboradores WHERE colaboradores.id_empresa = empresas.id_empresas) AS qtd_colaboradores
                    FROM empresas
                    INNER JOIN statuss ON id_status = id_stats
                    ORDER BY nome_empresas");
                $listaEmpresas->execute();

                $empresas = $listaEmpresas->fetchAll(PDO::FETCH_ASSOC);

                foreach ($empresas as $i):

                    $qtd   = (int)$i['qtd_colaboradores'];
                    $ativa = $i['status_agora'] === 'Ativo';

                ?>

                    <article class="card-empresa"
                        data-id="<?= (int)$i['id_empresas'] ?>"
                        data-nome="<?= e($i['nome_empresas']) ?>"
                        data-cnpj="<?= e($i['CNPJ']) ?>"
                        data-cnpj-fmt="<?= e(formatarCnpj($i['CNPJ'])) ?>"
                        data-email="<?= e($i['email']) ?>"
                        data-tel="<?= e($i['telefone']) ?>"
                        data-local="<?= e($i['endereco']) ?>"
                        data-rs="<?= e($i['razao_social']) ?>"
                        data-status="<?= (int)$i['id_stats'] ?>"
                        data-status-nome="<?= e($i['status_agora']) ?>"
                        data-qtd="<?= $qtd ?>"
                        data-busca="<?= e(mb_strtolower($i['nome_empresas'] . ' ' . $i['CNPJ'] . ' ' . $i['email'] . ' ' . $i['razao_social'])) ?>">

                        <div class="topo-card">

                            <div class="icone-card" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 12h4" />
                                    <path d="M10 8h4" />
                                    <path d="M14 21v-3a2 2 0 0 0-4 0v3" />
                                    <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2" />
                                    <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16" />
                                </svg>
                            </div>

                            <span class="situacao <?= $ativa ? 'ativa' : 'inativa' ?>"><?= e($i['status_agora']) ?></span>

                        </div>

                        <h4><?= e($i['nome_empresas']) ?></h4>

                        <p class="cnpj"><?= e(formatarCnpj($i['CNPJ'])) ?></p>
                        <p class="email"><?= e($i['email']) ?></p>

                        <p class="qtd-colab">
                            <!-- Lucide: users -->
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                            <?= $qtd ?> <?= $qtd === 1 ? 'colaborador' : 'colaboradores' ?>
                        </p>

                        <div class="acoes-card">

                            <button type="button" class="acao-txt btn-detalhes">
                                <!-- Lucide: eye -->
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                Detalhes
                            </button>

                            <button type="button" class="acao-txt editar btn-editar" data-id="<?= (int)$i['id_empresas'] ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                                Editar
                            </button>

                        </div>

                    </article>

                <?php endforeach; ?>

                <p class="vazio" id="vazio" <?= count($empresas) > 0 ? 'hidden' : '' ?>>
                    <?= count($empresas) > 0 ? 'Nenhuma empresa encontrada para esse filtro.' : 'Nenhuma empresa cadastrada ainda. Clique em "Nova empresa".' ?>
                </p>

            </div>

        </main>

    </div>

    <dialog class="newEnterprice">

        <div class="topModal">
            <h3>Cadastrar Empresa</h3>
        </div>

        <div class="mainModal">

            <form action="empresas.php" method="post">

                <div class="ipt">

                    <div class="separation">
                        <label for="">Nome Empresa</label>
                        <input required type="text" name="name" id="">
                    </div>

                    <div class="separation">
                        <label for="">CNPJ</label>
                        <input required maxlength="14" type="num" name="cnpj" id="">
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
                        <label for="">Status</label>
                        <select name="status" id="">
                            <option value="1">Ativo</option>
                            <option value="2">Inativa</option>
                        </select>
                    </div>

                </div>

                <div class="ipt">

                    <p class='erro' id="erro"> </p>

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


    <dialog class="edit">

        <div class="topModal">
            <h3>Editar Dados</h3>
        </div>

        <div class="mainModal">

            <form action="empresas.php" method="post">

                <div class="ipt">

                    <div class="separation">
                        <label for="">Nome Empresa</label>
                        <input value="" required type="text" name="name" id="">
                    </div>

                    <div class="separation">
                        <label for="">CNPJ</label>
                        <input required maxlength="14" type="num" name="cnpj" id="">
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
                        <label for="">Status</label>
                        <select name="status" id="">
                            <option value="1">Ativo</option>
                            <option value="2">Inativa</option>
                        </select>
                    </div>

                </div>

                <div class="ipt">

                    <p class='erro' id="erro"> </p>
                    <input type="hidden" name="id" id="idEditar">

                    <div class="btns">
                        <button type="submit">Confirmar</button>
                        <button type="button" id="cancelEdit">Cancelar</button>
                    </div>

                </div>

                <!-- input para ação no PHP -->
                <input type="hidden" name="acao" value="edit">

            </form>

        </div>

    </dialog>


    <dialog class="detalhes">

        <div class="topModal">
            <h3 id="detNome">Detalhes da empresa</h3>
        </div>

        <dl class="info-det">
            <div>
                <dt>CNPJ</dt>
                <dd id="detCnpj"></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd id="detStatus"></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd id="detEmail"></dd>
            </div>
            <div>
                <dt>Telefone</dt>
                <dd id="detTel"></dd>
            </div>
            <div class="larga">
                <dt>Endereço</dt>
                <dd id="detLocal"></dd>
            </div>
            <div class="larga">
                <dt>Razão Social</dt>
                <dd id="detRS"></dd>
            </div>
            <div>
                <dt>Colaboradores</dt>
                <dd id="detQtd"></dd>
            </div>
        </dl>

        <div class="btns">
            <button type="button" class="perigo" id="detApagar">Apagar empresa</button>
            <button type="button" id="detFechar">Fechar</button>
        </div>

    </dialog>


    <dialog class="delete">

        <form action="empresas.php" method="post">
            <h3>Apagar Empresa</h3>
            <p>Tem certeza que deseja apagar esta Empresa?</p>

            <input type="hidden" name="acao" value="apagar">
            <input type="hidden" name="id" id="idApagar"> <!-- receber o id de quem vai apagar -->

            <div class="dialog-botoes">
                <button type="button" id="cancelarExcluir">Cancelar</button>
                <button type="submit" class="excluir">Apagar</button>
            </div>
        </form>

    </dialog>

    <dialog class="user">


    </dialog>

</body>
<script src="../assets/js/empresas.js"></script>

</html>
