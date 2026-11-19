<?php

    // Menu do usuário logado, que fica no topo da barra lateral.
    // Use dentro de <div class="logo"> em main.php e mainAdm.php (a sessão já deve estar iniciada).

    function p($valor)
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }

    $perfilNome  = $_SESSION['User'] ?? '';
    $perfilEmail = $_SESSION['Email'] ?? '';
    $perfilNivel = (int)($_SESSION['Nivel'] ?? 0);

    $perfilCargo = $perfilNivel === 1 ? 'Administrador do sistema' : 'Gerente de RH';
    $perfilTipo  = $perfilNivel === 1 ? 'Administrador' : 'Gerente RH';

?>

<div class="perfil" id="perfil">

    <!-- quem está logado: clicar abre e fecha o menu -->
    <button type="button" class="perfil-btn" id="perfilBtn" aria-expanded="false" aria-controls="perfilMenu" title="<?= p($perfilNome) ?>">

        <span class="icone">
            <span class="avatar-perfil" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </span>
        </span>

        <span class="perfil-dados texto">
            <strong><?= p($perfilNome) ?></strong>
            <small><?= p($perfilCargo) ?></small>
        </span>

        <!-- Lucide: chevron-down (gira quando o menu abre) -->
        <svg class="perfil-seta texto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>

    </button>

    <!-- opções do usuário -->
    <div class="perfil-menu" id="perfilMenu" hidden>

        <button type="button" class="perfil-item" id="abrirConta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            Minha conta
        </button>

        <hr>

        <a class="perfil-item sair" href="logout.php">
            <!-- Lucide: log-out -->
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="m16 17 5-5-5-5" />
                <path d="M21 12H9" />
            </svg>
            Sair do sistema
        </a>

    </div>

    <!-- janela "Minha conta" -->
    <dialog class="conta" id="dialogConta">

        <div class="conta-topo">
            <h3>Minha conta</h3>
        </div>

        <dl class="conta-info">
            <div>
                <dt>Usuário</dt>
                <dd><?= p($perfilNome) ?></dd>
            </div>
            <div>
                <dt>Nível de acesso</dt>
                <dd><?= p($perfilTipo) ?></dd>
            </div>
            <?php if ($perfilEmail !== ''): ?>
                <div>
                    <dt>E-mail</dt>
                    <dd><?= p($perfilEmail) ?></dd>
                </div>
            <?php endif; ?>
        </dl>

        <div class="conta-botoes">
            <button type="button" id="fecharConta">Fechar</button>
        </div>

    </dialog>

</div>