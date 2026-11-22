<?php

require_once "../../src/config/conexao.php";
require_once "../../src/config/gravarHistorico.php";
session_start();

// só entra quem estiver logado
if (!isset($_SESSION['User'])) {
    header("Location: ../index.php");
    exit;
}

$listaStatus = ['Aberta', 'Em análise', 'Encerrada'];
$modalidades = ['Presencial', 'Híbrido', 'Remoto'];
$etapas      = ['Triagem', 'Entrevista', 'Proposta', 'Contratado', 'Reprovado'];
$origens     = ['LinkedIn', 'Indicação', 'Site da empresa', 'Outro'];

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

// texto vazio vira NULL
function nulo($texto, $limite)
{
    $texto = mb_substr(trim((string)$texto), 0, $limite);
    return $texto === '' ? null : $texto;
}

$empresas = $pdo->query("SELECT id_empresas, nome_empresas FROM empresas ORDER BY nome_empresas")->fetchAll(PDO::FETCH_KEY_PAIR);
$vinculos = $pdo->query("SELECT id_vinculo, nome_vinculo FROM tipo_vinculo ORDER BY nome_vinculo")->fetchAll(PDO::FETCH_KEY_PAIR);
$cargos   = $pdo->query("SELECT nome_cargo FROM cargos ORDER BY nome_cargo")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    $msg  = 'erro';

    // dados atuais da vaga (para conferir o que mudou e para o histórico)
    $busca = $pdo->prepare("SELECT v.*, c.nome_cargo, e.nome_empresas
        FROM vagas v JOIN cargos c ON c.id_cargo = v.id_cargo JOIN empresas e ON e.id_empresas = v.id_empresa
        WHERE v.id_vaga = ?");
    $busca->execute([$id]);
    $vaga = $busca->fetch(PDO::FETCH_ASSOC);

    // nova vaga ou edição
    if ($acao === 'salvar') {

        $empresa    = (int)($_POST['empresa'] ?? 0);
        $nomeCargo  = nulo($_POST['cargo'] ?? '', 100);
        $posicoes   = max(1, min(999, (int)($_POST['posicoes'] ?? 1)));
        $modalidade = in_array($_POST['modalidade'] ?? '', $modalidades) ? $_POST['modalidade'] : 'Presencial';
        $vinculo    = isset($vinculos[(int)($_POST['vinculo'] ?? 0)]) ? (int)$_POST['vinculo'] : null;
        $cidade     = nulo($_POST['cidade'] ?? '', 100);
        $salario    = ($_POST['salario'] ?? '') !== '' ? max(0, round((float)$_POST['salario'], 2)) : null;
        $descricao  = nulo($_POST['descricao'] ?? '', 2000);

        if (isset($empresas[$empresa]) && $nomeCargo !== null) {

            $pdo->prepare("INSERT IGNORE INTO cargos (nome_cargo) VALUES (?)")->execute([$nomeCargo]);
            $idCargo = $pdo->prepare("SELECT id_cargo FROM cargos WHERE nome_cargo = ?");
            $idCargo->execute([$nomeCargo]);
            $idCargo = (int)$idCargo->fetchColumn();

            if ($vaga) {
                $pdo->prepare("UPDATE vagas SET id_empresa = ?, id_cargo = ?, id_vinculo = ?, posicoes = ?, modalidade = ?, cidade = ?, salario = ?, descricao = ? WHERE id_vaga = ?")
                    ->execute([$empresa, $idCargo, $vinculo, $posicoes, $modalidade, $cidade, $salario, $descricao, $id]);

                // grava só o que mudou
                $mudancas = [
                    'cargo'      => [$vaga['nome_cargo'], $nomeCargo],
                    'id_empresa' => [$vaga['nome_empresas'], $empresas[$empresa]],
                    'id_vinculo' => [$vinculos[$vaga['id_vinculo']] ?? '', $vinculos[$vinculo] ?? ''],
                    'posicoes'   => [$vaga['posicoes'], $posicoes],
                    'modalidade' => [$vaga['modalidade'], $modalidade],
                    'cidade'     => [$vaga['cidade'], $cidade],
                    'salario'    => [$vaga['salario'] !== null ? number_format($vaga['salario'], 2, ',', '.') : '', $salario !== null ? number_format($salario, 2, ',', '.') : ''],
                    'descricao'  => [$vaga['descricao'], $descricao],
                ];
                foreach ($mudancas as $campo => [$antes, $depois]) {
                    if ((string)$antes !== (string)$depois) {
                        gravarHistorico($pdo, $id, 'vagas', $campo, $antes === '' || $antes === null ? '—' : $antes, $depois === '' || $depois === null ? '—' : $depois, $empresa);
                    }
                }
            } else {
                $pdo->prepare("INSERT INTO vagas (id_empresa, id_cargo, id_vinculo, posicoes, status, cidade, modalidade, salario, descricao, data_abertura)
                    VALUES (?, ?, ?, ?, 'Aberta', ?, ?, ?, ?, CURDATE())")
                    ->execute([$empresa, $idCargo, $vinculo, $posicoes, $cidade, $modalidade, $salario, $descricao]);
                $id = $pdo->lastInsertId();
                gravarHistorico($pdo, $id, 'vagas', 'cadastro', '', $nomeCargo . ' - ' . $empresas[$empresa], $empresa);
            }
            $msg = 'ok';
        }
    }

    if ($vaga && $acao === 'status' && in_array($_POST['status'] ?? '', $listaStatus)) {
        $pdo->prepare("UPDATE vagas SET status = ? WHERE id_vaga = ?")->execute([$_POST['status'], $id]);
        if ($vaga['status'] !== $_POST['status']) {
            gravarHistorico($pdo, $id, 'vagas', 'status', $vaga['status'], $_POST['status'], $vaga['id_empresa']);
        }
        $msg = 'ok';
    }

    if ($vaga && $acao === 'apagar') {
        $pdo->prepare("DELETE FROM vagas WHERE id_vaga = ?")->execute([$id]);
        gravarHistorico($pdo, $id, 'vagas', 'exclusao', $vaga['nome_cargo'] . ' - ' . $vaga['nome_empresas'], '', $vaga['id_empresa']);
        $msg = 'apagada';
        $id  = 0;
    }

    // novo candidato (candidato vazio) ou edição de um existente
    if ($vaga && $acao === 'candidato') {
        $idCand = (int)($_POST['candidato'] ?? 0);
        $nome   = nulo($_POST['nome'] ?? '', 100);
        $link   = nulo($_POST['linkedin'] ?? '', 255);
        if ($link !== null && !preg_match('#^https?://#i', $link)) {
            $link = 'https://' . $link;
        }
        $origem = in_array($_POST['origem'] ?? '', $origens) ? $_POST['origem'] : 'Outro';
        $dados  = ['nome' => $nome, 'email' => nulo($_POST['email'] ?? '', 150), 'telefone' => nulo($_POST['telefone'] ?? '', 20),
            'linkedin' => $link, 'origem' => $origem, 'observacoes' => nulo($_POST['observacoes'] ?? '', 500)];

        if ($nome !== null && !$idCand) {
            $pdo->prepare("INSERT INTO candidatos (id_vaga, nome, email, telefone, linkedin, origem, observacoes, etapa, data_candidatura) VALUES (?, ?, ?, ?, ?, ?, ?, 'Triagem', CURDATE())")
                ->execute([$id, ...array_values($dados)]);
            gravarHistorico($pdo, $pdo->lastInsertId(), 'candidatos', 'cadastro', '', $nome . ' - ' . $vaga['nome_cargo'], $vaga['id_empresa']);
            $msg = 'ok';
        }

        if ($nome !== null && $idCand) {
            $antes = $pdo->prepare("SELECT * FROM candidatos WHERE id_candidato = ? AND id_vaga = ?");
            $antes->execute([$idCand, $id]);
            $antes = $antes->fetch(PDO::FETCH_ASSOC);

            if ($antes) {
                $pdo->prepare("UPDATE candidatos SET nome = ?, email = ?, telefone = ?, linkedin = ?, origem = ?, observacoes = ? WHERE id_candidato = ?")
                    ->execute([...array_values($dados), $idCand]);
                foreach ($dados as $campo => $depois) {
                    if ((string)$antes[$campo] !== (string)$depois) {
                        gravarHistorico($pdo, $idCand, 'candidatos', $campo, $antes[$campo] ?? '—', $depois ?? '—', $vaga['id_empresa']);
                    }
                }
                $msg = 'ok';
            }
        }
    }

    // etapa ou exclusão de um candidato (o id recebido é o do candidato)
    if (in_array($acao, ['etapa', 'candidato_apagar'])) {
        $busca = $pdo->prepare("SELECT c.*, v.id_empresa FROM candidatos c JOIN vagas v ON v.id_vaga = c.id_vaga WHERE c.id_candidato = ?");
        $busca->execute([$id]);
        $cand = $busca->fetch(PDO::FETCH_ASSOC);

        if ($cand && $acao === 'etapa' && in_array($_POST['etapa'] ?? '', $etapas)) {
            $pdo->prepare("UPDATE candidatos SET etapa = ? WHERE id_candidato = ?")->execute([$_POST['etapa'], $id]);
            if ($cand['etapa'] !== $_POST['etapa']) {
                gravarHistorico($pdo, $id, 'candidatos', 'etapa', $cand['etapa'], $_POST['etapa'], $cand['id_empresa']);
            }
            $msg = 'ok';
        }

        if ($cand && $acao === 'candidato_apagar') {
            $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?")->execute([$id]);
            gravarHistorico($pdo, $id, 'candidatos', 'exclusao', $cand['nome'], '', $cand['id_empresa']);
            $msg = 'ok';
        }

        $id = $cand['id_vaga'] ?? 0;
    }

    header("Location: vagas.php?msg=" . $msg . ($id ? "#vaga-" . $id : ""));
    exit;
}

// vagas (abertas primeiro) e candidatos de cada uma
$vagas = $pdo->query("SELECT v.*, c.nome_cargo, e.nome_empresas, t.nome_vinculo
    FROM vagas v
    JOIN cargos c ON c.id_cargo = v.id_cargo
    JOIN empresas e ON e.id_empresas = v.id_empresa
    LEFT JOIN tipo_vinculo t ON t.id_vinculo = v.id_vinculo
    ORDER BY FIELD(v.status, 'Aberta', 'Em análise', 'Encerrada'), v.data_abertura DESC, v.id_vaga DESC")->fetchAll(PDO::FETCH_ASSOC);

$candidatos = [];
foreach ($pdo->query("SELECT * FROM candidatos ORDER BY data_candidatura DESC, id_candidato DESC")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $candidatos[$c['id_vaga']][] = $c;
}

$abertas   = count(array_filter($vagas, fn($v) => $v['status'] === 'Aberta'));
$emAnalise = count(array_filter($vagas, fn($v) => $v['status'] === 'Em análise'));
$totalCand = array_sum(array_map('count', $candidatos));

$mensagens = [
    'ok'      => ['ok', 'Alteração salva.'],
    'apagada' => ['ok', 'Vaga apagada.'],
    'erro'    => ['erro', 'Não foi possível concluir a ação.'],
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
    <link rel="stylesheet" href="../assets/css/vagas.css?v=<?= filemtime(__DIR__ . '/../assets/css/vagas.css') ?>">
    <link rel="stylesheet" href="../assets/css/all.css">
    <title>Vagas e Recrutamento</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="titulo">
                <div class="img"><?= icone('<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') ?></div>
                <div>
                    <h3>Vagas e Recrutamento</h3>
                    <p>Gerencie oportunidades e acompanhe as contratações</p>
                </div>
            </div>

            <button type="button" id="novo" <?= $empresas ? '' : 'disabled' ?>>
                <?= icone('<path d="M5 12h14"/><path d="M12 5v14"/>') ?> Nova vaga
            </button>

        </header>

        <?php if ($aviso): ?>
            <p class="aviso <?= $aviso[0] ?>" role="status"><?= e($aviso[1]) ?></p>
        <?php endif; ?>

        <div class="resumo">
            <div class="card">
                <?= icone('<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') ?>
                <span>Vagas abertas</span>
                <strong><?= $abertas ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') ?>
                <span>Candidaturas</span>
                <strong><?= $totalCand ?></strong>
            </div>
            <div class="card">
                <?= icone('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>') ?>
                <span>Em análise</span>
                <strong><?= $emAnalise ?></strong>
            </div>
        </div>

        <input type="search" id="ipt" placeholder="Pesquisar vaga ou empresa..." aria-label="Pesquisar vagas">

        <div class="filtros">
            <button type="button" class="chip ativo" data-filtro="">Todas</button>
            <?php foreach ($listaStatus as $s): ?>
                <button type="button" class="chip" data-filtro="<?= e($s) ?>"><?= e($s) ?></button>
            <?php endforeach; ?>
        </div>

        <main>

            <div class="lista">

                <?php foreach ($vagas as $v):
                    $lista = $candidatos[$v['id_vaga']] ?? [];
                    $cls   = ['Aberta' => 'aberta', 'Em análise' => 'analise', 'Encerrada' => 'encerrada'][$v['status']];
                ?>

                    <details class="vaga" id="vaga-<?= (int)$v['id_vaga'] ?>" data-status="<?= e($v['status']) ?>"
                        data-busca="<?= e(mb_strtolower($v['nome_cargo'] . ' ' . $v['nome_empresas'] . ' ' . $v['cidade'])) ?>">

                        <summary>
                            <span class="topo">
                                <span class="icone"><?= icone('<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') ?></span>
                                <span class="quem">
                                    <strong><?= e($v['nome_cargo']) ?></strong>
                                    <small><?= e($v['nome_empresas']) ?></small>
                                </span>
                                <span class="status <?= $cls ?>"><?= e($v['status']) ?></span>
                            </span>

                            <span class="tags">
                                <span><?= icone('<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>') ?> <?= e($v['cidade'] ?: 'Local não informado') ?></span>
                                <span><?= icone('<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>') ?> <?= e($v['modalidade']) ?></span>
                                <?php if ($v['nome_vinculo']): ?>
                                    <span><?= icone('<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h6"/>') ?> <?= e($v['nome_vinculo']) ?></span>
                                <?php endif; ?>
                            </span>

                            <span class="rodape">
                                <span class="preco">
                                    <strong><?= $v['salario'] !== null ? 'R$ ' . number_format($v['salario'], 2, ',', '.') : 'A combinar' ?></strong>
                                    <small><?= (int)$v['posicoes'] ?> posição(ões) · <?= count($lista) ?> candidato(s)</small>
                                </span>
                                <span class="ver">Ver detalhes <?= icone('<path d="m6 9 6 6 6-6"/>') ?></span>
                            </span>
                        </summary>

                        <div class="detalhes">

                            <?php if ($v['descricao']): ?>
                                <p class="descricao"><?= nl2br(e($v['descricao'])) ?></p>
                            <?php endif; ?>

                            <p class="datas">Aberta em <?= date('d/m/Y', strtotime($v['data_abertura'])) ?></p>

                            <div class="cabecaCand">
                                <h4>Candidatos</h4>
                                <button type="button" class="addCand" data-vaga="<?= (int)$v['id_vaga'] ?>">+ Adicionar candidato</button>
                            </div>

                            <?php
                            // candidatos separados por etapa (reprovados ficam recolhidos)
                            $porEtapa = array_fill_keys($etapas, []);
                            foreach ($lista as $c) {
                                $porEtapa[$c['etapa']][] = $c;
                            }

                            // desenha um candidato
                            $cartao = function ($c) use ($etapas, $v) {
                                $dias = (int)((time() - strtotime($c['data_candidatura'])) / 86400);
                                ob_start(); ?>
                                <div class="candidato">
                                    <strong><?= e($c['nome']) ?></strong>
                                    <small><?= e(implode(' · ', array_filter([$c['email'], $c['telefone']]))) ?: 'Sem contato' ?></small>
                                    <small class="meta"><span class="origem"><?= e($c['origem']) ?></span> <?= $dias === 0 ? 'hoje' : 'há ' . $dias . ' dia(s)' ?>
                                        <?php if ($c['linkedin']): ?> · <a href="<?= e($c['linkedin']) ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a><?php endif; ?></small>
                                    <?php if ($c['observacoes']): ?><small class="obs"><?= e($c['observacoes']) ?></small><?php endif; ?>

                                    <form method="post" class="etapa">
                                        <input type="hidden" name="acao" value="etapa">
                                        <input type="hidden" name="id" value="<?= (int)$c['id_candidato'] ?>">
                                        <select name="etapa" onchange="this.form.submit()" aria-label="Etapa de <?= e($c['nome']) ?>">
                                            <?php foreach ($etapas as $et): ?>
                                                <option <?= $et === $c['etapa'] ? 'selected' : '' ?>><?= $et ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>

                                    <div class="acoes">
                                        <?php if ($c['etapa'] === 'Proposta'): ?>
                                            <a class="contratar" href="colaboradores.php?contratar=<?= (int)$c['id_candidato'] ?>">Contratar</a>
                                        <?php endif; ?>
                                        <button type="button" class="editarCand" data-vaga="<?= (int)$v['id_vaga'] ?>" data-id="<?= (int)$c['id_candidato'] ?>"
                                            data-nome="<?= e($c['nome']) ?>" data-email="<?= e($c['email']) ?>" data-telefone="<?= e($c['telefone']) ?>"
                                            data-linkedin="<?= e($c['linkedin']) ?>" data-origem="<?= e($c['origem']) ?>" data-observacoes="<?= e($c['observacoes']) ?>">Editar</button>
                                        <form method="post">
                                            <input type="hidden" name="acao" value="candidato_apagar">
                                            <input type="hidden" name="id" value="<?= (int)$c['id_candidato'] ?>">
                                            <button type="submit" class="x" aria-label="Remover <?= e($c['nome']) ?>" title="Remover candidato">×</button>
                                        </form>
                                    </div>
                                </div>
                            <?php return ob_get_clean();
                            };
                            ?>

                            <div class="colunas">
                                <?php foreach (['Triagem', 'Entrevista', 'Proposta', 'Contratado'] as $et): ?>
                                    <div class="coluna">
                                        <h5><?= $et ?> <span><?= count($porEtapa[$et]) ?></span></h5>
                                        <?php foreach ($porEtapa[$et] as $c) echo $cartao($c); ?>
                                        <?php if (!$porEtapa[$et]): ?><p class="sem">Ninguém aqui.</p><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($porEtapa['Reprovado']): ?>
                                <details class="reprovados">
                                    <summary>Reprovados (<?= count($porEtapa['Reprovado']) ?>)</summary>
                                    <div class="colunas">
                                        <div class="coluna">
                                            <?php foreach ($porEtapa['Reprovado'] as $c) echo $cartao($c); ?>
                                        </div>
                                    </div>
                                </details>
                            <?php endif; ?>

                            <div class="gerenciar">
                                <form method="post">
                                    <input type="hidden" name="acao" value="status">
                                    <input type="hidden" name="id" value="<?= (int)$v['id_vaga'] ?>">
                                    <label>Status
                                        <select name="status" onchange="this.form.submit()">
                                            <?php foreach ($listaStatus as $s): ?>
                                                <option <?= $s === $v['status'] ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </form>

                                <div class="botoes">
                                    <button type="button" class="acao-txt editar"
                                        data-id="<?= (int)$v['id_vaga'] ?>"
                                        data-empresa="<?= (int)$v['id_empresa'] ?>"
                                        data-cargo="<?= e($v['nome_cargo']) ?>"
                                        data-vinculo="<?= (int)$v['id_vinculo'] ?>"
                                        data-posicoes="<?= (int)$v['posicoes'] ?>"
                                        data-modalidade="<?= e($v['modalidade']) ?>"
                                        data-cidade="<?= e($v['cidade']) ?>"
                                        data-salario="<?= e($v['salario']) ?>"
                                        data-descricao="<?= e($v['descricao']) ?>">
                                        <?= icone('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>') ?> Editar
                                    </button>
                                    <button type="button" class="acao-txt apagar" data-id="<?= (int)$v['id_vaga'] ?>" data-nome="<?= e($v['nome_cargo'] . ' - ' . $v['nome_empresas']) ?>">
                                        <?= icone('<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>') ?> Apagar
                                    </button>
                                </div>
                            </div>

                        </div>

                    </details>

                <?php endforeach; ?>

                <p class="vazio" id="vazio" <?= $vagas ? 'hidden' : '' ?>>Nenhuma vaga encontrada.</p>

            </div>

        </main>

    </div>

    <dialog class="modal largo" id="dialog">

        <form method="post">

            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="id" value="">

            <div class="topModal">
                <h3 id="tituloModal">Nova vaga</h3>
            </div>

            <div class="duas">
                <div>
                    <label for="cargo">Cargo</label>
                    <input type="text" name="cargo" id="cargo" list="cargos" maxlength="100" required autocomplete="off">
                    <datalist id="cargos">
                        <?php foreach ($cargos as $c): ?>
                            <option value="<?= e($c) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label for="empresa">Empresa</label>
                    <select name="empresa" id="empresa" required>
                        <?php foreach ($empresas as $idEmp => $nomeEmp): ?>
                            <option value="<?= (int)$idEmp ?>"><?= e($nomeEmp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="cidade">Cidade</label>
                    <input type="text" name="cidade" id="cidade" maxlength="100">
                </div>
                <div>
                    <label for="modalidade">Modalidade</label>
                    <select name="modalidade" id="modalidade">
                        <?php foreach ($modalidades as $m): ?>
                            <option><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="vinculo">Tipo de vínculo</label>
                    <select name="vinculo" id="vinculo">
                        <option value="">Não informado</option>
                        <?php foreach ($vinculos as $idVin => $nomeVin): ?>
                            <option value="<?= (int)$idVin ?>"><?= e($nomeVin) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="posicoes">Posições</label>
                    <input type="number" name="posicoes" id="posicoes" min="1" max="999" value="1" required>
                </div>
            </div>

            <label for="salario">Salário (R$)</label>
            <input type="number" name="salario" id="salario" min="0" step="0.01" placeholder="Em branco = a combinar">

            <label for="descricao">Descrição</label>
            <textarea name="descricao" id="descricao" maxlength="2000"></textarea>

            <div class="btns">
                <button type="button" id="cancelar">Cancelar</button>
                <button type="submit">Salvar</button>
            </div>

        </form>

    </dialog>

    <dialog class="modal largo" id="dialogCand">

        <form method="post">

            <input type="hidden" name="acao" value="candidato">
            <input type="hidden" name="id" id="candVaga" value="">
            <input type="hidden" name="candidato" id="candId" value="">

            <div class="topModal">
                <h3 id="tituloCand">Adicionar candidato</h3>
            </div>

            <label for="candNome">Nome</label>
            <input type="text" name="nome" id="candNome" maxlength="100" required>

            <div class="duas">
                <div>
                    <label for="candEmail">E-mail</label>
                    <input type="email" name="email" id="candEmail" maxlength="150">
                </div>
                <div>
                    <label for="candTelefone">Telefone</label>
                    <input type="text" name="telefone" id="candTelefone" maxlength="20">
                </div>
                <div>
                    <label for="candLinkedin">Link do LinkedIn</label>
                    <input type="text" name="linkedin" id="candLinkedin" maxlength="255" placeholder="linkedin.com/in/...">
                </div>
                <div>
                    <label for="candOrigem">Origem</label>
                    <select name="origem" id="candOrigem">
                        <?php foreach ($origens as $o): ?>
                            <option><?= $o ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="candObservacoes">Observações</label>
            <textarea name="observacoes" id="candObservacoes" maxlength="500"></textarea>

            <div class="btns">
                <button type="button" id="cancelarCand">Cancelar</button>
                <button type="submit">Salvar</button>
            </div>

        </form>

    </dialog>

    <dialog class="modal" id="dialogApagar">

        <form method="post">

            <input type="hidden" name="acao" value="apagar">
            <input type="hidden" name="id" id="idApagar" value="">

            <div class="topModal">
                <h3>Apagar vaga</h3>
            </div>

            <p class="confirma">Apagar <strong id="nomeApagar"></strong>? Os candidatos dessa vaga também serão apagados.</p>

            <div class="btns">
                <button type="button" id="cancelarApagar">Cancelar</button>
                <button type="submit" class="excluir">Apagar</button>
            </div>

        </form>

    </dialog>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="../assets/js/vagas.js?v=<?= filemtime(__DIR__ . '/../assets/js/vagas.js') ?>"></script>

</body>

</html>
