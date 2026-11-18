<?php

    require_once "../config/conexao.php";
    header('Content-Type: application/json; charset=utf-8');

    $id = $_GET['id'] ?? null;

    if (!$id) {
        echo json_encode(['erro' => 'ID não informado']);
        exit;
    }

    $detalhes = $pdo->prepare("SELECT * FROM colaboradores WHERE id = :id");
    $detalhes->execute(['id' => $id]);

    
    echo json_encode($detalhes->fetch(PDO::FETCH_ASSOC));

?>