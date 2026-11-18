<?php

require_once "../../src/config/conexao.php";
session_start();
date_default_timezone_set('America/Sao_Paulo');

// só entra quem estiver logado (Administrador = 1 ou Gerente RH = 2)
if (!isset($_SESSION['User'])) {
    header("Location: ../index.php");
    exit();
}

/* ========================= FUNÇÕES AUXILIARES ========================= */

// escapa o texto antes de mostrar na tela (evita XSS)
function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// número com 2 casas, sempre com ponto
function dec($valor)
{
    return number_format((float)$valor, 2, '.', '');
}

// dinheiro no formato brasileiro
function moeda($valor)
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

// data do banco (Y-m-d) para o formato brasileiro
function dataBR($data)
{
    return $data ? date('d/m/Y', strtotime($data)) : '—';
}

// confere se o texto é uma data real no formato Y-m-d
function dataValida($texto)
{
    $d = DateTime::createFromFormat('Y-m-d', (string)$texto);
    return $d && $d->format('Y-m-d') === $texto;
}

// grava a alteração na tabela historico_alteracoes
// obs: valor_anterior e valor_novo são VARCHAR(30), então cortamos em 30 caracteres
function registrarHistorico($pdo, $idAcesso, $idEmpresa, $idRegistro, $tabela, $campo, $anterior, $novo)
{

    $sql = "INSERT INTO historico_alteracoes
                (id_acessos, id_empresa, id_registro, tabela_alterada, campo_alterado, valor_anterior, valor_novo, data_alteracao)
                VALUES (?,?,?,?,?,?,?,NOW())";

    $pdo->prepare($sql)->execute([
        $idAcesso,
        $idEmpresa,
        $idRegistro,
        $tabela,
        $campo,
        mb_substr((string)$anterior, 0, 30),
        mb_substr((string)$novo, 0, 30)
    ]);
}

// deixa o status do colaborador ligado a este vínculo igual ao do vínculo (Ativo/Inativo)
// e grava no histórico quando o status do colaborador realmente mudou
function sincronizarStatusColaborador($pdo, $idAcesso, $idEmpresa, $idTrabalista, $novoStatus, $listaStatus)
{

    $busca = $pdo->prepare("SELECT id_colaboradores, id_status FROM colaboradores WHERE id_trabalista = ?");
    $busca->execute([$idTrabalista]);

    foreach ($busca->fetchAll() as $c) {

        if ((int)$c['id_status'] === (int)$novoStatus) {
            continue;
        }

        $up = $pdo->prepare("UPDATE colaboradores SET id_status = ? WHERE id_colaboradores = ?");
        $up->execute([$novoStatus, $c['id_colaboradores']]);

        registrarHistorico(
            $pdo,
            $idAcesso,
            $idEmpresa,
            $c['id_colaboradores'],
            'colaboradores',
            'id_status',
            $listaStatus[$c['id_status']] ?? '',
            $listaStatus[$novoStatus] ?? ''
        );
    }
}

// lê e valida os campos do formulário de vínculo (retorna null se algo estiver errado)
function lerCamposVinculo($listaEmpresas, $listaTipos)
{

    $cargo     = trim($_POST['cargo'] ?? '');
    $salario   = (float) str_replace(',', '.', $_POST['salario'] ?? '0');
    $idEmpresa = (int) ($_POST['empresa'] ?? 0);
    $idTipo    = (int) ($_POST['tipo'] ?? 0);
    $inicio    = $_POST['data_inicio'] ?? '';
    $termino   = $_POST['data_termino'] ?? '';

    if ($cargo === '' || mb_strlen($cargo) > 150) {
        return null;
    }
    if ($salario < 0 || $salario > 99999999.99) {
        return null;
    }
    if (!isset($listaEmpresas[$idEmpresa])) {
        return null;
    }
    if (!dataValida($inicio)) {
        return null;
    }

    // o tipo (CLT, PJ...) é obrigatório quando a tabela tipo_vinculo tem registros
    if ($listaTipos && !isset($listaTipos[$idTipo])) {
        return null;
    }
    $idTipo = $listaTipos ? $idTipo : null;

    if ($termino === '') {
        $termino = null;
    } else if (!dataValida($termino) || $termino < $inicio) {
        return null;
    }

    return [
        'cargo'   => $cargo,
        'salario' => $salario,
        'empresa' => $idEmpresa,
        'tipo'    => $idTipo,
        'inicio'  => $inicio,
        'termino' => $termino
    ];
}

