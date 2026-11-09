<?php

    require_once "../config/conexao.php";
    header('Content-Type: application/json; charset=utf-8');

    $stmt = $pdo->prepare("SELECT id_acesso, nome_user, CPF, email, telefone, endereco
    FROM acessos
    WHERE nome_user LIKE :nome");

    $stmt->execute([
        'nome' => ($_GET['user'] ?? '') . '%',
    ]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

?>