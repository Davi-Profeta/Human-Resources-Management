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

// número com 2 casas, sempre com ponto (usado para comparar valores)
function dec($valor)
{
    return number_format((float)$valor, 2, '.', '');
}

// dinheiro no formato brasileiro
function moeda($valor)
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

// grava a alteração na tabela historico_alteracoes (RNF: histórico de mudanças)
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

// lê e valida os campos do formulário de benefício (retorna null se algo estiver errado)
function lerCamposBeneficio($tipos, $listaStatus, $listaEmpresas)
{

    $nome      = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo      = $_POST['tipo'] ?? 'Outro';
    $valor     = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
    $desconto  = (float) str_replace(',', '.', $_POST['desconto'] ?? '0');
    $idStatus  = (int) ($_POST['status'] ?? 0);
    $idEmpresa = (($_POST['empresa'] ?? '') === '') ? null : (int) $_POST['empresa'];

    if ($nome === '' || mb_strlen($nome) > 100 || mb_strlen($descricao) > 250) {
        return null;
    }
    if ($valor < 0 || $desconto < 0 || $desconto > $valor) {
        return null;
    }
    if (!isset($listaStatus[$idStatus])) {
        return null;
    }
    if ($idEmpresa !== null && !isset($listaEmpresas[$idEmpresa])) {
        return null;
    }
    if (!in_array($tipo, $tipos, true)) {
        $tipo = 'Outro';
    }

    return [
        'nome'      => $nome,
        'descricao' => $descricao,
        'tipo'      => $tipo,
        'valor'     => $valor,
        'desconto'  => $desconto,
        'status'    => $idStatus,
        'empresa'   => $idEmpresa
    ];
}

/* ============================ DADOS BASE ============================== */

$tipos = ['Alimentação', 'Transporte', 'Saúde', 'Odontológico', 'Seguro', 'Educação', 'Bem-estar', 'Outro'];

$listaStatus   = [];   // id => nome (só Ativo e Inativo)
$listaEmpresas = [];   // id => nome
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

    // se o beneficios.sql ainda não foi executado, essa consulta dá erro
    $pdo->query("SELECT tipo, valor, desconto, id_empresa FROM beneficios LIMIT 1");
    $pdo->query("SELECT 1 FROM colaborador_beneficio LIMIT 1");
} catch (PDOException $ex) {
    $bancoPronto = false;
}

// id de quem está logado (para o histórico)
$idAcesso = null;
if ($bancoPronto) {
    $stmtA = $pdo->prepare("SELECT id_acesso FROM acessos WHERE nome_user = ? LIMIT 1");
    $stmtA->execute([$_SESSION['User']]);
    $idAcesso = $stmtA->fetchColumn() ?: null;
}

