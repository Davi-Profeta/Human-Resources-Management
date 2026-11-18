<?php

    require_once "../config/conexao.php";
    header('Content-Type: application/json; charset=utf-8');

    $colaboradores = $pdo->prepare("SELECT *
    FROM colaboradores
    WHERE nome_user LIKE :nome");

    $colaboradores->execute([
        'nome' => ($_GET['colaborador'] ?? '') . '%'
    ]);

    echo json_encode($colaboradores->fetchAll(PDO::FETCH_BOTH));

?>