/* ============================ DADOS BASE ============================== */

$listaStatus   = [];   // id => nome (só Ativo e Inativo)
$listaEmpresas = [];   // id => nome
$listaTipos    = [];   // id => nome (CLT, PJ...)
$bancoPronto   = true;

try {

    $stmtS = $pdo->query("SELECT id_status, status_agora FROM statuss WHERE status_agora IN ('Ativo','Inativo') ORDER BY status_agora");
    foreach ($stmtS as $s) {
        $listaStatus[(int)$s['id_status']] = $s['status_agora'];
    }

    $stmtE = $pdo->query("SELECT id_empresas, nome_empresas FROM empresas ORDER BY nome_empresas");
    foreach ($stmtE as $emp) {
        $listaEmpresas[(int)$emp['id_empresas']] = $emp['nome_empresas'];
    }

    foreach ($pdo->query("SELECT id_vinculo, nome_vinculo FROM tipo_vinculo ORDER BY nome_vinculo") as $t) {
        $listaTipos[(int)$t['id_vinculo']] = $t['nome_vinculo'];
    }

    $pdo->query("SELECT cargo, salario, data_inicio, data_termino FROM vinculo_trabalista LIMIT 1");
    $pdo->query("SELECT id_trabalista FROM colaboradores LIMIT 1");
} catch (PDOException $ex) {
    $bancoPronto = false;
}

$idAtivo   = array_search('Ativo', $listaStatus, true);
$idInativo = array_search('Inativo', $listaStatus, true);
$hoje      = date('Y-m-d');

// regra do status: sem data de término (ou término no futuro) = Ativo; término até hoje = Encerrado
function statusPara($termino, $hoje, $idAtivo, $idInativo)
{
    return ($termino !== null && $termino <= $hoje) ? $idInativo : $idAtivo;
}

// id de quem está logado (para o histórico)
$idAcesso = null;
if ($bancoPronto) {
    $stmtA = $pdo->prepare("SELECT id_acesso FROM acessos WHERE nome_user = ? LIMIT 1");
    $stmtA->execute([$_SESSION['User']]);
    $idAcesso = $stmtA->fetchColumn() ?: null;
}

