<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Fundo aplicado antes dos estilos externos: evita tela branca no iframe. -->
    <style>html, body { background-color: #0f172a; }</style>

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


        </header>


        <main>

            <section class="indicadoresKpi" aria-label="Indicadores principais">

                <article class="indicadorCard">
                    <span>Colaboradores ativos</span>
                    <strong id="kpiColaboradoresAtivos">—</strong>
                    <small>Com vínculo ativo</small>
                </article>

                <article class="indicadorCard">
                    <span>Empresas cadastradas</span>
                    <strong id="kpiEmpresasCadastradas">—</strong>
                    <small>Total de empresas no sistema</small>
                </article>

                <article class="indicadorCard">
                    <span>Custo mensal total</span>
                    <strong id="kpiCustoMensal">—</strong>
                    <small>Salários + benefícios</small>
                </article>

            </section>

            <section class="card gastosGerais">

                <div class="cardTopo">
                    <h3>Gastos Gerais</h3>
                    <p>Distribuição entre salários e benefícios</p>
                </div>

                <div class="grafico">
                    <canvas id="graficoGastos"></canvas>
                </div>

            </section>

            <section class="card colaboradoresEmpresa">

                <div class="cardTopo">
                    <h3>Colaboradores por empresa</h3>
                    <p>Quantidade de colaboradores vinculados a cada empresa</p>
                </div>

                <div class="grafico">
                    <canvas id="graficoColaboradoresEmpresa"></canvas>
                    <p id="avisoGraficoEmpresa" class="mensagemGrafico" hidden>
                        A API precisa retornar o nome da empresa de cada colaborador.
                    </p>
                </div>

            </section>

        </main>


    </div>

    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../assets/js/dashboard.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboard.js') ?>"></script>


</body>

</html>
