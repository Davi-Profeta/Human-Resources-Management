<?php

require_once "../../src/config/conexao.php";
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

// nome de tela de cada tabela e de cada campo
$tabelas = [
    'beneficios'            => 'Benefício',
    'colaborador_beneficio' => 'Benefício',
    'vinculo_trabalista'    => 'Vínculo',
    'colaboradores'         => 'Colaborador',
    'empresas'              => 'Empresa',
    'acessos'               => 'Usuário',
];

$campos = [
    'cargo' => 'Cargo',
    'salario' => 'Salário',
    'data_inicio' => 'Data de início',
    'data_termino' => 'Data de término',
    'id_status' => 'Status',
    'id_empresa' => 'Empresa',
    'tipo_vinculo' => 'Tipo de vínculo',
    'nome_user' => 'Nome',
    'CPF' => 'CPF',
    'email' => 'E-mail',
    'endereço' => 'Endereço',
    'telefone' => 'Telefone',
    'foto' => 'Foto',
    'nome_beneficios' => 'Nome',
    'tipo' => 'Tipo',
    'valor' => 'Valor',
    'desconto' => 'Desconto',
    'nome_empresas' => 'Nome',
    'CNPJ' => 'CNPJ',
    'endereco' => 'Endereço',
    'razao_social' => 'Razão social',
    'id_stats' => 'Status',
];

// tipo da ação => [nome, ícone]
$tipos = [
    'cadastro'     => ['Cadastro', icone('<path d="M5 12h14"/><path d="M12 5v14"/>')],
    'edicao'       => ['Atualização', icone('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>')],
    'exclusao'     => ['Exclusão', icone('<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>')],
    'desligamento' => ['Desligamento', icone('<path d="M2 21a8 8 0 0 1 11.873-7"/><circle cx="10" cy="8" r="5"/><path d="m17 17 5 5"/><path d="m22 17-5 5"/>')],
];

