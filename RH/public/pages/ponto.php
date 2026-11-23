<?php

require_once "../../src/config/conexao.php";
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['User'])) {
    header("Location: ../index.php");
    exit();
}

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function hora($valor)
{
    return $valor ? date('H:i', strtotime($valor)) : '—';
}

function horaMinutos($valor)
{
    return $valor ? ((int)date('H', strtotime($valor)) * 60 + (int)date('i', strtotime($valor))) : null;
}

function duracao($inicio, $fim, $intervaloInicio = null, $intervaloFim = null)
{
    if (!$inicio || !$fim) {
        return '—';
    }

    $minutos = horaMinutos($fim) - horaMinutos($inicio);
    if ($intervaloInicio && $intervaloFim) {
        $minutos -= max(0, horaMinutos($intervaloFim) - horaMinutos($intervaloInicio));
    }

    if ($minutos < 0) {
        return '—';
    }

    return floor($minutos / 60) . 'h ' . str_pad((string)($minutos % 60), 2, '0', STR_PAD_LEFT) . 'min';
}

function primeiraMarca($marcas, $tipos)
{
    foreach ($marcas as $marca) {
        if (in_array($marca['tipo'], $tipos, true)) {
            return $marca['data_hora'];
        }
    }

    return null;
}

function classeSituacao($situacao)
{
    return match ($situacao) {
        'Presente' => 'ok',
        'Atrasado', 'Fora da jornada' => 'alerta',
        'Folga', 'Sem jornada' => 'neutro',
        'Sem registro' => 'erro',
        default => 'pendente'
    };
}

$hoje = date('Y-m-d');
$data = $_GET['data'] ?? $hoje;
$d = DateTime::createFromFormat('Y-m-d', $data);
if (!$d || $d->format('Y-m-d') !== $data) {
    $data = $hoje;
}

$empresaSelecionada = (int)($_GET['empresa'] ?? 0);
$busca = trim((string)($_GET['busca'] ?? ''));
$empresas = $pdo->query("SELECT id_empresas, nome_empresas FROM empresas ORDER BY nome_empresas")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT c.id_colaboradores, c.nome_user, c.id_empresa, e.nome_empresas,
                v.id_trabalista, v.cargo, v.jornada_entrada, v.jornada_intervalo_inicio,
                v.jornada_intervalo_fim, v.jornada_saida, v.jornada_tolerancia, v.jornada_dias
            FROM colaboradores c
            INNER JOIN statuss s ON s.id_status = c.id_status
                AND LOWER(TRIM(s.status_agora)) = 'ativo'
            LEFT JOIN empresas e ON e.id_empresas = c.id_empresa
            LEFT JOIN vinculo_trabalista v ON v.id_trabalista = c.id_trabalista
            WHERE 1 = 1";
$params = [];

if ($empresaSelecionada > 0) {
    $sql .= " AND c.id_empresa = ?";
    $params[] = $empresaSelecionada;
}

if ($busca !== '') {
    $sql .= " AND (c.nome_user LIKE ? OR v.cargo LIKE ?)";
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
}

