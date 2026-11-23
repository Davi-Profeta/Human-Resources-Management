<?php

require_once "../../src/config/conexao.php";
require_once "../../src/config/gravarHistorico.php";
session_start();
date_default_timezone_set('America/Sao_Paulo');

// só entra quem estiver logado
if (!isset($_SESSION['User'])) {
    header("Location: ../index.php");
    exit;
}

// escapa o texto antes de mostrar na tela (evita XSS)
function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// desenha um ícone a partir do conteúdo do <svg>
function icone($desenho)
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $desenho . '</svg>';
}

// id de cada status (Ativo / Inativo)
$status = $pdo->query("SELECT status_agora, id_status FROM statuss")->fetchAll(PDO::FETCH_KEY_PAIR);

/* ========================= REGISTRAR DESLIGAMENTO ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idColaborador = (int)($_POST['colaborador'] ?? 0);
    $data          = $_POST['data'] ?? '';
    $motivo        = mb_substr(trim($_POST['motivo'] ?? ''), 0, 250);
    $msg           = 'erro';

    if ($idColaborador && $motivo !== '' && DateTime::createFromFormat('Y-m-d', $data)) {

        $pdo->beginTransaction();

        try {

            $acesso = $pdo->prepare("SELECT id_acesso FROM acessos WHERE nome_user = ? LIMIT 1");
            $acesso->execute([$_SESSION['User']]);

            // grava o desligamento
            $pdo->prepare("INSERT INTO desligamento (id_colaboradores, id_acessos, motivo, data_desligamento)
                VALUES (?, ?, ?, ?)")
                ->execute([$idColaborador, $acesso->fetchColumn() ?: null, $motivo, $data . ' 00:00:00']);

            // status que o colaborador tinha antes (para o histórico)
            $antes = $pdo->prepare("SELECT s.status_agora FROM colaboradores c LEFT JOIN statuss s ON s.id_status = c.id_status WHERE c.id_colaboradores = ?");
            $antes->execute([$idColaborador]);

            // colaborador passa para Inativo
            $pdo->prepare("UPDATE colaboradores SET id_status = ? WHERE id_colaboradores = ?")
                ->execute([$status['Inativo'], $idColaborador]);
            gravarHistorico($pdo, $idColaborador, 'colaboradores', 'desligamento', $antes->fetchColumn() ?: 'Sem vínculo', 'Inativo');

            // encerra o vínculo que ainda estiver aberto
            $pdo->prepare("UPDATE vinculo_trabalista v
                JOIN colaboradores c ON c.id_trabalista = v.id_trabalista
                SET v.data_termino = ?, v.id_status = ?
                WHERE c.id_colaboradores = ? AND (v.data_termino IS NULL OR v.data_termino > ?)")
                ->execute([$data, $status['Inativo'], $idColaborador, $data]);

            $pdo->commit();
            $msg = 'ok';
        } catch (PDOException $erro) {
            $pdo->rollBack();
            error_log('Desligamento: ' . $erro->getMessage());
            $codigo = '&cod=' . (int)($erro->errorInfo[1] ?? 0);
        }
    }

    header("Location: desligamentos.php?msg=" . $msg . ($codigo ?? ''));
    exit;
}

/* ================================ LISTAGEM ================================ */

