<?php

    require_once "../config/conexao.php";
    header('Content-Type: application/json; charset=utf-8');

    $stmt = $pdo->prepare("SELECT 
    c.id_colaboradores,
    c.nome_user,
    COALESCE(vt.salario, 0) AS salario,
    COALESCE(SUM(b.valor), 0) AS total_beneficios_colaborador,
    (COALESCE(vt.salario, 0) + COALESCE(SUM(b.valor), 0)) AS custo_total_colaborador
    FROM colaboradores c
    LEFT JOIN vinculo_trabalista vt ON c.id_colaboradores = vt.id_trabalista -- ou c.id_trabalista = vt.id_trabalista
    LEFT JOIN colaborador_beneficio cb ON c.id_colaboradores = cb.id_colaboradores AND cb.id_status = 1
    LEFT JOIN beneficios b ON cb.id_beneficios = b.id_beneficios
    GROUP BY c.id_colaboradores, c.nome_user, vt.salario;");
    $stmt->execute();
    $valores = $stmt->fetchAll(PDO::FETCH_BOTH);

    echo json_encode($valores);

?>