$sql .= " ORDER BY c.nome_user";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$marcacoesPorColaborador = [];
if ($colaboradores) {
    $ids = array_map(fn($colaborador) => (int)$colaborador['id_colaboradores'], $colaboradores);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtMarcas = $pdo->prepare("SELECT id_colaboradores, data_hora, tipo
            FROM ponto_marcacoes
            WHERE data_local = ? AND id_colaboradores IN ($placeholders)
            ORDER BY data_hora ASC");
    $stmtMarcas->execute(array_merge([$data], $ids));

    foreach ($stmtMarcas as $marca) {
        $marcacoesPorColaborador[(int)$marca['id_colaboradores']][] = $marca;
    }
}

$numeroDia = (int)date('N', strtotime($data));
$linhas = [];
$totalChegaram = 0;
$totalAtrasados = 0;
$totalPendencias = 0;
$totalSemJornada = 0;

foreach ($colaboradores as $colaborador) {
    $marcas = $marcacoesPorColaborador[(int)$colaborador['id_colaboradores']] ?? [];
    $entrada = primeiraMarca($marcas, ['Entrada']);
    $intervaloInicio = primeiraMarca($marcas, ['Início intervalo', 'Intervalo']);
    $intervaloFim = primeiraMarca($marcas, ['Retorno intervalo']);
    $saida = primeiraMarca($marcas, ['Saída']);
    $temJornada = !empty($colaborador['jornada_entrada']) && !empty($colaborador['jornada_saida']);
    $diasJornada = array_filter(explode(',', (string)$colaborador['jornada_dias']));
    $trabalhaNoDia = in_array((string)$numeroDia, $diasJornada, true);
    $temIntervalo = !empty($colaborador['jornada_intervalo_inicio']) && !empty($colaborador['jornada_intervalo_fim']);

    // O card "Chegaram" representa qualquer entrada registrada,
    // inclusive quando o colaborador marcou fora dos dias previstos.
    if ($entrada) {
        $totalChegaram++;
    }

    if (!$temJornada) {
        $situacao = 'Sem jornada';
        $totalSemJornada++;
    } elseif (!$trabalhaNoDia) {
        $situacao = $entrada ? 'Fora da jornada' : 'Folga';
    } elseif (!$entrada) {
        $situacao = 'Sem registro';
    } else {
        $limiteAtraso = horaMinutos($colaborador['jornada_entrada']) + (int)$colaborador['jornada_tolerancia'];
        $situacao = horaMinutos($entrada) > $limiteAtraso ? 'Atrasado' : 'Presente';

        if ($situacao === 'Atrasado') {
            $totalAtrasados++;
        }

        if ($temIntervalo && !$intervaloInicio) {
            $situacao = 'Intervalo pendente';
        } elseif ($temIntervalo && $intervaloInicio && !$intervaloFim) {
            $situacao = 'Retorno pendente';
        } elseif (!$saida) {
            $situacao = 'Saída pendente';
        }

        if (str_contains($situacao, 'pendente')) {
            $totalPendencias++;
        }
    }

    $jornada = $temJornada
        ? hora($colaborador['jornada_entrada']) . '–' . hora($colaborador['jornada_saida'])
        : 'Não definida';
    if ($temIntervalo) {
        $jornada .= ' · ' . hora($colaborador['jornada_intervalo_inicio']) . '–' . hora($colaborador['jornada_intervalo_fim']);
    }

    $linhas[] = [
        'nome' => $colaborador['nome_user'],
        'empresa' => $colaborador['nome_empresas'] ?: 'Sem empresa',
        'cargo' => $colaborador['cargo'] ?: 'Sem cargo',
        'jornada' => $jornada,
        'entrada' => $entrada,
        'intervalo_inicio' => $intervaloInicio,
        'intervalo_fim' => $intervaloFim,
        'saida' => $saida,
        'horas' => duracao($entrada, $saida, $intervaloInicio, $intervaloFim),
        'situacao' => $situacao
    ];
}

$total = count($linhas);
$nomeData = date('d/m/Y', strtotime($data));

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <style>html, body { background-color: #0f172a; }</style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/ponto-rh.css?v=<?= filemtime(__DIR__ . '/../assets/css/ponto-rh.css') ?>">
    <title>Ponto do RH</title>
</head>
<body>
    <main class="pontoRh">
        <header class="cabecalhoPonto">
            <div class="tituloPonto">
                <span class="iconePonto">✓</span>
                <div>
                    <h1>Controle de ponto</h1>
                    <p>Acompanhe a jornada dos colaboradores em <?= e($nomeData) ?>.</p>
                </div>
            </div>
            <div class="acoesPonto">
                <a class="btnSecundario" href="../ponto/index.php" target="_blank" rel="noopener">Abrir portal</a>
                <a class="btnSecundario" href="vinculos.php" target="iframe">Configurar jornadas</a>
                <span class="atualizacaoPonto" id="statusAtualizacao" role="status">Atualização automática ativa</span>
            </div>
        </header>

        <form class="filtrosPonto" method="get">
            <label>Data
                <input type="date" name="data" value="<?= e($data) ?>">
            </label>
            <label>Empresa
                <select name="empresa">
                    <option value="0">Todas as empresas</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?= (int)$empresa['id_empresas'] ?>" <?= $empresaSelecionada === (int)$empresa['id_empresas'] ? 'selected' : '' ?>><?= e($empresa['nome_empresas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="buscaPonto">Colaborador ou cargo
                <input type="search" name="busca" value="<?= e($busca) ?>" placeholder="Pesquisar">
            </label>
            <button class="btnPrincipal" type="submit">Filtrar</button>
        </form>

        <section class="resumoPontoRh">
            <div class="cardPonto"><span>Colaboradores ativos</span><strong><?= $total ?></strong></div>
            <div class="cardPonto positivo"><span>Chegaram</span><strong><?= $totalChegaram ?></strong></div>
            <div class="cardPonto alerta"><span>Atrasados</span><strong><?= $totalAtrasados ?></strong></div>
            <div class="cardPonto pendente"><span>Pendências</span><strong><?= $totalPendencias ?></strong></div>
        </section>

        <?php if ($totalSemJornada > 0): ?>
            <div class="avisoPonto"><?= $totalSemJornada ?> colaborador(es) ainda está(ão) sem jornada configurada. Acesse “Configurar jornadas” para completar o vínculo.</div>
        <?php endif; ?>

        <section class="tabelaPontoCard">
            <div class="cabecalhoTabela">
                <div>
                    <span class="etiqueta">Visão diária</span>
                    <h2>Registros de <?= e($nomeData) ?></h2>
                </div>
                <span class="contadorPonto"><?= $total ?> colaborador(es)</span>
            </div>

            <?php if ($linhas): ?>
                <div class="tabelaWrapPonto">
                    <table>
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Jornada prevista</th>
                                <th>Entrada</th>
                                <th>Intervalo</th>
                                <th>Retorno</th>
                                <th>Saída</th>
                                <th>Horas</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($linhas as $linha): ?>
                                <tr>
                                    <td><strong><?= e($linha['nome']) ?></strong><small><?= e($linha['empresa']) ?> · <?= e($linha['cargo']) ?></small></td>
                                    <td><?= e($linha['jornada']) ?></td>
                                    <td><?= e(hora($linha['entrada'])) ?></td>
                                    <td><?= e(hora($linha['intervalo_inicio'])) ?></td>
                                    <td><?= e(hora($linha['intervalo_fim'])) ?></td>
                                    <td><?= e(hora($linha['saida'])) ?></td>
                                    <td><?= e($linha['horas']) ?></td>
                                    <td><span class="situacao <?= e(classeSituacao($linha['situacao'])) ?>"><?= e($linha['situacao']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="vazioPonto">Nenhum colaborador encontrado para os filtros informados.</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
        (() => {
            const intervaloAtualizacao = 10000;
            const status = document.getElementById('statusAtualizacao');

            const atualizarPonto = () => {
                if (document.hidden) return;

                const ativo = document.activeElement;
                if (ativo && ['INPUT', 'SELECT', 'TEXTAREA'].includes(ativo.tagName)) return;

                const url = new URL(window.location.href);
                url.searchParams.set('atualizacao', Date.now());

                if (status) status.textContent = 'Atualizando registros…';
                window.location.replace(url.toString());
            };

            setInterval(atualizarPonto, intervaloAtualizacao);
        })();
    </script>
</body>
</html>
