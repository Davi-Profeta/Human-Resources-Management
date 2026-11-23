<?php

require_once "../../src/config/conexao.php";
require_once "../../src/config/gravarHistorico.php";
session_start();

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

// cargos que já existem nos vínculos entram sozinhos na lista de cargos
$pdo->exec("INSERT IGNORE INTO cargos (nome_cargo) SELECT DISTINCT TRIM(cargo) FROM vinculo_trabalista WHERE TRIM(cargo) <> ''");

$idAtivo  = (int)$pdo->query("SELECT id_status FROM statuss WHERE status_agora = 'Ativo'")->fetchColumn();
$empresas = $pdo->query("SELECT id_empresas, nome_empresas FROM empresas ORDER BY nome_empresas")->fetchAll(PDO::FETCH_KEY_PAIR);

$idEmpresa = (int)($_POST['empresa'] ?? $_GET['empresa'] ?? 0);
if (!isset($empresas[$idEmpresa])) {
    $idEmpresa = (int)array_key_first($empresas);
}
$nomeEmpresa = $empresas[$idEmpresa] ?? '';

// quantos colaboradores ativos a empresa tem em um cargo
function efetivoAtual($pdo, $idEmpresa, $nomeCargo, $idAtivo)
{
    $q = $pdo->prepare("SELECT COUNT(*) FROM colaboradores c
        JOIN vinculo_trabalista v ON v.id_trabalista = c.id_trabalista
        WHERE v.id_empresa = ? AND LOWER(TRIM(v.cargo)) = LOWER(TRIM(?)) AND c.id_status = ?");
    $q->execute([$idEmpresa, $nomeCargo, $idAtivo]);
    return (int)$q->fetchColumn();
}

// vagas ainda em andamento para o cargo
function vagasEmAndamento($pdo, $idEmpresa, $idCargo)
{
    $q = $pdo->prepare("SELECT COALESCE(SUM(posicoes), 0) FROM vagas
        WHERE id_empresa = ? AND id_cargo = ? AND status IN ('Aberta', 'Em análise')");
    $q->execute([$idEmpresa, $idCargo]);
    return (int)$q->fetchColumn();
}

// grava o efetivo ideal e registra no histórico (sem linha ainda = cria; o "antes" é o que estava na tela)
function definirIdeal($pdo, $idEmpresa, $idCargo, $novo, $antesNaTela)
{
    $linha = $pdo->prepare("SELECT id_planejamento, quantidade_ideal FROM planejamento WHERE id_empresa = ? AND id_cargo = ?");
    $linha->execute([$idEmpresa, $idCargo]);
    $linha = $linha->fetch(PDO::FETCH_ASSOC);

    if ($linha) {
        $antes = (int)$linha['quantidade_ideal'];
        $id    = $linha['id_planejamento'];
        $pdo->prepare("UPDATE planejamento SET quantidade_ideal = ? WHERE id_planejamento = ?")->execute([$novo, $id]);
    } else {
        $antes = $antesNaTela;
        $pdo->prepare("INSERT INTO planejamento (id_empresa, id_cargo, quantidade_ideal) VALUES (?, ?, ?)")->execute([$idEmpresa, $idCargo, $novo]);
        $id = $pdo->lastInsertId();
    }

    if ($antes !== $novo) {
        gravarHistorico($pdo, $id, 'planejamento', 'quantidade_ideal', $antes, $novo, $idEmpresa);
    }
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao    = $_POST['acao'] ?? '';
    $idCargo = (int)($_POST['cargo'] ?? 0);
    $msg     = 'erro';

    $cargo = $pdo->prepare("SELECT nome_cargo FROM cargos WHERE id_cargo = ?");
    $cargo->execute([$idCargo]);
    $nomeCargo = $cargo->fetchColumn();

    // + e − dos cartões (resposta em JSON, a tela se atualiza sozinha)
    if ($acao === 'ideal' && $nomeCargo !== false && isset($empresas[$idEmpresa])) {
        $novo = max(0, min(999, (int)($_POST['ideal'] ?? 0)));
        definirIdeal($pdo, $idEmpresa, $idCargo, $novo, (int)($_POST['antes'] ?? 0));
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($acao === 'adicionar' && isset($empresas[$idEmpresa])) {

        $nome = trim($_POST['nome'] ?? '');
        $qtd  = max(0, min(999, (int)($_POST['qtd'] ?? 1)));

        if ($nome !== '' && mb_strlen($nome) <= 100) {
            $pdo->prepare("INSERT IGNORE INTO cargos (nome_cargo) VALUES (?)")->execute([$nome]);
            $busca = $pdo->prepare("SELECT id_cargo FROM cargos WHERE nome_cargo = ?");
            $busca->execute([$nome]);
            $idNovo = (int)$busca->fetchColumn();

            $existe = $pdo->prepare("SELECT COUNT(*) FROM planejamento WHERE id_empresa = ? AND id_cargo = ?");
            $existe->execute([$idEmpresa, $idNovo]);

            if ($existe->fetchColumn()) {
                $msg = 'existe';
            } else {
                $id = definirIdeal($pdo, $idEmpresa, $idNovo, $qtd, $qtd); // o cadastro já fica no histórico
                gravarHistorico($pdo, $id, 'planejamento', 'cadastro', '', $nome . ' - ' . $nomeEmpresa, $idEmpresa);
                $msg = 'ok';
            }
        }
    }

    if ($acao === 'remover' && $nomeCargo !== false) {
        $linha = $pdo->prepare("SELECT id_planejamento FROM planejamento WHERE id_empresa = ? AND id_cargo = ?");
        $linha->execute([$idEmpresa, $idCargo]);
        $id = $linha->fetchColumn();

        if ($id) {
            $pdo->prepare("DELETE FROM planejamento WHERE id_planejamento = ?")->execute([$id]);
            gravarHistorico($pdo, $id, 'planejamento', 'exclusao', $nomeCargo . ' - ' . $nomeEmpresa, '', $idEmpresa);
            $msg = 'removido';
        }
    }

    // cria uma vaga com a quantidade que falta
    if ($acao === 'solicitar' && $nomeCargo !== false) {
        $linha = $pdo->prepare("SELECT quantidade_ideal FROM planejamento WHERE id_empresa = ? AND id_cargo = ?");
        $linha->execute([$idEmpresa, $idCargo]);
        $falta = (int)$linha->fetchColumn() - efetivoAtual($pdo, $idEmpresa, $nomeCargo, $idAtivo);

        if ($falta > 0 && vagasEmAndamento($pdo, $idEmpresa, $idCargo) === 0) {
            $pdo->prepare("INSERT INTO vagas (id_empresa, id_cargo, posicoes, status, data_abertura) VALUES (?, ?, ?, 'Aberta', CURDATE())")
                ->execute([$idEmpresa, $idCargo, $falta]);
            gravarHistorico($pdo, $pdo->lastInsertId(), 'vagas', 'cadastro', '', $nomeCargo . ' - ' . $nomeEmpresa . ' (' . $falta . ' posição(ões))', $idEmpresa);
            $msg = 'solicitada';
        }
    }

    header("Location: planejamento.php?empresa=" . $idEmpresa . "&msg=" . $msg);
    exit;
}

// um cartão por cargo: os que têm planejamento + os que já têm gente ativa na empresa
$cargos = [];
$lista = $pdo->query("SELECT id_cargo, nome_cargo FROM cargos ORDER BY nome_cargo")->fetchAll(PDO::FETCH_ASSOC);
$ideais = $pdo->prepare("SELECT id_cargo, quantidade_ideal FROM planejamento WHERE id_empresa = ?");
$ideais->execute([$idEmpresa]);
$ideais = $ideais->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($lista as $c) {
    $atual = efetivoAtual($pdo, $idEmpresa, $c['nome_cargo'], $idAtivo);
    $tem   = isset($ideais[$c['id_cargo']]);

    if ($tem || $atual > 0) {
        $cargos[] = [
            'id'    => (int)$c['id_cargo'],
            'nome'  => $c['nome_cargo'],
            'atual' => $atual,
            'ideal' => $tem ? (int)$ideais[$c['id_cargo']] : $atual,
            'tem'   => $tem,
            'vagas' => vagasEmAndamento($pdo, $idEmpresa, $c['id_cargo']),
        ];
    }
}

$totalAtual = array_sum(array_column($cargos, 'atual'));
$totalIdeal = array_sum(array_column($cargos, 'ideal'));
$totalFalta = 0;
foreach ($cargos as $c) {
    $totalFalta += max(0, $c['ideal'] - $c['atual']);
}

$mensagens = [
    'ok'         => ['ok', 'Cargo adicionado ao planejamento.'],
    'existe'     => ['erro', 'Este cargo já está no planejamento da empresa.'],
    'removido'   => ['ok', 'Cargo removido do planejamento.'],
    'solicitada' => ['ok', 'Vaga criada. Ela já aparece em Vagas e Recrutamento.'],
    'erro'       => ['erro', 'Não foi possível concluir a ação.'],
];
$aviso = $mensagens[$_GET['msg'] ?? ''] ?? null;

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
    <link rel="stylesheet" href="../assets/css/planejamento.css?v=<?= filemtime(__DIR__ . '/../assets/css/planejamento.css') ?>">
    <link rel="stylesheet" href="../assets/css/all.css">
    <title>Planejamento de Pessoal</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="titulo">
                <div class="img"><?= icone('<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>') ?></div>
                <div>
                    <h3>Planejamento de Pessoal</h3>
                    <p>Dimensionamento do quadro de colaboradores</p>
                </div>
            </div>

            <button type="button" id="novo" <?= $empresas ? '' : 'disabled' ?>>
                <?= icone('<path d="M5 12h14"/><path d="M12 5v14"/>') ?> Cargo
            </button>

        </header>

        <?php if ($aviso): ?>
            <p class="aviso <?= $aviso[0] ?>" role="status"><?= e($aviso[1]) ?></p>
        <?php endif; ?>

        <form method="get" class="empresa">
            <label for="empresa">Empresa</label>
            <select id="empresa" name="empresa" onchange="this.form.submit()">
                <?php foreach ($empresas as $id => $nome): ?>
                    <option value="<?= (int)$id ?>" <?= $id === $idEmpresa ? 'selected' : '' ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
                <?php if (!$empresas): ?>
                    <option>Nenhuma empresa cadastrada</option>
                <?php endif; ?>
            </select>
        </form>

        <div class="resumo">
            <div class="card">
                <?= icone('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') ?>
                <span>Efetivo atual</span>
                <strong id="totalAtual"><?= $totalAtual ?></strong>
            </div>
            <div class="card">
                <?= icone('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>') ?>
                <span>Efetivo ideal</span>
                <strong id="totalIdeal"><?= $totalIdeal ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>') ?>
                <span>Posições necessárias</span>
                <strong id="totalFalta"><?= $totalFalta ?></strong>
            </div>
        </div>

        <main>

            <div class="lista" id="lista">

                <?php foreach ($cargos as $c): ?>

                    <article class="cargo" data-cargo="<?= $c['id'] ?>" data-atual="<?= $c['atual'] ?>" data-ideal="<?= $c['ideal'] ?>" data-vagas="<?= $c['vagas'] ?>">

                        <div class="linha">
                            <h4><?= e($c['nome']) ?></h4>
                            <span class="selo"></span>
                        </div>

                        <div class="linha">
                            <span class="atual">Atual: <strong><?= $c['atual'] ?></strong></span>
                            <div class="ideal">
                                <span>Ideal:</span>
                                <button type="button" class="menos" aria-label="Diminuir">−</button>
                                <strong class="valor"><?= $c['ideal'] ?></strong>
                                <button type="button" class="mais" aria-label="Aumentar">+</button>
                            </div>
                        </div>

                        <div class="barra"><span></span></div>

                        <form method="post" class="acao">
                            <input type="hidden" name="empresa" value="<?= $idEmpresa ?>">
                            <input type="hidden" name="cargo" value="<?= $c['id'] ?>">
                            <button type="submit" name="acao" value="solicitar" class="solicitar">Solicitar contratação</button>
                            <?php if ($c['tem'] && $c['atual'] === 0): ?>
                                <button type="submit" name="acao" value="remover" class="remover">Remover</button>
                            <?php endif; ?>
                        </form>

                    </article>

                <?php endforeach; ?>

                <p class="vazio" <?= $cargos ? 'hidden' : '' ?>>
                    <?= $empresas ? 'Nenhum cargo no planejamento desta empresa. Use o botão "Cargo" para começar.' : 'Cadastre uma empresa para começar o planejamento.' ?>
                </p>

            </div>

        </main>

    </div>

    <dialog class="modal" id="dialog">

        <form method="post">

            <input type="hidden" name="acao" value="adicionar">
            <input type="hidden" name="empresa" value="<?= $idEmpresa ?>">

            <div class="topModal">
                <h3>Adicionar cargo</h3>
            </div>

            <label for="nome">Cargo</label>
            <input type="text" name="nome" id="nome" list="cargos" maxlength="100" required autocomplete="off">
            <datalist id="cargos">
                <?php foreach ($lista as $c): ?>
                    <option value="<?= e($c['nome_cargo']) ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <label for="qtd">Efetivo ideal</label>
            <input type="number" name="qtd" id="qtd" min="0" max="999" value="1" required>

            <div class="btns">
                <button type="button" id="cancelar">Cancelar</button>
                <button type="submit">Adicionar</button>
            </div>

        </form>

    </dialog>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script>const EMPRESA = <?= $idEmpresa ?>;</script>
    <script src="../assets/js/planejamento.js?v=<?= filemtime(__DIR__ . '/../assets/js/planejamento.js') ?>"></script>

</body>

</html>
