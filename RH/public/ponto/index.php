<?php

require_once "../../src/config/conexao.php";
session_start();
date_default_timezone_set('America/Sao_Paulo');

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function cpfLimpo($valor)
{
    return preg_replace('/\D/', '', (string)$valor);
}

function temIntervalo($colaborador)
{
    return !empty($colaborador['jornada_intervalo_inicio']) && !empty($colaborador['jornada_intervalo_fim']);
}

function proximaMarcacao($colaborador, $quantidade)
{
    if (temIntervalo($colaborador)) {
        return ['Entrada', 'Início intervalo', 'Retorno intervalo', 'Saída'][$quantidade] ?? 'Jornada concluída';
    }

    return ['Entrada', 'Saída'][$quantidade] ?? 'Jornada concluída';
}

function tipoClasse($tipo)
{
    return match ($tipo) {
        'Entrada' => 'entrada',
        'Início intervalo' => 'intervalo',
        'Retorno intervalo' => 'retorno',
        'Saída' => 'saida',
        default => 'registro'
    };
}

function csrf()
{
    if (empty($_SESSION['ponto_csrf'])) {
        $_SESSION['ponto_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['ponto_csrf'];
}

function colaboradorAtual($pdo)
{
    $id = (int)($_SESSION['ponto_colaborador_id'] ?? 0);
    if (!$id) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT c.id_colaboradores, c.nome_user, c.CPF, c.id_empresa,
                e.nome_empresas, ca.exigir_troca,
                v.jornada_entrada, v.jornada_intervalo_inicio, v.jornada_intervalo_fim,
                v.jornada_saida, v.jornada_tolerancia, v.jornada_dias
            FROM colaboradores c
            INNER JOIN colaborador_acessos ca ON ca.id_colaboradores = c.id_colaboradores
                AND ca.ativo = 1
            LEFT JOIN empresas e ON e.id_empresas = c.id_empresa
            LEFT JOIN vinculo_trabalista v ON v.id_trabalista = c.id_trabalista
            INNER JOIN statuss s ON s.id_status = c.id_status
            WHERE c.id_colaboradores = ?
                AND LOWER(TRIM(s.status_agora)) = 'ativo'");
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function exigirCsrf()
{
    if (!hash_equals($_SESSION['ponto_csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sessão expirada. Recarregue a página.');
    }
}

$mensagem = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'login') {

        $cpf = cpfLimpo($_POST['cpf'] ?? '');
        $pin = trim((string)($_POST['pin'] ?? ''));

        if (strlen($cpf) !== 11 || !preg_match('/^\d{6}$/', $pin)) {
            $erro = 'Informe um CPF válido e um PIN de 6 dígitos.';
        } else {
            $stmt = $pdo->prepare("SELECT c.id_colaboradores, c.nome_user, ca.pin_hash
                    FROM colaboradores c
                    INNER JOIN colaborador_acessos ca ON ca.id_colaboradores = c.id_colaboradores
                        AND ca.ativo = 1
                    INNER JOIN statuss s ON s.id_status = c.id_status
                    WHERE c.CPF = ?
                        AND LOWER(TRIM(s.status_agora)) = 'ativo'");
            $stmt->execute([$cpf]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conta || !password_verify($pin, $conta['pin_hash'])) {
                $erro = 'CPF ou PIN inválido.';
            } else {
                session_regenerate_id(true);
                $_SESSION['ponto_colaborador_id'] = (int)$conta['id_colaboradores'];
                $_SESSION['ponto_nome'] = $conta['nome_user'];
                $_SESSION['ponto_csrf'] = bin2hex(random_bytes(32));

                $pdo->prepare("UPDATE colaborador_acessos SET ultimo_acesso = NOW() WHERE id_colaboradores = ?")
                    ->execute([(int)$conta['id_colaboradores']]);

                header('Location: index.php');
                exit;
            }
        }
    }

    if ($acao === 'sair') {
        unset($_SESSION['ponto_colaborador_id'], $_SESSION['ponto_nome'], $_SESSION['ponto_csrf']);
        header('Location: index.php');
        exit;
    }

    if ($acao !== 'login' && $acao !== 'sair') {
        exigirCsrf();
        $colaborador = colaboradorAtual($pdo);

        if (!$colaborador) {
            unset($_SESSION['ponto_colaborador_id'], $_SESSION['ponto_nome']);
            header('Location: index.php');
            exit;
        }

        if ($acao === 'trocar_pin') {

            $novoPin = trim((string)($_POST['novo_pin'] ?? ''));
            $confirmacao = trim((string)($_POST['confirmar_pin'] ?? ''));

            if (!preg_match('/^\d{6}$/', $novoPin)) {
                $erro = 'O novo PIN deve ter exatamente 6 dígitos.';
            } elseif ($novoPin !== $confirmacao) {
                $erro = 'A confirmação do PIN não confere.';
            } elseif ($novoPin === '123456') {
                $erro = 'Escolha um PIN diferente do PIN inicial.';
            } else {
                $pdo->prepare("UPDATE colaborador_acessos SET pin_hash = ?, exigir_troca = 0 WHERE id_colaboradores = ?")
                    ->execute([password_hash($novoPin, PASSWORD_DEFAULT), $colaborador['id_colaboradores']]);
                $mensagem = 'PIN atualizado. Agora você já pode registrar o ponto.';
                $colaborador['exigir_troca'] = 0;
            }
        }

        if ($acao === 'marcar' && !(bool)$colaborador['exigir_troca']) {

            $hoje = date('Y-m-d');
            $ultima = $pdo->prepare("SELECT data_hora FROM ponto_marcacoes
                    WHERE id_colaboradores = ? ORDER BY data_hora DESC LIMIT 1");
            $ultima->execute([$colaborador['id_colaboradores']]);
            $ultima = $ultima->fetchColumn();

            if ($ultima && (time() - strtotime($ultima)) < 60) {
                $erro = 'Aguarde um minuto antes de registrar outra marcação.';
            } else {
                $qtd = $pdo->prepare("SELECT COUNT(*) FROM ponto_marcacoes WHERE id_colaboradores = ? AND data_local = ?");
                $qtd->execute([$colaborador['id_colaboradores'], $hoje]);
                $tipo = proximaMarcacao($colaborador, (int)$qtd->fetchColumn());

                if ($tipo === 'Jornada concluída') {
                    $erro = 'A jornada de hoje já possui todos os registros esperados.';
                    $tipo = null;
                }

                if ($tipo === null) {
                    // Não cria uma marcação depois de concluir a sequência prevista.
                } else {
                $agora = date('Y-m-d H:i:s');

                $marcar = $pdo->prepare("INSERT INTO ponto_marcacoes
                        (id_colaboradores, id_empresa, data_hora, data_local, tipo, origem, ip)
                        VALUES (?, ?, ?, ?, ?, 'portal', ?)");
                $marcar->execute([
                    $colaborador['id_colaboradores'],
                    $colaborador['id_empresa'],
                    $agora,
                    $hoje,
                    $tipo,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                $mensagem = $tipo . ' registrada às ' . date('H:i', strtotime($agora)) . '.';
                }
            }
        }
    }
}

$colaborador = colaboradorAtual($pdo);
$marcacoes = [];
$proximaMarcacao = 'Entrada';

if ($colaborador) {
    $lista = $pdo->prepare("SELECT data_hora, tipo FROM ponto_marcacoes
            WHERE id_colaboradores = ? ORDER BY data_hora DESC LIMIT 20");
    $lista->execute([$colaborador['id_colaboradores']]);
    $marcacoes = $lista->fetchAll(PDO::FETCH_ASSOC);

    $hoje = date('Y-m-d');
    $qtdHoje = $pdo->prepare("SELECT COUNT(*) FROM ponto_marcacoes WHERE id_colaboradores = ? AND data_local = ?");
    $qtdHoje->execute([$colaborador['id_colaboradores'], $hoje]);
    $proximaMarcacao = proximaMarcacao($colaborador, (int)$qtdHoje->fetchColumn());
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
    <link rel="stylesheet" href="../assets/css/ponto-colaborador.css?v=<?= filemtime(__DIR__ . '/../assets/css/ponto-colaborador.css') ?>">
    <title>Portal de Ponto</title>
</head>
<body>
    <main class="portal">
        <header class="cabecalho">
            <div class="marca">
                <span class="marcaIcone" aria-hidden="true">⌚</span>
                <div>
                    <strong>Portal de Ponto</strong>
                    <small>Registro de jornada do colaborador</small>
                </div>
            </div>
            <?php if ($colaborador): ?>
                <form method="post">
                    <input type="hidden" name="acao" value="sair">
                    <button class="btnSecundario" type="submit">Sair</button>
                </form>
            <?php endif; ?>
        </header>

        <?php if ($erro): ?>
            <p class="aviso erro" role="alert"><?= e($erro) ?></p>
        <?php elseif ($mensagem): ?>
            <p class="aviso sucesso" role="status"><?= e($mensagem) ?></p>
        <?php endif; ?>

        <?php if (!$colaborador): ?>
            <section class="loginCard">
                <div class="intro">
                    <span class="etiqueta">Acesso do colaborador</span>
                    <h1>Registre seu ponto</h1>
                    <p>Entre com seu CPF e PIN para continuar.</p>
                </div>

                <form method="post" class="formulario">
                    <label for="cpf">CPF</label>
                    <input id="cpf" name="cpf" inputmode="numeric" autocomplete="username" maxlength="14" placeholder="000.000.000-00" required>

                    <label for="pin">PIN</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="current-password" maxlength="6" placeholder="6 dígitos" required>

                    <input type="hidden" name="acao" value="login">
                    <button class="btnPrincipal" type="submit">Entrar no portal</button>
                </form>

                <p class="ajuda">No primeiro acesso, use o PIN inicial informado pelo RH e troque-o quando solicitado.</p>
            </section>
        <?php else: ?>
            <section class="boasVindas">
                <div>
                    <span class="etiqueta">Olá, <?= e($colaborador['nome_user']) ?></span>
                    <h1>Seu ponto de hoje</h1>
                        <p><?= e($colaborador['nome_empresas'] ?: 'Empresa não informada') ?> · Jornada <?= e(substr((string)$colaborador['jornada_entrada'], 0, 5)) ?> às <?= e(substr((string)$colaborador['jornada_saida'], 0, 5)) ?> · <span id="relogio">--:--:--</span></p>
                </div>
                <span class="statusOnline">Acesso ativo</span>
            </section>

            <?php if ((bool)$colaborador['exigir_troca']): ?>
                <section class="trocaPin cardPortal">
                    <div>
                        <span class="etiqueta">Primeiro acesso</span>
                        <h2>Crie seu PIN pessoal</h2>
                        <p>Por segurança, troque o PIN inicial antes de registrar o ponto.</p>
                    </div>
                    <form method="post" class="formulario formularioInline">
                        <label for="novoPin">Novo PIN</label>
                        <input id="novoPin" name="novo_pin" type="password" inputmode="numeric" maxlength="6" required>
                        <label for="confirmarPin">Confirmar PIN</label>
                        <input id="confirmarPin" name="confirmar_pin" type="password" inputmode="numeric" maxlength="6" required>
                        <input type="hidden" name="acao" value="trocar_pin">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <button class="btnPrincipal" type="submit">Salvar PIN</button>
                    </form>
                </section>
            <?php else: ?>
                <section class="resumoPonto">
                    <div class="cardPortal destaque">
                        <span>Próximo registro</span>
                        <strong><?= e($proximaMarcacao) ?></strong>
                        <small><?= $proximaMarcacao === 'Jornada concluída' ? 'Todos os registros previstos para hoje foram realizados.' : 'Clique somente no momento correspondente da sua jornada.' ?></small>
                        <?php if ($proximaMarcacao !== 'Jornada concluída'): ?>
                            <form method="post">
                                <input type="hidden" name="acao" value="marcar">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <button class="btnPrincipal" type="submit">Registrar <?= e(strtolower($proximaMarcacao)) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="cardPortal">
                        <span>Última marcação</span>
                        <strong><?= $marcacoes ? date('H:i', strtotime($marcacoes[0]['data_hora'])) : '—' ?></strong>
                        <small><?= $marcacoes ? e($marcacoes[0]['tipo']) . ' · ' . date('d/m/Y', strtotime($marcacoes[0]['data_hora'])) : 'Nenhuma marcação registrada' ?></small>
                    </div>
                    <div class="cardPortal">
                        <span>Intervalo previsto</span>
                        <strong><?= temIntervalo($colaborador) ? e(substr($colaborador['jornada_intervalo_inicio'], 0, 5) . '–' . substr($colaborador['jornada_intervalo_fim'], 0, 5)) : 'Não definido' ?></strong>
                        <small>Tolerância de <?= (int)$colaborador['jornada_tolerancia'] ?> minutos para atrasos.</small>
                    </div>
                </section>
            <?php endif; ?>

            <section class="historico cardPortal">
                <div class="tituloSecao">
                    <div>
                        <span class="etiqueta">Acompanhamento</span>
                        <h2>Últimas marcações</h2>
                    </div>
                    <span class="contador"><?= count($marcacoes) ?></span>
                </div>

                <?php if ($marcacoes): ?>
                    <div class="tabelaWrap">
                        <table>
                            <thead>
                                <tr><th>Data</th><th>Horário</th><th>Registro</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($marcacoes as $marcacao): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($marcacao['data_hora'])) ?></td>
                                        <td><?= date('H:i:s', strtotime($marcacao['data_hora'])) ?></td>
                                        <td><span class="badge <?= e(tipoClasse($marcacao['tipo'])) ?>"><?= e($marcacao['tipo']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="vazio">Você ainda não possui marcações registradas.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        const relogio = document.getElementById('relogio');
        if (relogio) {
            const atualizarRelogio = () => {
                relogio.textContent = new Intl.DateTimeFormat('pt-BR', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                }).format(new Date());
            };
            atualizarRelogio();
            setInterval(atualizarRelogio, 1000);
        }

        const cpf = document.getElementById('cpf');
        if (cpf) {
            cpf.addEventListener('input', () => {
                const valor = cpf.value.replace(/\D/g, '').slice(0, 11);
                cpf.value = valor.replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            });
        }
    </script>
</body>
</html>
