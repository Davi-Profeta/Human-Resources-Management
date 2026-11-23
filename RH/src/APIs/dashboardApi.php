<?php

require_once "../config/conexao.php";

header("Content-Type: application/json; charset=utf-8");

try {
    $sql = "
        SELECT
            c.id_colaboradores,
            c.nome_user,
            COALESCE(vt.salario, 0) AS salario,
            COALESCE(SUM(cb.valor_aplicado), 0) AS total_beneficios_colaborador,
            (
                COALESCE(vt.salario, 0) + COALESCE(SUM(cb.valor_aplicado), 0)
            ) AS custo_total_colaborador,
            e.nome_empresas AS nome_empresa
        FROM colaboradores c
        LEFT JOIN vinculo_trabalista vt
            ON c.id_colaboradores = vt.id_trabalista
        LEFT JOIN colaborador_beneficio cb
            ON c.id_colaboradores = cb.id_colaboradores
            AND cb.id_status = 1
        LEFT JOIN beneficios b
            ON cb.id_beneficios = b.id_beneficios
        LEFT JOIN empresas e
            ON e.id_empresas = c.id_empresa
        GROUP BY
            c.id_colaboradores,
            c.nome_user,
            vt.salario,
            e.nome_empresas
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlAtivos = "
        SELECT COUNT(DISTINCT c.id_colaboradores)
        FROM colaboradores c
        INNER JOIN vinculo_trabalista vt
            ON c.id_colaboradores = vt.id_trabalista
        INNER JOIN statuss s
            ON c.id_status = s.id_status
        WHERE LOWER(TRIM(COALESCE(s.status_agora, ''))) = 'ativo'
    ";

    $colaboradoresAtivos = (int)$pdo
        ->query($sqlAtivos)
        ->fetchColumn();

    $empresasCadastradas = (int)$pdo
        ->query("SELECT COUNT(*) FROM empresas")
        ->fetchColumn();

    $idStatusAtivo = (int)$pdo
        ->query("SELECT id_status FROM statuss WHERE LOWER(TRIM(status_agora)) = 'ativo' LIMIT 1")
        ->fetchColumn();

    $vagasAbertasStmt = $pdo->query("SELECT COUNT(*) AS quantidade, COALESCE(SUM(posicoes), 0) AS posicoes
        FROM vagas
        WHERE status = 'Aberta'");
    $vagasAbertas = $vagasAbertasStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'quantidade' => 0,
        'posicoes' => 0,
    ];

    $candidatosProcesso = (int)$pdo
        ->query("SELECT COUNT(*) FROM candidatos WHERE etapa NOT IN ('Contratado', 'Reprovado')")
        ->fetchColumn();

    $planejamentoStmt = $pdo->prepare("SELECT
            e.nome_empresas AS empresa,
            cg.nome_cargo AS cargo,
            p.quantidade_ideal,
            (
                SELECT COUNT(*)
                FROM colaboradores c2
                INNER JOIN vinculo_trabalista vt2
                    ON vt2.id_trabalista = c2.id_trabalista
                WHERE c2.id_status = ?
                    AND vt2.id_empresa = p.id_empresa
                    AND LOWER(TRIM(vt2.cargo)) = LOWER(TRIM(cg.nome_cargo))
            ) AS efetivo_atual
        FROM planejamento p
        INNER JOIN empresas e ON e.id_empresas = p.id_empresa
        INNER JOIN cargos cg ON cg.id_cargo = p.id_cargo
        ORDER BY e.nome_empresas, cg.nome_cargo");
    $planejamentoStmt->execute([$idStatusAtivo]);

    $deficitPessoal = 0;
    $deficitEmpresas = [];
    foreach ($planejamentoStmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $falta = max(0, (int)$linha['quantidade_ideal'] - (int)$linha['efetivo_atual']);
        if ($falta === 0) {
            continue;
        }

        $deficitPessoal += $falta;
        $deficitEmpresas[] = [
            'empresa' => $linha['empresa'],
            'cargo' => $linha['cargo'],
            'falta' => $falta,
        ];
    }

    $contratosStmt = $pdo->prepare("SELECT
            c.nome_user AS colaborador,
            e.nome_empresas AS empresa,
            vt.cargo,
            vt.data_termino
        FROM colaboradores c
        INNER JOIN vinculo_trabalista vt ON vt.id_trabalista = c.id_trabalista
        LEFT JOIN empresas e ON e.id_empresas = c.id_empresa
        WHERE c.id_status = ?
            AND vt.data_termino IS NOT NULL
            AND vt.data_termino BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        ORDER BY vt.data_termino ASC");
    $contratosStmt->execute([$idStatusAtivo]);
    $contratosVencendo = array_map(function ($linha) {
        return [
            'texto' => $linha['colaborador'] . ' · ' . ($linha['empresa'] ?: 'Sem empresa'),
            'detalhe' => $linha['cargo'] . ' · até ' . date('d/m/Y', strtotime($linha['data_termino'])),
        ];
    }, $contratosStmt->fetchAll(PDO::FETCH_ASSOC));

    $vagasSemCandidatosStmt = $pdo->query("SELECT
            v.id_vaga,
            cg.nome_cargo AS cargo,
            e.nome_empresas AS empresa
        FROM vagas v
        INNER JOIN cargos cg ON cg.id_cargo = v.id_cargo
        INNER JOIN empresas e ON e.id_empresas = v.id_empresa
        LEFT JOIN candidatos ca ON ca.id_vaga = v.id_vaga
        WHERE v.status IN ('Aberta', 'Em análise')
        GROUP BY v.id_vaga, cg.nome_cargo, e.nome_empresas
        HAVING COUNT(ca.id_candidato) = 0
        ORDER BY v.data_abertura ASC");
    $vagasSemCandidatos = array_map(function ($linha) {
        return [
            'texto' => $linha['cargo'] . ' · ' . $linha['empresa'],
            'detalhe' => 'Nenhum candidato cadastrado',
        ];
    }, $vagasSemCandidatosStmt->fetchAll(PDO::FETCH_ASSOC));

    $pendenciasStmt = $pdo->prepare("SELECT
            c.nome_user AS colaborador,
            e.nome_empresas AS empresa,
            CASE
                WHEN c.id_trabalista IS NULL
                    OR NOT EXISTS (
                        SELECT 1 FROM vinculo_trabalista vt
                        WHERE vt.id_trabalista = c.id_trabalista AND vt.id_status = ?
                    ) THEN 'Sem vínculo válido'
                ELSE 'Sem benefício ativo'
            END AS pendencia
        FROM colaboradores c
        LEFT JOIN empresas e ON e.id_empresas = c.id_empresa
        WHERE c.id_status = ?
            AND (
                c.id_trabalista IS NULL
                OR NOT EXISTS (
                    SELECT 1 FROM vinculo_trabalista vt
                    WHERE vt.id_trabalista = c.id_trabalista AND vt.id_status = ?
                )
                OR NOT EXISTS (
                    SELECT 1
                    FROM colaborador_beneficio cb
                    INNER JOIN beneficios b ON b.id_beneficios = cb.id_beneficios
                    WHERE cb.id_colaboradores = c.id_colaboradores
                        AND cb.id_status = ?
                        AND b.id_status = ?
                        AND cb.data_inicio <= CURDATE()
                        AND (cb.data_fim IS NULL OR cb.data_fim >= CURDATE())
                )
            )
        ORDER BY c.nome_user");
    $pendenciasStmt->execute([
        $idStatusAtivo,
        $idStatusAtivo,
        $idStatusAtivo,
        $idStatusAtivo,
        $idStatusAtivo,
    ]);
    $cadastrosPendentes = array_map(function ($linha) {
        return [
            'texto' => $linha['colaborador'] . ' · ' . ($linha['empresa'] ?: 'Sem empresa'),
            'detalhe' => $linha['pendencia'],
        ];
    }, $pendenciasStmt->fetchAll(PDO::FETCH_ASSOC));

    $custoMensalTotal = 0;

    foreach ($colaboradores as $colaborador) {
        $custoMensalTotal += (float)$colaborador["custo_total_colaborador"];
    }

    $resposta = [
        "colaboradores" => $colaboradores,
        "indicadores" => [
            "colaboradores_ativos" => $colaboradoresAtivos,
            "empresas_cadastradas" => $empresasCadastradas,
            "custo_mensal_total" => round($custoMensalTotal, 2),
        ],
        "resumo" => [
            "vagas_abertas" => (int)$vagasAbertas['quantidade'],
            "posicoes_abertas" => (int)$vagasAbertas['posicoes'],
            "candidatos_em_processo" => $candidatosProcesso,
            "deficit_pessoal" => $deficitPessoal,
        ],
        "alertas" => [
            "contratos_vencendo" => $contratosVencendo,
            "vagas_sem_candidatos" => $vagasSemCandidatos,
            "deficit_empresas" => $deficitEmpresas,
            "cadastros_pendentes" => $cadastrosPendentes,
        ],
    ];

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    http_response_code(500);

    echo json_encode([
        "erro" => "Não foi possível carregar os dados do dashboard."
    ]);
}

?>
