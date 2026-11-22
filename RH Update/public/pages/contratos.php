<?php

require_once "../../src/config/conexao.php";
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

$arquivo = '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>';

// um contrato por vínculo que tem colaborador ligado
$contratos = $pdo->query("SELECT v.id_trabalista, v.cargo, v.data_inicio, v.data_termino, v.salario,
        s.status_agora, e.nome_empresas, c.nome_user, t.nome_vinculo
    FROM vinculo_trabalista v
    JOIN colaboradores c ON c.id_trabalista = v.id_trabalista
    JOIN empresas e ON e.id_empresas = v.id_empresa
    LEFT JOIN statuss s ON s.id_status = v.id_status
    LEFT JOIN tipo_vinculo t ON t.id_vinculo = c.id_vinculo
    ORDER BY v.data_inicio DESC, v.id_trabalista DESC")->fetchAll(PDO::FETCH_ASSOC);

$ativos     = count(array_filter($contratos, fn($c) => $c['status_agora'] === 'Ativo'));
$encerrados = count($contratos) - $ativos;

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
    <link rel="stylesheet" href="../assets/css/contratos.css?v=<?= filemtime(__DIR__ . '/../assets/css/contratos.css') ?>">
    <link rel="stylesheet" href="../assets/css/all.css">
    <title>Contratos</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="titulo">
                <div class="img"><?= icone($arquivo) ?></div>
                <div>
                    <h3>Contratos</h3>
                    <p>Contratos gerados a partir dos vínculos</p>
                </div>
            </div>

        </header>

        <div class="resumo">
            <div class="card">
                <?= icone($arquivo) ?>
                <span>Contratos</span>
                <strong><?= count($contratos) ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M20 6 9 17l-5-5"/>') ?>
                <span>Vigentes</span>
                <strong><?= $ativos ?></strong>
            </div>
            <div class="card">
                <?= icone('<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>') ?>
                <span>Encerrados</span>
                <strong><?= $encerrados ?></strong>
            </div>
        </div>

        <input type="search" id="ipt" placeholder="Pesquisar por colaborador, cargo ou empresa..." aria-label="Pesquisar contratos">

        <div class="filtros">
            <button type="button" class="chip ativo" data-filtro="">Todos</button>
            <button type="button" class="chip" data-filtro="vigente">Vigentes</button>
            <button type="button" class="chip" data-filtro="encerrado">Encerrados</button>
        </div>

        <main>

            <div class="lista">

                <?php foreach ($contratos as $c):
                    $vigente = $c['status_agora'] === 'Ativo';
                ?>

                    <article class="vaga" data-status="<?= $vigente ? 'vigente' : 'encerrado' ?>"
                        data-busca="<?= e(mb_strtolower($c['nome_user'] . ' ' . $c['cargo'] . ' ' . $c['nome_empresas'])) ?>">

                        <div class="corpo">

                            <div class="topo">
                                <span class="icone"><?= icone($arquivo) ?></span>
                                <span class="quem">
                                    <strong><?= e($c['nome_user']) ?></strong>
                                    <small><?= e($c['cargo']) ?> · <?= e($c['nome_empresas']) ?></small>
                                </span>
                                <span class="status <?= $vigente ? 'aberta' : 'encerrada' ?>"><?= $vigente ? 'Vigente' : 'Encerrado' ?></span>
                            </div>

                            <div class="tags">
                                <span><?= icone('<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h6"/>') ?> <?= e($c['nome_vinculo'] ?: 'Vínculo não informado') ?></span>
                                <span><?= icone('<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/>') ?>
                                    <?= date('d/m/Y', strtotime($c['data_inicio'])) ?><?= $c['data_termino'] ? ' a ' . date('d/m/Y', strtotime($c['data_termino'])) : '' ?></span>
                            </div>

                            <div class="rodape">
                                <span class="preco">
                                    <strong>R$ <?= number_format($c['salario'], 2, ',', '.') ?></strong>
                                    <small>Salário mensal</small>
                                </span>
                                <a class="ver" href="contrato.php?id=<?= (int)$c['id_trabalista'] ?>">Ver contrato <?= icone('<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>') ?></a>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

                <p class="vazio" id="vazio" <?= $contratos ? 'hidden' : '' ?>>
                    <?= $contratos ? 'Nenhum contrato encontrado.' : 'Nenhum contrato ainda. Cadastre um vínculo para um colaborador.' ?>
                </p>

            </div>

        </main>

    </div>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="../assets/js/contratos.js?v=<?= filemtime(__DIR__ . '/../assets/js/contratos.js') ?>"></script>

</body>

</html>
