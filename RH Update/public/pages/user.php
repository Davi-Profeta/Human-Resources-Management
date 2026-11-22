<?php
    require_once "../../src/config/conexao.php";
    require_once "../../src/config/gravarHistorico.php";
    session_start();
    if (empty($_SESSION['User']) || $_SESSION['Nivel'] != 1) {
        header("Location: index.php");
        exit;
    }
    $Erro = "";
    $ErroEditar = "";
    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $Acao = $_POST['acao'];
        if ($Acao === 'cadastrar') {
            $Name = $_POST['name'];
            $CPF = $_POST['CPF'];
            $Email = $_POST['email'];
            $Tel = $_POST['tel'];
            $Local = $_POST['local'];
            $Nivel = $_POST['nivel'];
            $Pass1 = $_POST['password1'];
            $Pass2 = $_POST['password2'];
            $idNovo = null;
            $add = ("INSERT INTO acessos(nome_user, CPF, email, telefone, endereco, senha, nivel)
                VALUES (?,?,?,?,?,?,?)");
            try {
                $stmt = $pdo->prepare($add);
                $stmt->execute([$Name, $CPF, $Email, $Tel, $Local, $Pass1, $Nivel]);
                $idNovo = $pdo->lastInsertId();
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    $consulta = $pdo->prepare("SELECT 1 FROM acessos WHERE CPF = ? LIMIT 1");
                    $consulta->execute([$CPF]);
                    $Erro = $consulta->fetchColumn()
                        ? "Este CPF já está cadastrado no sistema!"
                        : "Já existe um usuário com um dos dados informados.";
                } else {
                    $Erro = "Ocorreu um erro ao cadastrar o usuário.";
                }
            }
            if ($idNovo !== null) {
                gravarHistorico($pdo, $idNovo, 'acessos', 'cadastro', '', $Name);
            }
        };
        if ($Acao === "editar") {
            $Id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $dados = [
                'nome_user' => trim($_POST['name'] ?? ''),
                'CPF' => trim($_POST['CPF'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefone' => trim($_POST['tel'] ?? ''),
                'endereco' => trim($_POST['local'] ?? ''),
                'nivel' => $_POST['nivel'] ?? ''
            ];
            $Pass1 = $_POST['password1'] ?? '';
            $Pass2 = $_POST['password2'] ?? '';

            if (!$Id || $Id < 1 || in_array('', $dados, true) || !in_array($dados['nivel'], ['1', '2'], true)) {
                $ErroEditar = "Confira os dados informados.";
            } elseif ($Pass1 !== $Pass2) {
                $ErroEditar = "As senhas não coincidem.";
            } else {
                try {
                    $pdo->beginTransaction();
                    $consulta = $pdo->prepare("SELECT * FROM acessos WHERE id_acesso = ? FOR UPDATE");
                    $consulta->execute([$Id]);
                    $anterior = $consulta->fetch(PDO::FETCH_ASSOC);
                    if (!$anterior) {
                        $pdo->rollBack();
                        $ErroEditar = "Usuário não encontrado.";
                    } else {
                        // A senha só é alterada quando preenchida.
                        $sql = "UPDATE acessos SET nome_user = ?, CPF = ?, email = ?, telefone = ?, endereco = ?, nivel = ?";
                        $params = array_values($dados);
                        if ($Pass1 !== '') {
                            $sql .= ", senha = ?";
                            $params[] = $Pass1;
                        }
                        $sql .= " WHERE id_acesso = ?";
                        $params[] = $Id;
                        $pdo->prepare($sql)->execute($params);

                        // Registra apenas os campos que realmente mudaram.
                        foreach ($dados as $campo => $novo) {
                            if ((string)$anterior[$campo] !== (string)$novo) {
                                gravarHistorico($pdo, $Id, 'acessos', $campo, $anterior[$campo], $novo);
                            }
                        }
                        if ($Pass1 !== '' && $Pass1 !== (string)$anterior['senha']) {
                            gravarHistorico($pdo, $Id, 'acessos', 'senha', '', 'Senha alterada');
                        }
                        $pdo->commit();
                        header("Location: user.php");
                        exit;
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $ErroEditar = ($e instanceof PDOException && (int)($e->errorInfo[1] ?? 0) === 1062)
                        ? "Já existe outro usuário com um dos dados informados."
                        : "Ocorreu um erro ao editar o usuário.";
                }
            }
        }
        if ($Acao === "apagar") {
            $Id = $_POST['id'];
            $deletar = ("DELETE FROM acessos
                WHERE id_acesso = ?");
            $nome = $pdo->prepare("SELECT nome_user FROM acessos WHERE id_acesso = ?");
            $nome->execute([$Id]);
            $nome = $nome->fetchColumn();

            // solta o usuário do histórico e dos desligamentos para ele poder ser apagado
            $pdo->prepare("UPDATE historico_alteracoes SET id_acessos = NULL WHERE id_acessos = ?")->execute([$Id]);
            $pdo->prepare("UPDATE desligamento SET id_acessos = NULL WHERE id_acessos = ?")->execute([$Id]);

            $stmtA = $pdo->prepare($deletar);
            $stmtA->execute([$Id]);

            gravarHistorico($pdo, $Id, 'acessos', 'exclusao', $nome, '');
        };
    };
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <style>html, body { background-color: #0f172a; }</style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link rel="stylesheet" href="../assets/css/all.css">
    <title>Usuários</title>
</head>
<body>
    <div class="container">
        <header>
            <div class="top1">
                <div class="img">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <div class="text">
                    <h3>Usuários</h3>
                    <p>Gerencie os usuários do sistema</p>
                </div>
            </div>
            <div class="top2">
                <button type="button" id="new">Novo usuário</button>
            </div>
        </header>
        <section>
            <input placeholder="Pesquise por nome" type="text" name="" id="ipt">
        </section>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="corpoTabela">
                    <?php
                    $users = ("SELECT *
                    FROM acessos ");
                    $stmt = $pdo->prepare($users);
                    $stmt->execute();
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($usuarios as $resultado) {
                        $nome     = htmlspecialchars($resultado['nome_user']);
                        $cpf      = htmlspecialchars($resultado['CPF']);
                        $email    = htmlspecialchars($resultado['email']);
                        $telefone = htmlspecialchars($resultado['telefone']);
                        $endereco = htmlspecialchars($resultado['endereco']);
                        echo "
                            <tr>
                                <td>{$nome}</td>
                                <td>{$cpf}</td>
                                <td>{$email}</td>
                                <td>{$telefone}</td>
                                <td>{$endereco}</td>
                                <td>
                                    <div class=\"acoes\">
                                        <button type='button' class='btn-acao btn-editar' data-id='{$resultado['id_acesso']}' data-nivel='" . (int)$resultado['nivel'] . "' title='Editar usuário'>
                                            <svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.75\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">
                                                <path d=\"M12 20h9\" />
                                                <path d=\"M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z\" />
                                            </svg>
                                        </button>
                                        <button type='button' class='btn-acao btn-excluir'
                                                data-id='{$resultado['id_acesso']}'
                                                title='Apagar usuário' aria-label='Apagar usuário'>
                                            <svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>
                                                <path d='M3 6h18' />
                                                <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6' />
                                                <path d='M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' />
                                                <line x1='10' y1='11' x2='10' y2='17' />
                                                <line x1='14' y1='11' x2='14' y2='17' />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ";
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>
    <dialog class="createUser">
        <div class="topModal">
            <h3>Cadastrar Usuário</h3>
        </div>
        <div class="mainModal">
            <form action="user.php" method="post">
                <div class="ipt">
                    <div class="separation">
                        <label for="">Nome Completo</label>
                        <input required type="text" name="name" id="" value="<?= htmlspecialchars($Erro !== '' ? ($_POST['name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="separation">
                        <label for="">CPF</label>
                        <input required maxlength="11" type="text" inputmode="numeric" name="CPF" id="" value="<?= htmlspecialchars($Erro !== '' ? ($_POST['CPF'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="ipt">
                    <div class="separation">
                        <label for="">Email</label>
                        <input required type="text" name="email" id="" value="<?= htmlspecialchars($Erro !== '' ? ($_POST['email'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="separation">
                        <label for="">Telefone</label>
                        <input required maxlength="13" type="tel" name="tel" id="" value="<?= htmlspecialchars($Erro !== '' ? ($_POST['tel'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="ipt">
                    <div class="separation">
                        <label for="">Endereço</label>
                        <input required type="text" name="local" id="" value="<?= htmlspecialchars($Erro !== '' ? ($_POST['local'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="separation">
                        <label for="">Nivel de Usuário</label>
                        <select name="nivel" id="">
                            <option value="1" <?= $Erro !== '' && ($_POST['nivel'] ?? '') === '1' ? 'selected' : '' ?>> Administrador </option>
                            <option value="2" <?= $Erro !== '' && ($_POST['nivel'] ?? '') === '2' ? 'selected' : '' ?>> Gerente RH </option>
                        </select>
                    </div>
                </div>
                <div class="ipt">
                    <div class="separation">
                        <label for="">Senha</label>
                        <input required type="password" name="password1" id="">
                    </div>
                    <div class="separation">
                        <label for="">Confirmar Senha</label>
                        <input required type="password" name="password2" id="">
                    </div>
                </div>
                <div class="ipt">
                    <p class='erro' id="erro"><?= htmlspecialchars($Erro, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="btns">
                        <button type="submit">Confirmar</button>
                        <button type="button" id="cancel">Cancelar</button>
                    </div>
                </div>
                <!-- input para ação no PHP -->
                <input type="hidden" name="acao" value="cadastrar">
            </form>
        </div>
    </dialog>
    <dialog class="delete">
        <form action="user.php" method="post">
            <h3>Apagar usuário</h3>
            <p>Tem certeza que deseja apagar este usuário?</p>
            <input type="hidden" name="id" id="idExcluir">
            <input type="hidden" name="acao" value="apagar">
            <div class="dialog-botoes">
                <button type="button" id="cancelarExcluir">Cancelar</button>
                <button type="submit" class="excluir">Apagar</button>
            </div>
        </form>
    </dialog>
    <dialog class="editUser">
        <div class="topModal">
            <h3>Editar Dados</h3>
        </div>
        <div class="mainModal">
            <form action="user.php" method="post">
                <div class="ipt">
                    <div class="separation">
                        <label for="">Nome Completo</label>
                        <input required type="text" name="name" id="" value="<?= htmlspecialchars($ErroEditar !== '' ? ($_POST['name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="separation">
                        <label for="">CPF</label>
                        <input required maxlength="11" type="text" name="CPF" id="" value="<?= htmlspecialchars($ErroEditar !== '' ? ($_POST['CPF'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="ipt">
                    <div class="separation">
                        <label for="">Email</label>
                        <input required type="text" name="email" id="" value="<?= htmlspecialchars($ErroEditar !== '' ? ($_POST['email'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="separation">
                        <label for="">Telefone</label>
                        <input required maxlength="13" type="tel" name="tel" id="" value="<?= htmlspecialchars($ErroEditar !== '' ? ($_POST['tel'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="ipt">
                    <div class="separation">
                        <label for="">Endereço</label>
                        <input required type="text" name="local" id="" value="<?= htmlspecialchars($ErroEditar !== '' ? ($_POST['local'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="separation">
                        <label for="">Nivel de Usuário</label>
                        <select required name="nivel">
                            <option value="">Selecione o nível</option>
                            <option value="1" <?= $ErroEditar !== '' && ($_POST['nivel'] ?? '') === '1' ? 'selected' : '' ?>> Administrador </option>
                            <option value="2" <?= $ErroEditar !== '' && ($_POST['nivel'] ?? '') === '2' ? 'selected' : '' ?>> Gerente RH </option>
                        </select>
                    </div>
                </div>
                <div class="ipt">
                    <div class="separation">
                        <label for="">Senha (opcional)</label>
                        <input type="password" name="password1" id="">
                    </div>
                    <div class="separation">
                        <label for="">Confirmar Senha (opcional)</label>
                        <input type="password" name="password2" id="">
                    </div>
                </div>
                <div class="ipt">
                    <p class='erro' id="erroEditar"><?= htmlspecialchars($ErroEditar, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="btns">
                        <button type="submit">Confirmar</button>
                        <button type="button" id="cancelarEditar">Cancelar</button>
                    </div>
                </div>
                <!-- input para ação no PHP -->
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="idEditar" value="<?= htmlspecialchars($ErroEditar !== '' ? ($_POST['id'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
            </form>
        </div>
    </dialog>
</body>
    <script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../assets/js/user.js?v=<?= filemtime(__DIR__ . '/../assets/js/user.js') ?>"></script>
<?php if ($Erro !== ""): ?>
<script>
    document.querySelector(".createUser").showModal();
</script>
<?php endif; ?>
<?php if ($ErroEditar !== ""): ?>
<script>
    document.querySelector(".editUser").showModal();
</script>
<?php endif; ?>
</html>
