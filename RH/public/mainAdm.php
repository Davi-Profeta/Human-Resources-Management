<?php
require_once "../src/config/conexao.php";
session_start();

if (empty($_SESSION['User']) || $_SESSION['Nivel'] != 1) {
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="stylesheet" href="assets/css/perfil.css">
    <title>Human Resources</title>

</head>

<body>

    <main>

        <section>

            <div class="borderS">

                <div class="logo">
                    <?php include __DIR__ . "/../src/partials/perfil.php"; ?>
                </div>

                <a href="pages/dashboard.php" target="iframe" title="Dashboard">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: layout-dashboard -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="7" height="9" x="3" y="3" rx="1" />
                                <rect width="7" height="5" x="14" y="3" rx="1" />
                                <rect width="7" height="9" x="14" y="12" rx="1" />
                                <rect width="7" height="5" x="3" y="16" rx="1" />
                            </svg>
                        </span>

                        <span class="texto">Dashboard</span>
                    </div>
                </a>

                <a href="pages/colaboradores.php" target="iframe" title="Colaboradores">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: users -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                        </span>

                        <span class="texto">Colaboradores</span>
                    </div>
                </a>

                <a href="pages/vinculos.php" target="iframe" title="Vínculos">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: briefcase -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                                <rect width="20" height="14" x="2" y="6" rx="2" />
                            </svg>
                        </span>

                        <span class="texto">Vínculos</span>
                    </div>
                </a>

                <a href="pages/contratos.php" target="iframe" title="Contratos">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: file-text -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                <path d="M10 9H8" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                            </svg>
                        </span>

                        <span class="texto">Contratos</span>
                    </div>
                </a>

                <a href="pages/planejamento.php" target="iframe" title="Planejamento de Pessoal">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: chart-column -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                                <path d="M18 17V9" />
                                <path d="M13 17V5" />
                                <path d="M8 17v-3" />
                            </svg>
                        </span>

                        <span class="texto">Planejamento</span>
                    </div>
                </a>

                <a href="pages/vagas.php" target="iframe" title="Vagas e Recrutamento">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: briefcase -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="20" height="14" x="2" y="7" rx="2" ry="2" />
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                            </svg>
                        </span>

                        <span class="texto">Vagas</span>
                    </div>
                </a>

                <a href="pages/ponto.php" target="iframe" title="Ponto">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: clock -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                        </span>

                        <span class="texto">Ponto</span>
                    </div>
                </a>

                <a href="pages/beneficios.php" target="iframe" title="Benefícios">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: gift -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 7v14" />
                                <path d="M20 11v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8" />
                                <path d="M7.5 7a1 1 0 0 1 0-5A4.8 8 0 0 1 12 7a4.8 8 0 0 1 4.5-5 1 1 0 0 1 0 5" />
                                <rect x="3" y="7" width="18" height="4" rx="1" />
                            </svg>
                        </span>

                        <span class="texto">Benefícios</span>
                    </div>
                </a>

                <a href="pages/desligamentos.php" target="iframe" title="Desligamentos">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: user-minus -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <line x1="22" x2="16" y1="11" y2="11" />
                            </svg>
                        </span>

                        <span class="texto">Desligamentos</span>
                    </div>
                </a>

                <a href="pages/empresas.php" target="iframe" title="Empresas">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: building-2 -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 12h4" />
                                <path d="M10 8h4" />
                                <path d="M14 21v-3a2 2 0 0 0-4 0v3" />
                                <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2" />
                                <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16" />
                            </svg>
                        </span>

                        <span class="texto">Empresas</span>
                    </div>
                </a>

                <a href="pages/user.php" target="iframe" title="Usuários">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: shield-user -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                <path d="M6.376 18.91a6 6 0 0 1 11.249.003" />
                                <circle cx="12" cy="11" r="4" />
                            </svg>
                        </span>

                        <span class="texto">Usuários</span>
                    </div>
                </a>

                <a href="pages/historico.php" target="iframe" title="Histórico">
                    <div class="page">
                        <span class="icone">
                            <!-- Lucide: history -->
                            <svg class="icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                <path d="M3 3v5h5" />
                                <path d="M12 7v5l4 2" />
                            </svg>
                        </span>

                        <span class="texto">Histórico</span>
                    </div>
                </a>

            </div>

        </section>

        <div class="container is-loading">

            <iframe id="iframe" name="iframe" src="pages/dashboard.php" frameborder="0"></iframe>

        </div>

    </main>
    <script>
        // Mantém o fundo escuro visível enquanto cada aba carrega.
        (() => {
            const frame = document.getElementById('iframe');
            const container = frame.closest('.container');
            document.querySelectorAll('a[target="iframe"]').forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    if (frame.contentWindow.location.href === link.href) return;
                    container.classList.add('is-loading');
                    // espera a página atual sumir antes de carregar a próxima
                    setTimeout(() => frame.contentWindow.location.replace(link.href), 160);
                });
            });
            frame.addEventListener('load', () => {
                container.classList.remove('is-loading');
            });
        })();
    </script>
    <script src="assets/js/tema.js?v=<?= filemtime(__DIR__ . '/assets/js/tema.js') ?>"></script>
    <script src="assets/js/perfil.js?v=<?= filemtime(__DIR__ . '/assets/js/perfil.js') ?>"></script>
</body>

</html>
