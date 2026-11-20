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

        <button type="button" class="perfil-item" id="abrirTema">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 3a9 9 0 1 0 0 18h1.2a2 2 0 0 0 1.4-3.4 1.4 1.4 0 0 1 1-2.4H18a3 3 0 0 0 3-3c0-5.08-4.03-9.2-9-9.2Z" />
                <circle cx="7.5" cy="10" r=".8" fill="currentColor" />
                <circle cx="10" cy="6.8" r=".8" fill="currentColor" />
                <circle cx="14.3" cy="6.8" r=".8" fill="currentColor" />
            </svg>
            Personalizar
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

    <!-- janela de personalização visual -->
    <dialog class="tema-dialog" id="dialogTema">

        <div class="tema-topo">
            <div>
                <span class="tema-kicker">Aparência</span>
                <h3>Personalizar site</h3>
                <p>Escolha as cores que combinam com sua operação.</p>
            </div>
            <button type="button" class="tema-fechar" id="fecharTema" aria-label="Fechar personalização">&times;</button>
        </div>

        <div class="tema-presets" aria-label="Temas prontos">
            <span>Temas rápidos</span>
            <div class="tema-presets-list">
                <button type="button" class="tema-preset" data-tema="azul"><i></i>Azul</button>
                <button type="button" class="tema-preset" data-tema="rubi"><i></i>Rubi</button>
                <button type="button" class="tema-preset" data-tema="violeta"><i></i>Violeta</button>
                <button type="button" class="tema-preset" data-tema="esmeralda"><i></i>Esmeralda</button>
            </div>
        </div>

        <div class="tema-grid">
            <label class="tema-cor">
                <span>Cor primária</span>
                <small>Botões, links e destaques</small>
                <input type="color" id="temaPrimaria" value="#2f5bea">
            </label>
            <label class="tema-cor">
                <span>Cor secundária</span>
                <small>Focos e elementos de apoio</small>
                <input type="color" id="temaSecundaria" value="#14b8a6">
            </label>
            <label class="tema-cor">
                <span>Menu lateral</span>
                <small>Barra de navegação</small>
                <input type="color" id="temaMenu" value="#0f172a">
            </label>
            <label class="tema-cor">
                <span>Fundo</span>
                <small>Área principal das telas</small>
                <input type="color" id="temaFundo" value="#f6f8fb">
            </label>
            <label class="tema-cor">
                <span>Superfície</span>
                <small>Modais e campos claros</small>
                <input type="color" id="temaSuperficie" value="#ffffff">
            </label>
            <label class="tema-cor">
                <span>Cartões</span>
                <small>Blocos e indicadores</small>
                <input type="color" id="temaCartao" value="#162036">
            </label>
        </div>

        <div class="tema-acoes">
            <button type="button" class="tema-resetar" id="resetarTema">Restaurar padrão</button>
            <div>
                <button type="button" class="tema-cancelar" id="cancelarTema">Cancelar</button>
                <button type="button" class="tema-salvar" id="salvarTema">Aplicar cores</button>
            </div>
        </div>

    </dialog>

</div>
