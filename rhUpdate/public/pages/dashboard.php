<!DOCTYPE html>
<html lang="pt-br">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/all.css">

    <title>Dashboard</title>

</head>

<body>

    <div class="container">

        <header>

            <div class="top1">

                <div class="img">
                    <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="7" height="9" x="3" y="3" rx="1" />
                        <rect width="7" height="5" x="14" y="3" rx="1" />
                        <rect width="7" height="9" x="14" y="12" rx="1" />
                        <rect width="7" height="5" x="3" y="16" rx="1" />
                    </svg>
                </div>

                <div class="text">
                    <h3>Dashboard</h3>
                    <p>Informações rápidas e precisas para você</p>
                </div>

            </div>

            <div class="top2">
            </div>

        </header>


        <main>

            <section class="card gastosGerais">

                <div class="cardTopo">
                    <h3>Gastos Gerais</h3>
                    <p>Total gasto por mês</p>
                </div>

                <div class="grafico">
                    <canvas id="graficoGastos"></canvas>
                </div>

            </section>

            <section class="card controlePonto">

                <div class="cardTopo">
                    <h3>Controle de Ponto e Frequência</h3>
                    <p>Situação dos colaboradores hoje</p>
                </div>

                <div class="grafico">
                    <canvas id="graficoPonto"></canvas>
                </div>

            </section>

        </main>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>


</body>

</html>
