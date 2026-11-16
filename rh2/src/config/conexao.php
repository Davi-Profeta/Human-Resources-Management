<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rhmanagement";

try {
    // Cria a conexão PDO com suporte a UTF-8 (evita problemas com acentos)
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Configura o PDO para lançar exceções em caso de erros de SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Define o modo de busca padrão para array associativo
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Cancela a execução e exibe a mensagem se a conexão falhar
    die("Falha na Conexão com o BD: " . $e->getMessage());
}

?>