/* ============================ AÇÕES (POST) ============================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bancoPronto && $idAtivo !== false && $idInativo !== false) {

    $Acao = $_POST['acao'] ?? '';
    $msgCodigo = 'invalido';

    try {

        /* ---------- cadastrar ---------- */
        if ($Acao === 'cadastrar') {

            $d   = lerCamposVinculo($listaEmpresas, $listaTipos);
            $idC = (int) ($_POST['colaborador'] ?? 0);

            $stmtC = $pdo->prepare("SELECT c.nome_user, v.id_status AS status_atual
                    FROM colaboradores c
                    LEFT JOIN vinculo_trabalista v ON v.id_trabalista = c.id_trabalista
                    WHERE c.id_colaboradores = ?");
            $stmtC->execute([$idC]);
            $c = $stmtC->fetch();

            if ($d && $c) {

                // cada colaborador só pode ter um vínculo ativo por vez
                if ($c['status_atual'] !== null && (int)$c['status_atual'] === (int)$idAtivo) {

                    $msgCodigo = 'jatem';
                } else {

                    $pdo->beginTransaction();

                    $add = $pdo->prepare("INSERT INTO vinculo_trabalista
                            (id_empresa, id_status, cargo, data_inicio, data_termino, salario)
                            VALUES (?,?,?,?,?,?)");
                    $add->execute([$d['empresa'], statusPara($d['termino'], $hoje, $idAtivo, $idInativo), $d['cargo'], $d['inicio'], $d['termino'], dec($d['salario'])]);
                    $idNovo = (int) $pdo->lastInsertId();

                    // liga o colaborador ao novo vínculo e à empresa dele
                    $liga = $pdo->prepare("UPDATE colaboradores SET id_trabalista = ?, id_empresa = ?, id_vinculo = ? WHERE id_colaboradores = ?");
                    $liga->execute([$idNovo, $d['empresa'], $d['tipo'], $idC]);

                    registrarHistorico($pdo, $idAcesso, $d['empresa'], $idNovo, 'vinculo_trabalista', 'cadastro', '', $c['nome_user'] . ' - ' . $d['cargo']);

                    // colaborador passa a ter vínculo: o status dele acompanha o do vínculo (Ativo)
                    sincronizarStatusColaborador($pdo, $idAcesso, $d['empresa'], $idNovo, statusPara($d['termino'], $hoje, $idAtivo, $idInativo), $listaStatus);

                    $pdo->commit();
                    $msgCodigo = 'criado';
                }
            }
        }

        /* ---------- editar ---------- */
        if ($Acao === 'editar') {

            $id = (int) ($_POST['id'] ?? 0);
            $d  = lerCamposVinculo($listaEmpresas, $listaTipos);

            $busca = $pdo->prepare("SELECT * FROM vinculo_trabalista WHERE id_trabalista = ?");
            $busca->execute([$id]);
            $antigo = $busca->fetch();

            if ($d && $antigo) {

                $novoStatus = statusPara($d['termino'], $hoje, $idAtivo, $idInativo);

                $stmtT = $pdo->prepare("SELECT id_vinculo FROM colaboradores WHERE id_trabalista = ? LIMIT 1");
                $stmtT->execute([$id]);
                $tipoAntigo = $stmtT->fetchColumn();

                $pdo->beginTransaction();

                $up = $pdo->prepare("UPDATE vinculo_trabalista
                        SET id_empresa = ?, id_status = ?, cargo = ?, data_inicio = ?, data_termino = ?, salario = ?
                        WHERE id_trabalista = ?");
                $up->execute([$d['empresa'], $novoStatus, $d['cargo'], $d['inicio'], $d['termino'], dec($d['salario']), $id]);

                // a empresa e o tipo (CLT, PJ...) ficam no colaborador ligado a este vínculo
                $liga = $pdo->prepare("UPDATE colaboradores SET id_empresa = ?, id_vinculo = ? WHERE id_trabalista = ?");
                $liga->execute([$d['empresa'], $d['tipo'], $id]);

                // campo => [valor antigo, valor novo]
                $comparar = [
                    'cargo'        => [$antigo['cargo'], $d['cargo']],
                    'salario'      => [dec($antigo['salario']), dec($d['salario'])],
                    'data_inicio'  => [$antigo['data_inicio'], $d['inicio']],
                    'data_termino' => [$antigo['data_termino'] ?? '', $d['termino'] ?? ''],
                    'id_status'    => [$listaStatus[$antigo['id_status']] ?? '', $listaStatus[$novoStatus] ?? ''],
                    'id_empresa'   => [$listaEmpresas[$antigo['id_empresa']] ?? '', $listaEmpresas[$d['empresa']] ?? ''],
                    'tipo_vinculo' => [$listaTipos[$tipoAntigo] ?? '', $listaTipos[$d['tipo']] ?? '']
                ];

                // só grava no histórico o que realmente mudou
                foreach ($comparar as $campo => $par) {
                    if ((string)$par[0] !== (string)$par[1]) {
                        registrarHistorico($pdo, $idAcesso, $d['empresa'], $id, 'vinculo_trabalista', $campo, $par[0], $par[1]);
                    }
                }

                sincronizarStatusColaborador($pdo, $idAcesso, $d['empresa'], $id, $novoStatus, $listaStatus);

                $pdo->commit();
                $msgCodigo = 'editado';
            }
        }

        /* ---------- encerrar ---------- */
        if ($Acao === 'encerrar') {

            $id      = (int) ($_POST['id'] ?? 0);
            $termino = $_POST['data_termino'] ?? '';

            $busca = $pdo->prepare("SELECT * FROM vinculo_trabalista WHERE id_trabalista = ?");
            $busca->execute([$id]);
            $v = $busca->fetch();

            if ($v && dataValida($termino) && $termino >= $v['data_inicio']) {

                $novoStatus = statusPara($termino, $hoje, $idAtivo, $idInativo);

                $up = $pdo->prepare("UPDATE vinculo_trabalista SET data_termino = ?, id_status = ? WHERE id_trabalista = ?");
                $up->execute([$termino, $novoStatus, $id]);

                registrarHistorico($pdo, $idAcesso, $v['id_empresa'], $id, 'vinculo_trabalista', 'data_termino', $v['data_termino'] ?? '', $termino);

                if ((int)$v['id_status'] !== (int)$novoStatus) {
                    registrarHistorico($pdo, $idAcesso, $v['id_empresa'], $id, 'vinculo_trabalista', 'id_status', $listaStatus[$v['id_status']] ?? '', $listaStatus[$novoStatus] ?? '');
                }

                sincronizarStatusColaborador($pdo, $idAcesso, $v['id_empresa'], $id, $novoStatus, $listaStatus);

                $msgCodigo = 'encerrado';
            }
        }

        /* ---------- apagar ---------- */
        if ($Acao === 'apagar') {

            $id = (int) ($_POST['id'] ?? 0);

            $busca = $pdo->prepare("SELECT cargo, id_empresa FROM vinculo_trabalista WHERE id_trabalista = ?");
            $busca->execute([$id]);
            $v = $busca->fetch();

            if ($v) {

                $pdo->beginTransaction();

                // sem vínculo, o colaborador deixa de ficar ativo
                sincronizarStatusColaborador($pdo, $idAcesso, $v['id_empresa'], $id, $idInativo, $listaStatus);

                // solta o colaborador antes de apagar (a chave estrangeira não deixa apagar com ele ligado)
                $solta = $pdo->prepare("UPDATE colaboradores SET id_trabalista = NULL WHERE id_trabalista = ?");
                $solta->execute([$id]);

                $del = $pdo->prepare("DELETE FROM vinculo_trabalista WHERE id_trabalista = ?");
                $del->execute([$id]);

                registrarHistorico($pdo, $idAcesso, $v['id_empresa'], $id, 'vinculo_trabalista', 'exclusao', $v['cargo'], '');

                $pdo->commit();
                $msgCodigo = 'apagado';
            }
        }
    } catch (PDOException $ex) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $codigo = $ex->errorInfo[1] ?? 0;

        if ($codigo == 1451) {
            $msgCodigo = 'emuso';
        }       // ainda é usado em outra tabela
        else {
            $msgCodigo = 'invalido';
        }
    }

    // redireciona para não reenviar o formulário ao atualizar a página
    header("Location: vinculos.php?msg=" . $msgCodigo);
    exit();
}