// as 200 alterações mais recentes; o nome do registro vem da própria tabela alterada
$registros = $pdo->query("SELECT h.*, a.nome_user AS usuario,
        CASE h.tabela_alterada
            WHEN 'beneficios' THEN (SELECT nome_beneficios FROM beneficios WHERE id_beneficios = h.id_registro)
            WHEN 'vinculo_trabalista' THEN (SELECT nome_user FROM colaboradores WHERE id_trabalista = h.id_registro LIMIT 1)
            WHEN 'colaboradores' THEN (SELECT nome_user FROM colaboradores WHERE id_colaboradores = h.id_registro)
            WHEN 'empresas' THEN (SELECT nome_empresas FROM empresas WHERE id_empresas = h.id_registro)
            WHEN 'acessos' THEN (SELECT nome_user FROM acessos WHERE id_acesso = h.id_registro)
        END AS alvo
    FROM historico_alteracoes h
    LEFT JOIN acessos a ON a.id_acesso = h.id_acessos
    ORDER BY h.data_alteracao DESC, h.id_historicos DESC
    LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

// números dos cartões do topo (contam o histórico inteiro, não só os 200)
$total = (int)$pdo->query("SELECT COUNT(*) FROM historico_alteracoes")->fetchColumn();

$hoje = $pdo->prepare("SELECT COUNT(*) FROM historico_alteracoes WHERE DATE(data_alteracao) = ?");
$hoje->execute([date('Y-m-d')]);
$hoje = (int)$hoje->fetchColumn();

$usuarios = $pdo->prepare("SELECT COUNT(DISTINCT id_acessos) FROM historico_alteracoes WHERE data_alteracao >= ?");
$usuarios->execute([date('Y-m-d', strtotime('-30 days'))]);
$usuarios = (int)$usuarios->fetchColumn();

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
    <link rel="stylesheet" href="../assets/css/historico.css?v=<?= filemtime(__DIR__ . '/../assets/css/historico.css') ?>">
    <link rel="stylesheet" href="../assets/css/all.css">
    <title>Histórico de alterações</title>

</head>

<body>

    <div class="container">

        <header>
            <div class="titulo">
                <div class="img"><?= icone('<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>') ?></div>
                <div>
                    <h3>Histórico de alterações</h3>
                    <p>Acompanhe as atividades realizadas no sistema</p>
                </div>
            </div>
        </header>

        <div class="resumo">
            <div class="card">
                <?= icone('<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>') ?>
                <span>Total de ações</span>
                <strong><?= $total ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>') ?>
                <span>Ações hoje</span>
                <strong><?= $hoje ?></strong>
            </div>
            <div class="card">
                <?= icone('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') ?>
                <span>Usuários ativos (30 dias)</span>
                <strong><?= $usuarios ?></strong>
            </div>
        </div>

        <section>
            <input type="search" id="ipt" placeholder="Pesquisar por usuário ou alteração..." aria-label="Pesquisar no histórico">

            <select id="filtro" aria-label="Filtrar por tipo de ação">
                <option value="">Todas as ações</option>
                <option value="cadastro">Cadastros</option>
                <option value="edicao">Edições</option>
                <option value="exclusao">Exclusões</option>
                <option value="desligamento">Desligamentos</option>
            </select>
        </section>

        <main>

            <div class="lista">

                <?php foreach ($registros as $r):

                    $campo   = $r['campo_alterado'];
                    $tag     = $tabelas[$r['tabela_alterada']] ?? ucfirst($r['tabela_alterada']);
                    $alvo    = $r['alvo'] ?: 'registro #' . $r['id_registro'];
                    $anterior = $r['valor_anterior'];
                    $novo     = $r['valor_novo'];

                    // descobre o tipo e monta a frase que aparece na lista
                    if ($campo === 'cadastro') {
                        $tipo = 'cadastro';
                        $frase = 'Cadastro de ' . $novo;
                    } elseif ($campo === 'exclusao') {
                        $tipo = 'exclusao';
                        $frase = 'Exclusão de ' . $anterior;
                    } elseif ($campo === 'desligamento') {
                        $tipo = 'desligamento';
                        $frase = 'Desligamento de ' . $alvo . ' registrado';
                    } elseif ($campo === 'vinculo') {
                        $tipo = 'edicao';
                        $frase = 'Benefício vinculado: ' . $novo;
                    } elseif ($campo === 'desvinculo') {
                        $tipo = 'edicao';
                        $frase = 'Benefício desvinculado: ' . $anterior;
                    } else {
                        $tipo = 'edicao';
                        $frase = ($campos[$campo] ?? ucfirst(str_replace('_', ' ', $campo))) . ' de ' . $alvo . ' atualizado';
                    }

                    $quando = strtotime($r['data_alteracao']);
                    $quem   = $r['usuario'] ?: 'Usuário removido';

                ?>

                    <details class="registro" data-tipo="<?= $tipo ?>" data-busca="<?= e(mb_strtolower($frase . ' ' . $quem . ' ' . $tag . ' ' . $tipos[$tipo][0])) ?>">

                        <summary>
                            <span class="icone <?= $tipo ?>"><?= $tipos[$tipo][1] ?></span>
                            <span class="texto">
                                <span class="linha1"><strong><?= $tipos[$tipo][0] ?></strong> <em><?= e($tag) ?></em></span>
                                <span class="linha2"><?= e($frase) ?></span>
                                <small><?= e($quem) ?> · <?= date('d/m/Y', $quando) ?> • <?= date('H:i', $quando) ?></small>
                            </span>
                            <?= icone('<path d="m6 9 6 6 6-6"/>') ?>
                        </summary>

                        <div class="detalhes">
                            <h4>Detalhes da alteração</h4>
                            <dl>
                                <div>
                                    <dt>Antes</dt>
                                    <dd><?= e($anterior !== '' ? $anterior : '—') ?></dd>
                                </div>
                                <div>
                                    <dt>Depois</dt>
                                    <dd><?= e($novo !== '' ? $novo : '—') ?></dd>
                                </div>
                            </dl>
                        </div>

                    </details>

                <?php endforeach; ?>

                <p class="vazio" id="vazio" <?= $registros ? 'hidden' : '' ?>>Nenhuma alteração encontrada.</p>

            </div>

        </main>

    </div>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="../assets/js/historico.js?v=<?= filemtime(__DIR__ . '/../assets/js/historico.js') ?>"></script>

</body>

</html>
