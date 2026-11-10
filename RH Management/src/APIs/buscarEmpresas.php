<?php

    require_once "../config/conexao.php";

    $empresas = $pdo->prepare("SELECT *
    FROM empresas");
    $empresas->execute();

    $stmt = $empresas->fetch(PDO::FETCH_BOTH);

    foreach($stmt as $i){

        

    };


?>