/* ======================= MENSAGEM DE RETORNO ========================== */

$mensagens = [
    'criado'    => ['ok',   'Vínculo cadastrado com sucesso.'],
    'editado'   => ['ok',   'Vínculo atualizado com sucesso.'],
    'encerrado' => ['ok',   'Vínculo encerrado.'],
    'apagado'   => ['ok',   'Vínculo apagado.'],
    'jatem'     => ['erro', 'Este colaborador já possui um vínculo ativo. Encerre o atual antes de criar outro.'],
    'emuso'     => ['erro', 'Não foi possível apagar: o vínculo ainda está em uso. Encerre-o em vez de apagar.'],
    'invalido'  => ['erro', 'Dados inválidos. Confira os campos e tente de novo.']
];

$aviso = $mensagens[$_GET['msg'] ?? ''] ?? null;

/* ========================= DADOS DA LISTAGEM ========================== */

$vinculos      = [];
$colaboradores = [];

if ($bancoPronto) {

    $sqlLista = "SELECT v.*, s.status_agora, e.nome_empresas,
                        (SELECT c.id_colaboradores FROM colaboradores c WHERE c.id_trabalista = v.id_trabalista LIMIT 1) AS id_colab,
                        (SELECT c.nome_user FROM colaboradores c WHERE c.id_trabalista = v.id_trabalista LIMIT 1) AS nome_colab,
                        (SELECT c.id_vinculo FROM colaboradores c WHERE c.id_trabalista = v.id_trabalista LIMIT 1) AS id_tipo
                     FROM vinculo_trabalista v
                     LEFT JOIN statuss s ON s.id_status = v.id_status
                     LEFT JOIN empresas e ON e.id_empresas = v.id_empresa
                     ORDER BY nome_colab, v.cargo";
    $vinculos = $pdo->query($sqlLista)->fetchAll();

    // colaboradores e o status do vínculo atual de cada um (para saber quem já tem vínculo ativo)
    $sqlColab = "SELECT c.id_colaboradores, c.nome_user, c.id_empresa, v.id_status AS status_vinculo
                    FROM colaboradores c
                    LEFT JOIN vinculo_trabalista v ON v.id_trabalista = c.id_trabalista
                    ORDER BY c.nome_user";
    foreach ($pdo->query($sqlColab) as $c) {
        $colaboradores[] = [
            'id'      => (int)$c['id_colaboradores'],
            'nome'    => $c['nome_user'],
            'empresa' => $c['id_empresa'] === null ? null : (int)$c['id_empresa'],
            'ocupado' => $c['status_vinculo'] !== null && (int)$c['status_vinculo'] === (int)$idAtivo
        ];
    }
}

