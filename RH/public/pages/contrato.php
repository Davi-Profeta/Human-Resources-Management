<?php

require_once "../../src/config/conexao.php";
session_start();

// só entra quem estiver logado
if (!isset($_SESSION['User'])) {
    header("Location: ../index.php");
    exit;
}

date_default_timezone_set('America/Sao_Paulo');

// escapa o texto antes de mostrar na tela (evita XSS)
function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function moeda($valor)
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function data($valor)
{
    return $valor ? date('d/m/Y', strtotime($valor)) : '';
}

function cpf($n)
{
    $n = preg_replace('/\D/', '', (string)$n);
    return strlen($n) === 11 ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $n) : $n;
}

function cnpj($n)
{
    $n = preg_replace('/\D/', '', (string)$n);
    return strlen($n) === 14 ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $n) : $n;
}

$id = (int)($_GET['id'] ?? 0);

$busca = $pdo->prepare("SELECT v.cargo, v.data_inicio, v.data_termino, v.salario,
        e.nome_empresas, e.razao_social, e.CNPJ, e.endereco AS end_empresa,
        c.id_colaboradores, c.nome_user, c.CPF, c.email, c.telefone, c.`endereço` AS end_colab,
        t.nome_vinculo
    FROM vinculo_trabalista v
    JOIN empresas e ON e.id_empresas = v.id_empresa
    LEFT JOIN colaboradores c ON c.id_trabalista = v.id_trabalista
    LEFT JOIN tipo_vinculo t ON t.id_vinculo = c.id_vinculo
    WHERE v.id_trabalista = ?");
$busca->execute([$id]);
$d = $busca->fetch(PDO::FETCH_ASSOC);

$beneficios = [];
if ($d && $d['id_colaboradores']) {
    $b = $pdo->prepare("SELECT b.nome_beneficios, cb.valor_aplicado AS valor, cb.desconto_aplicado AS desconto
        FROM colaborador_beneficio cb
        JOIN beneficios b ON b.id_beneficios = cb.id_beneficios
        JOIN statuss s ON s.id_status = cb.id_status
        WHERE cb.id_colaboradores = ? AND s.status_agora = 'Ativo'
        ORDER BY b.nome_beneficios");
    $b->execute([$d['id_colaboradores']]);
    $beneficios = $b->fetchAll(PDO::FETCH_ASSOC);
}

// o modelo muda conforme o tipo de vínculo
$tipo = $d ? mb_strtolower(trim((string)$d['nome_vinculo'])) : '';
$modelo = 'trabalho';
if (in_array($tipo, ['pj', 'autônomo', 'autonomo'])) {
    $modelo = 'servicos';
} elseif (in_array($tipo, ['estágio', 'estagio'])) {
    $modelo = 'estagio';
}
$prazo = $d && $d['data_termino'];

$meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$hoje  = date('d') . ' de ' . $meses[(int)date('m') - 1] . ' de ' . date('Y');

$pronto = $d && $d['nome_user'];

if ($pronto) {

    $titulos = [
        'trabalho' => $prazo ? 'Contrato Individual de Trabalho por Prazo Determinado' : 'Contrato Individual de Trabalho',
        'servicos' => 'Contrato de Prestação de Serviços',
        'estagio'  => 'Termo de Compromisso de Estágio',
    ];
    // [parte que paga, parte que trabalha]
    $partes = [
        'trabalho' => ['EMPREGADORA', 'EMPREGADO(A)'],
        'servicos' => ['CONTRATANTE', 'CONTRATADO(A)'],
        'estagio'  => ['CONCEDENTE', 'ESTAGIÁRIO(A)'],
    ];
    [$parteA, $parteB] = $partes[$modelo];
    $pagamento = ['trabalho' => 'salário mensal', 'servicos' => 'remuneração mensal', 'estagio' => 'bolsa-auxílio mensal'][$modelo];
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
    <link rel="stylesheet" href="../assets/css/contrato.css?v=<?= filemtime(__DIR__ . '/../assets/css/contrato.css') ?>">
    <title>Contrato</title>

</head>

<body>

    <div class="tela">

        <div class="barra">

            <a href="<?= isset($_GET['novo']) ? 'colaboradores.php' : 'contratos.php' ?>" class="voltar">← Voltar</a>

            <?php if ($pronto): ?>
                <button type="button" onclick="window.print()">Imprimir / Salvar PDF</button>
            <?php endif; ?>

        </div>

        <?php if (isset($_GET['novo']) && $pronto): ?>
            <p class="aviso ok">Colaborador contratado e vínculo criado. Confira o contrato abaixo.</p>
        <?php endif; ?>

        <?php if ($pronto && (float)$d['salario'] <= 0): ?>
            <p class="aviso erro">O salário deste vínculo está em R$ 0,00. Ajuste em <a href="vinculos.php">Vínculos</a> antes de imprimir.</p>
        <?php endif; ?>

        <?php if (!$pronto): ?>

            <p class="aviso erro">Não foi possível gerar o contrato: o vínculo não existe ou não tem colaborador ligado.</p>

        <?php else: ?>

            <article class="papel">

                <h1><?= e($titulos[$modelo]) ?></h1>

                <p>
                    Pelo presente instrumento particular, de um lado <strong><?= e($d['razao_social'] ?: $d['nome_empresas']) ?></strong>,
                    inscrita no CNPJ sob o nº <strong><?= e(cnpj($d['CNPJ'])) ?></strong>, com sede em <?= e($d['end_empresa']) ?>,
                    doravante denominada <strong><?= $parteA ?></strong>, e, de outro lado, <strong><?= e($d['nome_user']) ?></strong>,
                    inscrito(a) no CPF sob o nº <strong><?= e(cpf($d['CPF'])) ?></strong>, residente em <?= e($d['end_colab']) ?>,
                    e-mail <?= e($d['email']) ?>, doravante denominado(a) <strong><?= $parteB ?></strong>,
                    têm entre si justo e contratado o presente instrumento, que se regerá pelas cláusulas seguintes:
                </p>

                <h2>Cláusula 1ª — Do objeto</h2>
                <?php if ($modelo === 'trabalho'): ?>
                    <p>O(A) EMPREGADO(A) é admitido(a) para exercer a função de <strong><?= e($d['cargo']) ?></strong>, comprometendo-se a executar as atividades inerentes ao cargo com zelo e dedicação.</p>
                <?php elseif ($modelo === 'servicos'): ?>
                    <p>O(A) CONTRATADO(A) prestará à CONTRATANTE serviços na função de <strong><?= e($d['cargo']) ?></strong>, de forma autônoma, com liberdade técnica e organização própria do trabalho.</p>
                <?php else: ?>
                    <p>O(A) ESTAGIÁRIO(A) realizará estágio na função de <strong><?= e($d['cargo']) ?></strong>, em atividades compatíveis com a sua formação escolar, com acompanhamento de um supervisor designado pela CONCEDENTE.</p>
                <?php endif; ?>

                <h2>Cláusula 2ª — Da jornada</h2>
                <?php if ($modelo === 'trabalho'): ?>
                    <p>A jornada será de 8 (oito) horas diárias e 44 (quarenta e quatro) horas semanais, observados os intervalos legais, podendo ser ajustada por acordo entre as partes, nos termos da legislação.</p>
                <?php elseif ($modelo === 'servicos'): ?>
                    <p>Não há jornada fixa. Os serviços serão prestados conforme a demanda e os prazos combinados entre as partes.</p>
                <?php else: ?>
                    <p>A jornada de estágio não ultrapassará 6 (seis) horas diárias e 30 (trinta) horas semanais, conforme a Lei nº 11.788/2008, e será compatível com o horário escolar.</p>
                <?php endif; ?>

                <h2>Cláusula 3ª — Da remuneração</h2>
                <p>
                    A <?= $modelo === 'estagio' ? 'CONCEDENTE' : $parteA ?> pagará ao(à) <?= $parteB ?> <?= $pagamento ?> de <strong><?= moeda($d['salario']) ?></strong>,
                    <?= $modelo === 'servicos' ? 'mediante emissão de nota fiscal ou recibo, ' : '' ?>até o 5º (quinto) dia útil do mês seguinte ao período trabalhado<?= $modelo === 'trabalho' ? ', sujeito aos descontos previstos em lei' : '' ?>.
                </p>

                <h2>Cláusula 4ª — Dos benefícios</h2>
                <?php if ($beneficios): ?>
                    <p>Ficam concedidos os seguintes benefícios:</p>
                    <ul>
                        <?php foreach ($beneficios as $b): ?>
                            <li>
                                <?= e($b['nome_beneficios']) ?> — <?= moeda($b['valor']) ?>
                                <?= (float)$b['desconto'] > 0 ? ' (desconto ao colaborador: ' . moeda($b['desconto']) . ')' : '' ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Não há benefícios cadastrados para este contrato até a presente data.</p>
                <?php endif; ?>

                <h2>Cláusula 5ª — Da vigência</h2>
                <p>
                    Este instrumento vigora a partir de <strong><?= data($d['data_inicio']) ?></strong>,
                    <?= $prazo ? 'com término previsto em <strong>' . data($d['data_termino']) . '</strong>' : 'por prazo indeterminado' ?>.
                </p>

                <h2>Cláusula 6ª — Das obrigações e da confidencialidade</h2>
                <p>O(A) <?= $parteB ?> compromete-se a cumprir as normas internas da empresa e a manter sigilo sobre as informações a que tiver acesso em razão desta relação, mesmo após o seu término.</p>

                <?php if ($modelo !== 'trabalho'): ?>
                    <h2>Cláusula 7ª — Da natureza da relação</h2>
                    <p>O presente instrumento não gera vínculo empregatício entre as partes<?= $modelo === 'estagio' ? ', conforme a Lei nº 11.788/2008' : '' ?>.</p>
                <?php endif; ?>

                <h2>Cláusula <?= $modelo === 'trabalho' ? '7ª' : '8ª' ?> — Da rescisão</h2>
                <p>
                    <?= $modelo === 'trabalho'
                        ? 'A rescisão observará o disposto na legislação trabalhista vigente.'
                        : 'Qualquer das partes poderá rescindir este instrumento mediante comunicação escrita com antecedência mínima de 30 (trinta) dias.' ?>
                </p>

                <h2>Cláusula <?= $modelo === 'trabalho' ? '8ª' : '9ª' ?> — Do foro</h2>
                <p>As partes elegem o foro da comarca da sede da <?= $parteA ?> para dirimir quaisquer dúvidas oriundas deste instrumento.</p>

                <p class="local">E, por estarem de acordo, firmam o presente em 2 (duas) vias de igual teor.</p>
                <p class="local"><?= $hoje ?>.</p>

                <div class="assinaturas">
                    <div>
                        <span></span>
                        <strong><?= e($d['razao_social'] ?: $d['nome_empresas']) ?></strong>
                        <small><?= $parteA ?></small>
                    </div>
                    <div>
                        <span></span>
                        <strong><?= e($d['nome_user']) ?></strong>
                        <small><?= $parteB ?></small>
                    </div>
                </div>

                <p class="rodape">Modelo gerado pelo RH Management. Revise com o setor jurídico antes de assinar.</p>

            </article>

        <?php endif; ?>

    </div>

</body>

</html>
