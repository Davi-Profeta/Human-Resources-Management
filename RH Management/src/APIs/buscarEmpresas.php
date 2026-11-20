<?php

    require_once "../config/conexao.php";
     header('Content-Type: application/json; charset=utf-8');

    $empresas = $pdo->prepare("SELECT *
    FROM empresas
    WHERE nome_empresas LIKE :nome");

    $empresas->execute([
        'nome' => ($_GET['empresa'] ?? '') . "%"
    ]);

    echo json_encode($empresas->fetchAll(PDO::FETCH_ASSOC));

?>