// benefícios de cada colaborador (tabela colaborador_beneficio): id_colaborador => [benefícios]
// fica fora do bancoPronto: se a tabela não existir, a tela de vínculos continua funcionando
$beneficiosPorColab = [];

if ($bancoPronto) {

    try {

        $sqlBen = "SELECT cb.id_colaboradores, b.nome_beneficios, b.tipo, b.valor, b.desconto, s.status_agora AS status_ligacao
                    FROM colaborador_beneficio cb
                    JOIN beneficios b ON b.id_beneficios = cb.id_beneficios
                    LEFT JOIN statuss s ON s.id_status = cb.id_status
                    ORDER BY b.nome_beneficios";

        foreach ($pdo->query($sqlBen) as $bn) {
            $beneficiosPorColab[(int)$bn['id_colaboradores']][] = $bn;
        }
    } catch (PDOException $ex) {
        $beneficiosPorColab = [];
    }
}

$totalAtivos = 0;

foreach ($vinculos as $v) {
    if (($v['status_agora'] ?? '') === 'Ativo') {
        $totalAtivos++;
    }
}

$totalGeral      = count($vinculos);
$totalEncerrados = $totalGeral - $totalAtivos;

// dados que o JavaScript usa para abrir os modais
$dadosJs = [
    'hoje'          => $hoje,
    'vinculos'      => [],
    'colaboradores' => $colaboradores
];

