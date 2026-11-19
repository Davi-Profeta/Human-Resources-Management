<?php

require_once "../config/conexao.php";

header("Content-Type: application/json; charset=utf-8");

try {
    $sql = "
        SELECT
            c.id_colaboradores,
            c.nome_user,
            COALESCE(vt.salario, 0) AS salario,
            COALESCE(SUM(b.valor), 0) AS total_beneficios_colaborador,
            (
                COALESCE(vt.salario, 0) + COALESCE(SUM(b.valor), 0)
            ) AS custo_total_colaborador,
            e.nome_empresas AS nome_empresa
        FROM colaboradores c
        LEFT JOIN vinculo_trabalista vt
            ON c.id_colaboradores = vt.id_trabalista
        LEFT JOIN colaborador_beneficio cb
            ON c.id_colaboradores = cb.id_colaboradores
            AND cb.id_status = 1
        LEFT JOIN beneficios b
            ON cb.id_beneficios = b.id_beneficios
        LEFT JOIN empresas e
            ON e.id_empresas = c.id_empresa
        GROUP BY
            c.id_colaboradores,
            c.nome_user,
            vt.salario,
            e.nome_empresas
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlAtivos = "
        SELECT COUNT(DISTINCT c.id_colaboradores)
        FROM colaboradores c
        INNER JOIN vinculo_trabalista vt
            ON c.id_colaboradores = vt.id_trabalista
        INNER JOIN statuss s
            ON c.id_status = s.id_status
        WHERE LOWER(TRIM(COALESCE(s.status_agora, ''))) = 'ativo'
    ";

    $colaboradoresAtivos = (int)$pdo
        ->query($sqlAtivos)
        ->fetchColumn();

    $empresasCadastradas = (int)$pdo
        ->query("SELECT COUNT(*) FROM empresas")
        ->fetchColumn();

    $custoMensalTotal = 0;

    foreach ($colaboradores as $colaborador) {
        $custoMensalTotal += (float)$colaborador["custo_total_colaborador"];
    }

    $resposta = [
        "colaboradores" => $colaboradores,
        "indicadores" => [
            "colaboradores_ativos" => $colaboradoresAtivos,
            "empresas_cadastradas" => $empresasCadastradas,
            "custo_mensal_total" => round($custoMensalTotal, 2),
        ],
    ];

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    http_response_code(500);

    echo json_encode([
        "erro" => "Não foi possível carregar os dados do dashboard."
    ]);
}

?>