$desligamentos = $pdo->query("SELECT d.motivo, d.data_desligamento, c.nome_user AS nome, v.cargo,
        e.nome_empresas AS empresa, a.nome_user AS registrou
    FROM desligamento d
    JOIN colaboradores c ON c.id_colaboradores = d.id_colaboradores
    LEFT JOIN vinculo_trabalista v ON v.id_trabalista = c.id_trabalista
    LEFT JOIN empresas e ON e.id_empresas = c.id_empresa
    LEFT JOIN acessos a ON a.id_acesso = d.id_acessos
    ORDER BY d.data_desligamento DESC, d.id_desligamento DESC")->fetchAll(PDO::FETCH_ASSOC);

// só colaboradores que ainda não estão Inativos podem ser desligados (Ativos ou sem vínculo)
$ativos = $pdo->prepare("SELECT id_colaboradores, nome_user FROM colaboradores WHERE id_status IS NULL OR id_status <> ? ORDER BY nome_user");
$ativos->execute([$status['Inativo'] ?? 0]);
$ativos = $ativos->fetchAll(PDO::FETCH_ASSOC);

// números dos cartões do topo
$total    = count($desligamentos);
$noMes    = count(array_filter($desligamentos, fn($d) => substr($d['data_desligamento'], 0, 7) === date('Y-m')));
$empresas = count(array_unique(array_filter(array_column($desligamentos, 'empresa'))));

$mensagens = [
    'ok'   => ['ok', 'Desligamento registrado.'],
    'erro' => ['erro', 'Não foi possível registrar o desligamento.'],
];
$aviso = $mensagens[$_GET['msg'] ?? ''] ?? null;
if ($aviso && !empty($_GET['cod'])) {
    $aviso[1] .= ' (código ' . (int)$_GET['cod'] . ')';
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <!-- fundo escuro aplicado antes de qualquer arquivo externo: evita o "pisca branco" ao trocar de página no iframe -->
    <style>
        html,
        body {
            background-color: #0f172a;
            color-scheme: dark;
        }
    </style>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/desligamentos.css?v=<?= filemtime(__DIR__ . '/../assets/css/desligamentos.css') ?>">
    <link rel="stylesheet" href="../assets/css/all.css">
    <title>Desligamentos</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="titulo">
                <div class="img"><?= icone('<path d="M2 21a8 8 0 0 1 13.292-6"/><circle cx="10" cy="8" r="5"/><path d="M22 19h-6"/>') ?></div>
                <div>
                    <h3>Desligamentos</h3>
                    <p>Histórico de desligamentos</p>
                </div>
            </div>

            <button type="button" id="novo">
                <?= icone('<path d="M5 12h14"/><path d="M12 5v14"/>') ?> Registrar
            </button>

        </header>

        <?php if ($aviso): ?>
            <p class="aviso <?= $aviso[0] ?>" role="status"><?= e($aviso[1]) ?></p>
        <?php endif; ?>

        <div class="resumo">
            <div class="card">
                <?= icone('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') ?>
                <span>Total</span>
                <strong><?= $total ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>') ?>
                <span>Neste mês</span>
                <strong><?= $noMes ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/>') ?>
                <span>Empresas</span>
                <strong><?= $empresas ?></strong>
            </div>
        </div>

        <input type="search" id="ipt" placeholder="Pesquisar colaborador, empresa ou motivo..." aria-label="Pesquisar desligamentos">

        <main>

            <div class="lista">

                <?php foreach ($desligamentos as $d): ?>

                    <details class="desligamento" data-busca="<?= e(mb_strtolower($d['nome'] . ' ' . $d['empresa'] . ' ' . $d['motivo'] . ' ' . $d['cargo'])) ?>">

                        <summary>
                            <span class="foto"><?= icone('<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>') ?></span>
                            <span class="quem">
                                <strong><?= e($d['nome']) ?></strong>
                                <small><?= e($d['cargo'] ?: 'Sem cargo') ?> · <?= e($d['empresa'] ?: 'Sem empresa') ?></small>
                            </span>
                            <span class="badge">Desligado</span>
                            <?= icone('<path d="m6 9 6 6 6-6"/>') ?>
                        </summary>

                        <dl class="info">
                            <div>
                                <dt>Data do desligamento</dt>
                                <dd><?= date('d/m/Y', strtotime($d['data_desligamento'])) ?></dd>
                            </div>
                            <div>
                                <dt>Motivo</dt>
                                <dd><?= e($d['motivo']) ?></dd>
                            </div>
                            <div>
                                <dt>Empresa</dt>
                                <dd><?= e($d['empresa'] ?: '—') ?></dd>
                            </div>
                            <div>
                                <dt>Registrado por</dt>
                                <dd><?= e($d['registrou'] ?: '—') ?></dd>
                            </div>
                        </dl>

                    </details>

                <?php endforeach; ?>

                <p class="vazio" id="vazio" <?= $desligamentos ? 'hidden' : '' ?>>Nenhum desligamento encontrado.</p>

            </div>

        </main>

    </div>

    <dialog class="modal" id="dialog">

        <form method="post">

            <div class="topModal">
                <h3>Registrar desligamento</h3>
            </div>

            <label for="colaborador">Colaborador</label>
            <select name="colaborador" id="colaborador" required>
                <option value=""><?= $ativos ? 'Selecione' : 'Nenhum colaborador disponível' ?></option>
                <?php foreach ($ativos as $c): ?>
                    <option value="<?= (int)$c['id_colaboradores'] ?>"><?= e($c['nome_user']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="data">Data do desligamento</label>
            <input type="date" name="data" id="data" value="<?= date('Y-m-d') ?>" required>

            <label for="motivo">Motivo</label>
            <textarea name="motivo" id="motivo" maxlength="250" required></textarea>

            <div class="btns">
                <button type="button" id="cancelar">Cancelar</button>
                <button type="submit" <?= $ativos ? '' : 'disabled' ?>>Registrar</button>
            </div>

        </form>

    </dialog>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="../assets/js/desligamentos.js?v=<?= filemtime(__DIR__ . '/../assets/js/desligamentos.js') ?>"></script>

</body>

</html>