foreach ($vinculos as $v) {
    $dadosJs['vinculos'][(int)$v['id_trabalista']] = [
        'colaborador'  => $v['id_colab'] === null ? null : (int)$v['id_colab'],
        'nomeColab'    => $v['nome_colab'] ?? 'Sem colaborador',
        'empresa'      => $v['id_empresa'] === null ? null : (int)$v['id_empresa'],
        'tipo'         => $v['id_tipo'] === null ? null : (int)$v['id_tipo'],
        'cargo'        => $v['cargo'],
        'salario'      => dec($v['salario']),
        'inicio'       => $v['data_inicio'],
        'termino'      => $v['data_termino'] ?? ''
    ];
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
    <link rel="stylesheet" href="../assets/css/vinculos.css?v=<?= filemtime(__DIR__ . '/../assets/css/vinculos.css') ?>">
    <title>Vínculos</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="top1">

                <div class="img">
                    <!-- Lucide: briefcase -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                        <rect width="20" height="14" x="2" y="6" rx="2" />
                    </svg>
                </div>

                <div class="text">
                    <h3>Vínculos</h3>
                    <p>Gerencie os contratos de trabalho</p>
                </div>

            </div>

            <div class="top2">
                <button type="button" id="novo" <?= $bancoPronto ? '' : 'disabled' ?>>Novo vínculo</button>
            </div>

        </header>

        <?php if (!$bancoPronto): ?>
            <p class="aviso erro">As tabelas de vínculos ainda não estão prontas no banco de dados. Confira o BD.sql.</p>
        <?php elseif ($aviso): ?>
            <p class="aviso <?= e($aviso[0]) ?>" role="status"><?= e($aviso[1]) ?></p>
        <?php endif; ?>

        <div class="resumo">

            <div class="card">
                <span>Vínculos ativos</span>
                <strong><?= $totalAtivos ?></strong>
            </div>

            <div class="card">
                <span>Encerrados</span>
                <strong><?= $totalEncerrados ?></strong>
            </div>

            <div class="card">
                <span>Total</span>
                <strong><?= $totalGeral ?></strong>
            </div>

        </div>

        <section>

            <input placeholder="Pesquise por colaborador, empresa ou cargo" type="search" id="ipt" aria-label="Pesquisar vínculos">

            <select id="filtroStatus" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Encerrados</option>
            </select>

        </section>

        <main>

            <div class="lista">

                <?php foreach ($vinculos as $v):

                    $nomeStatus  = $v['status_agora'] ?? 'Sem status';
                    $ativo       = $nomeStatus === 'Ativo';
                    $nomeColab   = $v['nome_colab'] ?? 'Sem colaborador';
                    $nomeEmpresa = $v['nome_empresas'] ?? 'Sem empresa';
                    $nomeTipo    = $listaTipos[$v['id_tipo']] ?? '—';
                    $listaBen    = $beneficiosPorColab[(int)($v['id_colab'] ?? 0)] ?? [];
                    $nomesBen    = implode(' ', array_column($listaBen, 'nome_beneficios'));
                    $textoBusca  = mb_strtolower($nomeColab . ' ' . $nomeEmpresa . ' ' . $v['cargo'] . ' ' . $nomeTipo . ' ' . $nomesBen);

                ?>

                    <details class="vinculo" data-status="<?= e(mb_strtolower($nomeStatus)) ?>" data-busca="<?= e($textoBusca) ?>">

                        <!-- parte sempre visível: clicar abre e fecha o resto -->
                        <summary>

                            <span class="foto" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>

                            <span class="quem">
                                <strong><?= e($nomeColab) ?></strong>
                                <small><?= e($v['cargo']) ?></small>
                            </span>

                            <span class="badge <?= e(mb_strtolower($nomeStatus)) ?>"><?= $ativo ? 'Ativo' : 'Encerrado' ?></span>

                            <!-- Lucide: chevron-down -->
                            <svg class="seta" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>

                        </summary>

                        <!-- parte que aparece ao abrir -->
                        <div class="detalhes">

                            <dl class="info">
                                <div>
                                    <dt>Empresa</dt>
                                    <dd><?= e($nomeEmpresa) ?></dd>
                                </div>
                                <div>
                                    <dt>Tipo de vínculo</dt>
                                    <dd><?= e($nomeTipo) ?></dd>
                                </div>
                                <div>
                                    <dt>Salário</dt>
                                    <dd><?= moeda($v['salario']) ?></dd>
                                </div>
                                <div>
                                    <dt>Data de início</dt>
                                    <dd><?= dataBR($v['data_inicio']) ?></dd>
                                </div>
                                <div>
                                    <dt>Data de término</dt>
                                    <dd><?= dataBR($v['data_termino']) ?></dd>
                                </div>
                                <div>
                                    <dt>Status</dt>
                                    <dd><?= $ativo ? 'Ativo' : 'Encerrado' ?></dd>
                                </div>
                            </dl>

                            <!-- benefícios que o colaborador deste vínculo recebe (só visualização) -->
                            <div class="beneficios-vinculo">

                                <h4>Benefícios <span>(<?= count($listaBen) ?>)</span></h4>

                                <?php if ($listaBen): ?>

                                    <ul>
                                        <?php foreach ($listaBen as $bn):
                                            $ligado = ($bn['status_ligacao'] ?? 'Ativo') === 'Ativo';
                                        ?>
                                            <li class="<?= $ligado ? '' : 'inativo' ?>">
                                                <span class="ben-nome">
                                                    <strong><?= e($bn['nome_beneficios']) ?></strong>
                                                    <small><?= e($bn['tipo']) ?><?= $ligado ? '' : ' · Inativo' ?></small>
                                                </span>
                                                <span class="ben-valor">
                                                    <?= moeda($bn['valor']) ?>
                                                    <?php if ((float)$bn['desconto'] > 0): ?>
                                                        <small>desconto <?= moeda($bn['desconto']) ?></small>
                                                    <?php endif; ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                <?php else: ?>

                                    <p class="sem-beneficio"><?= $v['id_colab'] === null ? 'Este vínculo não tem colaborador ligado.' : 'Nenhum benefício vinculado a este colaborador.' ?></p>

                                <?php endif; ?>

                            </div>

                            <div class="acoes-card">

                                <?php if ($ativo): ?>
                                    <button type="button" class="acao-txt btn-encerrar" data-id="<?= (int)$v['id_trabalista'] ?>">
                                        <!-- Lucide: calendar-x -->
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M8 2v4" />
                                            <path d="M16 2v4" />
                                            <rect width="18" height="18" x="3" y="4" rx="2" />
                                            <path d="M3 10h18" />
                                            <path d="m14 14-4 4" />
                                            <path d="m10 14 4 4" />
                                        </svg>
                                        Encerrar
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="acao-txt apagar btn-excluir" data-id="<?= (int)$v['id_trabalista'] ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 6h18" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                    Apagar
                                </button>

                                <button type="button" class="acao-txt editar btn-editar" data-id="<?= (int)$v['id_trabalista'] ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                    Editar
                                </button>

                            </div>

                        </div>

                    </details>

                <?php endforeach; ?>

                <p class="vazio" id="vazio" <?= count($vinculos) > 0 ? 'hidden' : '' ?>>
                    <?= count($vinculos) > 0 ? 'Nenhum vínculo encontrado para esse filtro.' : 'Nenhum vínculo cadastrado ainda. Clique em "Novo vínculo".' ?>
                </p>

            </div>

        </main>

    </div>


    <!-- ====================== MODAL: CADASTRAR / EDITAR ====================== -->
    <dialog class="modal" id="modalForm">

        <div class="topModal">
            <h3 id="tituloForm">Cadastrar vínculo</h3>
        </div>

        <div class="mainModal">

            <form action="vinculos.php" method="post" id="formVinculo">

                <div class="ipt">

                    <div class="separation2">
                        <label for="fColab">Colaborador</label>
                        <select name="colaborador" id="fColab" required></select>
                    </div>

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="fEmpresa">Empresa</label>
                        <select required name="empresa" id="fEmpresa">
                            <?php foreach ($listaEmpresas as $idEmp => $nomeEmp): ?>
                                <option value="<?= (int)$idEmp ?>"><?= e($nomeEmp) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="separation">
                        <label for="fCargo">Cargo</label>
                        <input required maxlength="150" type="text" name="cargo" id="fCargo">
                    </div>

                </div>

                <?php if ($listaTipos): ?>
                    <div class="ipt">

                        <div class="separation">
                            <label for="fTipo">Tipo de vínculo</label>
                            <select required name="tipo" id="fTipo">
                                <?php foreach ($listaTipos as $idTipo => $nomeTipo): ?>
                                    <option value="<?= (int)$idTipo ?>"><?= e($nomeTipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                <?php endif; ?>

                <div class="ipt">

                    <div class="separation">
                        <label for="fSalario">Salário (R$)</label>
                        <input required type="number" min="0" step="0.01" name="salario" id="fSalario" value="0.00">
                    </div>

                    <div class="separation">
                        <label for="fInicio">Data de início</label>
                        <input required type="date" name="data_inicio" id="fInicio">
                    </div>

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="fTermino">Data de término <small>(deixe vazio se está ativo)</small></label>
                        <input type="date" name="data_termino" id="fTermino">
                    </div>

                </div>

                <p class="erro" id="erroForm" role="alert"></p>

                <div class="btns">
                    <button type="submit" id="btnSalvar">Confirmar</button>
                    <button type="button" data-fechar>Cancelar</button>
                </div>

                <!-- campos para o PHP saber o que fazer -->
                <input type="hidden" name="acao" id="acaoForm" value="cadastrar">
                <input type="hidden" name="id" id="idForm" value="">

            </form>

        </div>

    </dialog>


    <!-- ========================= MODAL: ENCERRAR ============================= -->
    <dialog class="modal modal-pequeno" id="modalEncerrar">

        <form action="vinculos.php" method="post" id="formEncerrar">

            <div class="topModal">
                <h3>Encerrar vínculo</h3>
            </div>

            <p class="texto-modal">Encerrar o vínculo de <strong id="nomeEncerrar"></strong>? O registro continua no histórico.</p>

            <div class="ipt">
                <div class="separation2">
                    <label for="eData">Data de término</label>
                    <input required type="date" name="data_termino" id="eData">
                </div>
            </div>

            <p class="erro" id="erroEncerrar" role="alert"></p>

            <input type="hidden" name="id" id="idEncerrar">
            <input type="hidden" name="acao" value="encerrar">

            <div class="btns">
                <button type="submit">Encerrar</button>
                <button type="button" data-fechar>Cancelar</button>
            </div>

        </form>

    </dialog>


    <!-- ========================= MODAL: APAGAR ============================= -->
    <dialog class="modal modal-pequeno" id="modalApagar">

        <form action="vinculos.php" method="post">

            <div class="topModal">
                <h3>Apagar vínculo</h3>
            </div>

            <p class="texto-modal">Tem certeza que deseja apagar o vínculo de <strong id="nomeExcluir"></strong>? Para manter o histórico, prefira encerrar.</p>

            <input type="hidden" name="id" id="idExcluir">
            <input type="hidden" name="acao" value="apagar">

            <div class="btns">
                <button type="button" data-fechar>Cancelar</button>
                <button type="submit" class="excluir">Apagar</button>
            </div>

        </form>

    </dialog>


    <!-- ================= MENU DO USUÁRIO LOGADO (perfil / sair) ================= -->
    <dialog class="user" id="dialogPerfil"></dialog>


    <script>
        const DADOS = <?= json_encode($dadosJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../assets/js/vinculos.js"></script>

</body>

</html>