/* ============================ AÇÕES (POST) ============================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bancoPronto) {

    $Acao = $_POST['acao'] ?? '';
    $msgCodigo = 'invalido';

    try {

        /* ---------- cadastrar ---------- */
        if ($Acao === 'cadastrar') {

            $d = lerCamposBeneficio($tipos, $listaStatus, $listaEmpresas);

            if ($d) {

                $add = $pdo->prepare("INSERT INTO beneficios
                        (id_status, id_empresa, nome_beneficios, descricao, tipo, valor, desconto)
                        VALUES (?,?,?,?,?,?,?)");
                $add->execute([$d['status'], $d['empresa'], $d['nome'], $d['descricao'], $d['tipo'], dec($d['valor']), dec($d['desconto'])]);

                registrarHistorico($pdo, $idAcesso, $d['empresa'], $pdo->lastInsertId(), 'beneficios', 'cadastro', '', $d['nome']);
                $msgCodigo = 'criado';
            }
        }

        /* ---------- editar ---------- */
        if ($Acao === 'editar') {

            $id = (int) ($_POST['id'] ?? 0);
            $d  = lerCamposBeneficio($tipos, $listaStatus, $listaEmpresas);

            $busca = $pdo->prepare("SELECT * FROM beneficios WHERE id_beneficios = ?");
            $busca->execute([$id]);
            $antigo = $busca->fetch();

            if ($d && $antigo) {

                $up = $pdo->prepare("UPDATE beneficios
                        SET id_status = ?, id_empresa = ?, nome_beneficios = ?, descricao = ?, tipo = ?, valor = ?, desconto = ?
                        WHERE id_beneficios = ?");
                $up->execute([$d['status'], $d['empresa'], $d['nome'], $d['descricao'], $d['tipo'], dec($d['valor']), dec($d['desconto']), $id]);

                // campo => [valor antigo, valor novo]
                $comparar = [
                    'nome_beneficios' => [$antigo['nome_beneficios'], $d['nome']],
                    'descricao'       => [$antigo['descricao'], $d['descricao']],
                    'tipo'            => [$antigo['tipo'], $d['tipo']],
                    'valor'           => [dec($antigo['valor']), dec($d['valor'])],
                    'desconto'        => [dec($antigo['desconto']), dec($d['desconto'])],
                    'id_status'       => [$listaStatus[$antigo['id_status']] ?? '', $listaStatus[$d['status']] ?? ''],
                    'id_empresa'      => [$listaEmpresas[$antigo['id_empresa']] ?? 'Todas', $listaEmpresas[$d['empresa']] ?? 'Todas']
                ];

                // só grava no histórico o que realmente mudou
                foreach ($comparar as $campo => $par) {
                    if ((string)$par[0] !== (string)$par[1]) {
                        registrarHistorico($pdo, $idAcesso, $d['empresa'], $id, 'beneficios', $campo, $par[0], $par[1]);
                    }
                }

                $msgCodigo = 'editado';
            }
        }

        /* ---------- apagar ---------- */
        if ($Acao === 'apagar') {

            $id = (int) ($_POST['id'] ?? 0);

            $busca = $pdo->prepare("SELECT nome_beneficios, id_empresa FROM beneficios WHERE id_beneficios = ?");
            $busca->execute([$id]);
            $b = $busca->fetch();

            if ($b) {

                $del = $pdo->prepare("DELETE FROM beneficios WHERE id_beneficios = ?");
                $del->execute([$id]);   // os vínculos em colaborador_beneficio são apagados junto (CASCADE)

                registrarHistorico($pdo, $idAcesso, $b['id_empresa'], $id, 'beneficios', 'exclusao', $b['nome_beneficios'], '');
                $msgCodigo = 'apagado';
            }
        }

        /* ---------- vincular colaborador ao benefício ---------- */
        if ($Acao === 'vincular') {

            $idB    = (int) ($_POST['id_beneficios'] ?? 0);
            $idC    = (int) ($_POST['id_colaboradores'] ?? 0);
            $inicio = $_POST['data_inicio'] ?? '';

            $dataOk = DateTime::createFromFormat('Y-m-d', $inicio);
            $dataOk = $dataOk && $dataOk->format('Y-m-d') === $inicio;

            $stmtB = $pdo->prepare("SELECT nome_beneficios, id_empresa FROM beneficios WHERE id_beneficios = ?");
            $stmtB->execute([$idB]);
            $b = $stmtB->fetch();

            $stmtC = $pdo->prepare("SELECT nome_user, id_empresa FROM colaboradores WHERE id_colaboradores = ?");
            $stmtC->execute([$idC]);
            $c = $stmtC->fetch();

            $idAtivo = array_search('Ativo', $listaStatus, true);

            if ($b && $c && $dataOk && $idAtivo !== false) {

                // benefício de uma empresa só pode ir para colaborador dessa mesma empresa
                if ($b['id_empresa'] !== null && (int)$b['id_empresa'] !== (int)$c['id_empresa']) {

                    $msgCodigo = 'outraempresa';
                } else {

                    $add = $pdo->prepare("INSERT INTO colaborador_beneficio
                            (id_colaboradores, id_beneficios, id_status, data_inicio)
                            VALUES (?,?,?,?)");
                    $add->execute([$idC, $idB, $idAtivo, $inicio]);

                    registrarHistorico($pdo, $idAcesso, $c['id_empresa'], $pdo->lastInsertId(), 'colaborador_beneficio', 'vinculo', '', $c['nome_user'] . ' + ' . $b['nome_beneficios']);
                    $msgCodigo = 'vinculado';
                }
            }
        }

        /* ---------- remover vínculo ---------- */
        if ($Acao === 'desvincular') {

            $idV = (int) ($_POST['id_vinculo'] ?? 0);

            $busca = $pdo->prepare("SELECT cb.id_vinculo_beneficio, c.nome_user, c.id_empresa, b.nome_beneficios
                    FROM colaborador_beneficio cb
                    JOIN colaboradores c ON c.id_colaboradores = cb.id_colaboradores
                    JOIN beneficios b ON b.id_beneficios = cb.id_beneficios
                    WHERE cb.id_vinculo_beneficio = ?");
            $busca->execute([$idV]);
            $v = $busca->fetch();

            if ($v) {

                $del = $pdo->prepare("DELETE FROM colaborador_beneficio WHERE id_vinculo_beneficio = ?");
                $del->execute([$idV]);

                registrarHistorico($pdo, $idAcesso, $v['id_empresa'], $idV, 'colaborador_beneficio', 'desvinculo', $v['nome_user'] . ' + ' . $v['nome_beneficios'], '');
                $msgCodigo = 'desvinculado';
            }
        }
    } catch (PDOException $ex) {

        $codigo = $ex->errorInfo[1] ?? 0;

        if ($codigo == 1062) {
            $msgCodigo = 'duplicado';
        }        // já existe esse vínculo
        else if ($codigo == 1451) {
            $msgCodigo = 'emuso';
        }       // ainda é usado em outra tabela
        else {
            $msgCodigo = 'invalido';
        }
    }

    // redireciona para não reenviar o formulário ao atualizar a página
    header("Location: beneficios.php?msg=" . $msgCodigo);
    exit();
}

/* ======================= MENSAGEM DE RETORNO ========================== */

$mensagens = [
    'criado'       => ['ok',   'Benefício cadastrado com sucesso.'],
    'editado'      => ['ok',   'Benefício atualizado com sucesso.'],
    'apagado'      => ['ok',   'Benefício apagado.'],
    'vinculado'    => ['ok',   'Benefício vinculado ao colaborador.'],
    'desvinculado' => ['ok',   'Vínculo removido.'],
    'duplicado'    => ['erro', 'Esse colaborador já possui este benefício.'],
    'outraempresa' => ['erro', 'Este benefício pertence a outra empresa.'],
    'emuso'        => ['erro', 'Não foi possível apagar: o benefício ainda está em uso. Inative-o em vez de apagar.'],
    'invalido'     => ['erro', 'Dados inválidos. Confira os campos e tente de novo.']
];

$aviso = $mensagens[$_GET['msg'] ?? ''] ?? null;

/* ========================= DADOS DA LISTAGEM ========================== */

$beneficios    = [];
$vinculos      = [];   // id_beneficio => [colaboradores vinculados]
$colaboradores = [];

if ($bancoPronto) {

    $sqlLista = "SELECT b.*, s.status_agora, e.nome_empresas,
                        (SELECT COUNT(*) FROM colaborador_beneficio cb WHERE cb.id_beneficios = b.id_beneficios) AS qtd
                     FROM beneficios b
                     LEFT JOIN statuss s ON s.id_status = b.id_status
                     LEFT JOIN empresas e ON e.id_empresas = b.id_empresa
                     ORDER BY b.nome_beneficios";
    $beneficios = $pdo->query($sqlLista)->fetchAll();

    $sqlVinc = "SELECT cb.id_vinculo_beneficio, cb.id_beneficios, cb.data_inicio, c.id_colaboradores, c.nome_user
                    FROM colaborador_beneficio cb
                    JOIN colaboradores c ON c.id_colaboradores = cb.id_colaboradores
                    ORDER BY c.nome_user";
    foreach ($pdo->query($sqlVinc) as $v) {
        $vinculos[(int)$v['id_beneficios']][] = [
            'id_vinculo'       => (int)$v['id_vinculo_beneficio'],
            'id_colaboradores' => (int)$v['id_colaboradores'],
            'nome'             => $v['nome_user'],
            'inicio'           => date('d/m/Y', strtotime($v['data_inicio']))
        ];
    }

    $sqlColab = "SELECT id_colaboradores, nome_user, id_empresa FROM colaboradores ORDER BY nome_user";
    foreach ($pdo->query($sqlColab) as $c) {
        $colaboradores[] = [
            'id'      => (int)$c['id_colaboradores'],
            'nome'    => $c['nome_user'],
            'empresa' => $c['id_empresa'] === null ? null : (int)$c['id_empresa']
        ];
    }
}

$totalAtivos   = 0;
$custoMensal   = 0;
$totalVinculos = 0;

foreach ($beneficios as $b) {
    $totalVinculos += (int)$b['qtd'];
    if (($b['status_agora'] ?? '') === 'Ativo') {
        $totalAtivos++;
        // custo para a empresa = (valor - desconto do colaborador) x quantidade de colaboradores
        $custoMensal += max(0, (float)$b['valor'] - (float)$b['desconto']) * (int)$b['qtd'];
    }
}

// dados que o JavaScript usa para abrir os modais
$dadosJs = [
    'hoje'          => date('Y-m-d'),
    'beneficios'    => [],
    'vinculos'      => $vinculos,
    'colaboradores' => $colaboradores
];

foreach ($beneficios as $b) {
    $dadosJs['beneficios'][(int)$b['id_beneficios']] = [
        'nome'      => $b['nome_beneficios'],
        'descricao' => $b['descricao'],
        'tipo'      => $b['tipo'],
        'valor'     => dec($b['valor']),
        'desconto'  => dec($b['desconto']),
        'empresa'   => $b['id_empresa'] === null ? null : (int)$b['id_empresa'],
        'status'    => $b['id_status'] === null ? null : (int)$b['id_status']
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
    <link rel="stylesheet" href="../assets/css/beneficios.css">
    <title>Benefícios</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="top1">

                <div class="img">
                    <!-- Lucide: gift -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 7v14" />
                        <path d="M20 11v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8" />
                        <path d="M7.5 7a1 1 0 0 1 0-5A4.8 8 0 0 1 12 7a4.8 8 0 0 1 4.5-5 1 1 0 0 1 0 5" />
                        <rect x="3" y="7" width="18" height="4" rx="1" />
                    </svg>
                </div>

                <div class="text">
                    <h3>Benefícios</h3>
                    <p>Gerencie os benefícios e quem os recebe</p>
                </div>

            </div>

            <div class="top2">

                <div class="profile" title="<?= e($_SESSION['User']) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>

                <button type="button" id="novo" <?= $bancoPronto ? '' : 'disabled' ?>>Novo benefício</button>

            </div>

        </header>

        <?php if (!$bancoPronto): ?>

            <p class="aviso erro">O banco ainda não tem as tabelas do módulo de benefícios. Execute o arquivo <strong>database/beneficios.sql</strong> e recarregue a página.</p>

        <?php elseif ($aviso): ?>

            <p class="aviso <?= e($aviso[0]) ?>" role="status"><?= e($aviso[1]) ?></p>

        <?php endif; ?>

        <div class="resumo">

            <div class="card">
                <span>Benefícios ativos</span>
                <strong><?= $totalAtivos ?></strong>
            </div>

            <div class="card">
                <span>Custo mensal para a empresa</span>
                <strong><?= moeda($custoMensal) ?></strong>
            </div>

            <div class="card">
                <span>Vínculos com colaboradores</span>
                <strong><?= $totalVinculos ?></strong>
            </div>

        </div>

        <section>

            <input placeholder="Pesquise por nome, tipo ou empresa" type="search" id="ipt" aria-label="Pesquisar benefícios">

            <select id="filtroStatus" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>

        </section>

        <main>

            <div class="tabela">

                <table>

                    <thead>
                        <tr>
                            <th>Benefício</th>
                            <th>Tipo</th>
                            <th>Empresa</th>
                            <th>Valor/mês</th>
                            <th>Desconto</th>
                            <th>Status</th>
                            <th>Colaboradores</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($beneficios as $b):

                            $nomeStatus  = $b['status_agora'] ?? 'Sem status';
                            $nomeEmpresa = $b['nome_empresas'] ?? 'Todas as empresas';
                            $textoBusca  = mb_strtolower($b['nome_beneficios'] . ' ' . $b['tipo'] . ' ' . $nomeEmpresa . ' ' . $b['descricao']);

                        ?>

                            <tr data-status="<?= e(mb_strtolower($nomeStatus)) ?>" data-busca="<?= e($textoBusca) ?>">

                                <td>
                                    <strong><?= e($b['nome_beneficios']) ?></strong>
                                    <?php if ($b['descricao'] !== ''): ?>
                                        <span class="sub"><?= e($b['descricao']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($b['tipo']) ?></td>
                                <td><?= e($nomeEmpresa) ?></td>
                                <td><?= moeda($b['valor']) ?></td>
                                <td><?= moeda($b['desconto']) ?></td>
                                <td><span class="badge <?= e(mb_strtolower($nomeStatus)) ?>"><?= e($nomeStatus) ?></span></td>
                                <td><?= (int)$b['qtd'] ?></td>

                                <td>
                                    <div class="acoes">

                                        <button type="button" class="btn-acao btn-vincular" data-id="<?= (int)$b['id_beneficios'] ?>"
                                            title="Colaboradores deste benefício" aria-label="Colaboradores deste benefício">
                                            <!-- Lucide: users -->
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                                <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                                <circle cx="9" cy="7" r="4" />
                                            </svg>
                                        </button>

                                        <button type="button" class="btn-acao btn-editar" data-id="<?= (int)$b['id_beneficios'] ?>"
                                            title="Editar benefício" aria-label="Editar benefício">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                        </button>

                                        <button type="button" class="btn-acao btn-excluir" data-id="<?= (int)$b['id_beneficios'] ?>"
                                            title="Apagar benefício" aria-label="Apagar benefício">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                <line x1="10" y1="11" x2="10" y2="17" />
                                                <line x1="14" y1="11" x2="14" y2="17" />
                                            </svg>
                                        </button>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <p class="vazio" id="vazio" <?= count($beneficios) > 0 ? 'hidden' : '' ?>>
                    <?= count($beneficios) > 0 ? 'Nenhum benefício encontrado para esse filtro.' : 'Nenhum benefício cadastrado ainda. Clique em "Novo benefício".' ?>
                </p>

            </div>

        </main>

    </div>

    <dialog class="modal" id="modalForm">

        <div class="topModal">
            <h3 id="tituloForm">Cadastrar benefício</h3>
        </div>

        <div class="mainModal">

            <form action="beneficios.php" method="post" id="formBeneficio">

                <div class="ipt">

                    <div class="separation2">
                        <label for="fNome">Nome do benefício</label>
                        <input required maxlength="100" type="text" name="nome" id="fNome">
                    </div>

                </div>

                <div class="ipt">

                    <div class="separation2">
                        <label for="fDescricao">Descrição</label>
                        <textarea maxlength="250" name="descricao" id="fDescricao" rows="2"></textarea>
                    </div>

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="fTipo">Tipo</label>
                        <select name="tipo" id="fTipo">
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= e($t) ?>"><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="separation">
                        <label for="fEmpresa">Empresa</label>
                        <select name="empresa" id="fEmpresa">
                            <option value="">Todas as empresas</option>
                            <?php foreach ($listaEmpresas as $idEmp => $nomeEmp): ?>
                                <option value="<?= (int)$idEmp ?>"><?= e($nomeEmp) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="fValor">Valor mensal (R$)</label>
                        <input required type="number" min="0" step="0.01" name="valor" id="fValor" value="0.00">
                    </div>

                    <div class="separation">
                        <label for="fDesconto">Desconto do colaborador (R$)</label>
                        <input required type="number" min="0" step="0.01" name="desconto" id="fDesconto" value="0.00">
                    </div>

                </div>

                <div class="ipt">

                    <div class="separation">
                        <label for="fStatus">Status</label>
                        <select required name="status" id="fStatus">
                            <?php foreach ($listaStatus as $idSt => $nomeSt): ?>
                                <option value="<?= (int)$idSt ?>" <?= $nomeSt === 'Ativo' ? 'selected' : '' ?>><?= e($nomeSt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <p class="erro" id="erroForm" role="alert"></p>

                <div class="btns">
                    <button type="submit">Confirmar</button>
                    <button type="button" data-fechar>Cancelar</button>
                </div>

                <!-- campos para o PHP saber o que fazer -->
                <input type="hidden" name="acao" id="acaoForm" value="cadastrar">
                <input type="hidden" name="id" id="idForm" value="">

            </form>

        </div>

    </dialog>


    <!-- ========================= MODAL: APAGAR ============================= -->
    <dialog class="modal modal-pequeno" id="modalApagar">

        <form action="beneficios.php" method="post">

            <div class="topModal">
                <h3>Apagar benefício</h3>
            </div>

            <p class="texto-modal">Tem certeza que deseja apagar <strong id="nomeExcluir"></strong>? Os vínculos com colaboradores também serão removidos.</p>

            <input type="hidden" name="id" id="idExcluir">
            <input type="hidden" name="acao" value="apagar">

            <div class="btns">
                <button type="button" data-fechar>Cancelar</button>
                <button type="submit" class="excluir">Apagar</button>
            </div>

        </form>

    </dialog>


    <!-- ================= MODAL: COLABORADORES DO BENEFÍCIO ================= -->
    <dialog class="modal" id="modalVinculos">

        <div class="topModal">
            <h3 id="tituloVinc">Colaboradores</h3>
        </div>

        <div class="mainModal">

            <ul class="lista-vinculos" id="listaVinc"></ul>

            <form action="beneficios.php" method="post" id="formVincular">

                <div class="ipt">

                    <div class="separation">
                        <label for="vColab">Adicionar colaborador</label>
                        <select name="id_colaboradores" id="vColab" required></select>
                    </div>

                    <div class="separation">
                        <label for="vData">Data de início</label>
                        <input required type="date" name="data_inicio" id="vData">
                    </div>

                </div>

                <div class="btns">
                    <button type="submit" id="btnVincular">Vincular</button>
                    <button type="button" data-fechar>Fechar</button>
                </div>

                <input type="hidden" name="acao" value="vincular">
                <input type="hidden" name="id_beneficios" id="vIdBeneficio">

            </form>

        </div>

    </dialog>


    <script>
        const DADOS = <?= json_encode($dadosJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../assets/js/beneficios.js"></script>

</body>

</html>