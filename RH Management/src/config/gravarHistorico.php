<?php

// grava uma alteração em historico_alteracoes, em nome de quem está logado
// $registro = id do registro alterado | $tabela e $campo = o que mudou | $anterior e $novo = valores
function gravarHistorico($pdo, $registro, $tabela, $campo, $anterior, $novo, $idEmpresa = null)
{
    static $idAcesso = false;

    if ($idAcesso === false) {
        $busca = $pdo->prepare("SELECT id_acesso FROM acessos WHERE nome_user = ? LIMIT 1");
        $busca->execute([$_SESSION['User'] ?? '']);
        $idAcesso = $busca->fetchColumn() ?: null;
    }

    $pdo->prepare("INSERT INTO historico_alteracoes
        (id_acessos, id_empresa, id_registro, tabela_alterada, campo_alterado, valor_anterior, valor_novo, data_alteracao)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())")
        ->execute([$idAcesso, $idEmpresa, $registro, $tabela, $campo, mb_substr((string)$anterior, 0, 255), mb_substr((string)$novo, 0, 255)]);
}
