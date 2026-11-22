<?php

require_once "../../src/config/conexao.php";
session_start();

// só entra quem estiver logado
if (!isset($_SESSION['User'])) {
    header("Location: ../index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <!-- fundo escuro aplicado antes de qualquer arquivo externo: evita o "pisca branco" ao trocar de página no iframe -->
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            background-color: #0f172a;
            color-scheme: dark;
        }

        body {
            display: grid;
            place-items: center;
            color: #f6f8fb;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            text-align: center;
        }

        p {
            margin-top: 6px;
            opacity: 0.7;
        }
    </style>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ponto</title>

</head>

<body>

    <div>
        <h3>Ponto</h3>
        <p>Esta página ainda está em construção.</p>
    </div>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
</body>

</html>
