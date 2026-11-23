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
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/dashboard.css') ?>">
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

                <article class="indicadorCard">
                    <span>Vagas abertas</span>
                    <strong id="kpiVagasAbertas">—</strong>
                    <small id="kpiVagasAbertasMeta">Posições disponíveis</small>
                </article>

                <article class="indicadorCard">
                    <span>Candidatos em processo</span>
                    <strong id="kpiCandidatosProcesso">—</strong>
                    <small>Triagem, entrevista ou proposta</small>
                </article>

                <article class="indicadorCard">
                    <span>Déficit de pessoal</span>
                    <strong id="kpiDeficitPessoal">—</strong>
                    <small>Posições abaixo do planejado</small>
                </article>

            </section>

            <section class="card alertasDashboard" aria-labelledby="tituloAlertas">

                <div class="cardTopo">
                    <h3 id="tituloAlertas">Alertas de RH</h3>
                    <p>Pontos que precisam de acompanhamento</p>
                </div>

                <div class="alertasGrid">

                    <article class="alertaItem">
                        <div class="alertaCabecalho">
                            <span class="alertaIcone alertaAmarelo" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8 2v4M16 2v4" />
                                    <rect width="18" height="18" x="3" y="4" rx="2" />
                                    <path d="M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
                                </svg>
                            </span>
                            <div>
                                <h4>Contratos próximos do vencimento</h4>
                                <p>Vencem nos próximos 60 dias</p>
                            </div>
                            <strong id="qtdContratosVencendo">—</strong>
                        </div>
                        <ul id="listaContratosVencendo" class="listaAlerta"></ul>
                    </article>

                    <article class="alertaItem">
                        <div class="alertaCabecalho">
                            <span class="alertaIcone alertaAzul" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="14" x="2" y="7" rx="2" />
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                                </svg>
                            </span>
                            <div>
                                <h4>Vagas sem candidatos</h4>
                                <p>Vagas abertas sem movimentação</p>
                            </div>
                            <strong id="qtdVagasSemCandidatos">—</strong>
                        </div>
                        <ul id="listaVagasSemCandidatos" class="listaAlerta"></ul>
                    </article>

                    <article class="alertaItem">
                        <div class="alertaCabecalho">
                            <span class="alertaIcone alertaVermelho" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <div>
                                <h4>Empresas com déficit</h4>
                                <p>Cargos abaixo do quadro ideal</p>
                            </div>
                            <strong id="qtdDeficitEmpresas">—</strong>
                        </div>
                        <ul id="listaDeficitEmpresas" class="listaAlerta"></ul>
                    </article>

                    <article class="alertaItem">
                        <div class="alertaCabecalho">
                            <span class="alertaIcone alertaRoxo" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M4 21a8 8 0 0 1 16 0M19 8v6M22 11h-6" />
                                </svg>
                            </span>
                            <div>
                                <h4>Cadastros pendentes</h4>
                                <p>Sem benefício ou vínculo válido</p>
                            </div>
                            <strong id="qtdCadastrosPendentes">—</strong>
                        </div>
                        <ul id="listaCadastrosPendentes" class="listaAlerta"></ul>
                    </article>

                </div>

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
