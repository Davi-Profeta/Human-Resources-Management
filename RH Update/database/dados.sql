USE rhmanagement;

-- ==========================================
-- DADOS DE EXEMPLO (opcional - apenas para testes)
-- Execute depois do BD.sql e do inserts.sql, em um banco novo,
-- pois os ids abaixo assumem tabelas vazias.
-- id_status: 1 = Ativo | 2 = Inativo
-- id_vinculo: 1 = CLT | 2 = PJ | 3 = Estágio | 4 = Temporário | 5 = Autônomo
-- ==========================================

-- ==========================================
-- INSERÇÃO DAS EMPRESAS
-- ==========================================

INSERT INTO empresas (nome_empresas, CNPJ, email, telefone, endereco, razao_social, id_stats)
VALUES
    ('TechNova',          '11111111000111', 'contato@technova.com',    '3133334444', 'Av. Afonso Pena, 1000', 'TechNova Soluções em Tecnologia LTDA', 1),
    ('Construtora Alfa',  '22222222000122', 'contato@alfa.com',        '3133335555', 'Rua das Flores, 250',   'Construtora Alfa S.A.',                1),
    ('Mercado Bom Preço', '33333333000133', 'contato@bompreco.com',    '3133336666', 'Rua do Comércio, 80',   'Mercado Bom Preço LTDA',               1),
    ('Clínica Vida',      '44444444000144', 'contato@clinicavida.com', '3133337777', 'Av. Brasil, 500',       'Clínica Vida Saúde LTDA',              1),
    ('Logística Rápida',  '55555555000155', 'contato@lograpida.com',   '3133338888', 'Rod. BR-040, km 12',    'Logística Rápida Transportes LTDA',    1),
    ('Escola Aprender',   '66666666000166', 'contato@aprender.com',    '3133339999', 'Rua da Escola, 45',     'Escola Aprender Educação LTDA',        2);

-- ==========================================
-- INSERÇÃO DOS BENEFÍCIOS ESPECÍFICOS DE EMPRESA
-- ==========================================

INSERT INTO beneficios (id_status, id_empresa, nome_beneficios, descricao, tipo, valor, desconto)
VALUES
    (1, 1, 'Auxílio home office', 'Ajuda de custo para trabalho remoto',    'Outro',      150.00,  0.00),
    (1, 2, 'Cesta básica',        'Cesta básica mensal para a equipe',      'Alimentação', 280.00,  0.00),
    (1, 5, 'Auxílio combustível', 'Reembolso de combustível para motoristas','Transporte', 400.00,  0.00);

-- ==========================================
-- INSERÇÃO DOS CARGOS
-- ==========================================

INSERT INTO cargos (nome_cargo)
VALUES
    ('Desenvolvedor'),
    ('Analista de Suporte'),
    ('Designer'),
    ('Gerente de Projetos'),
    ('Engenheiro Civil'),
    ('Mestre de Obras'),
    ('Operador de Caixa'),
    ('Repositor'),
    ('Recepcionista'),
    ('Enfermeiro'),
    ('Motorista'),
    ('Auxiliar de Logística'),
    ('Professor'),
    ('Analista de RH');

-- ==========================================
-- INSERÇÃO DOS VÍNCULOS TRABALHISTAS
-- ==========================================

INSERT INTO vinculo_trabalista (id_empresa, id_status, cargo, data_inicio, data_termino, salario)
VALUES
    (1, 1, 'Desenvolvedor',          '2024-02-01', NULL,         6500.00),
    (1, 1, 'Desenvolvedor',          '2024-09-10', NULL,         5800.00),
    (1, 1, 'Analista de Suporte',    '2024-05-15', NULL,         3800.00),
    (1, 1, 'Designer',               '2025-01-10', NULL,         4200.00),
    (1, 1, 'Gerente de Projetos',    '2023-08-01', NULL,         9000.00),
    (2, 1, 'Engenheiro Civil',       '2023-03-01', NULL,         8500.00),
    (2, 1, 'Mestre de Obras',        '2024-01-20', NULL,         4500.00),
    (2, 1, 'Analista de RH',         '2024-07-01', NULL,         4800.00),
    (3, 1, 'Operador de Caixa',      '2025-03-01', NULL,         1800.00),
    (3, 1, 'Operador de Caixa',      '2025-04-14', NULL,         1800.00),
    (3, 1, 'Repositor',              '2025-02-03', NULL,         1650.00),
    (3, 2, 'Repositor',              '2025-08-18', '2026-02-28', 1650.00),
    (4, 1, 'Recepcionista',          '2025-06-01', NULL,         2200.00),
    (4, 1, 'Enfermeiro',             '2024-04-08', NULL,         5200.00),
    (4, 1, 'Enfermeiro',             '2024-11-04', NULL,         5200.00),
    (5, 1, 'Motorista',              '2024-03-11', NULL,         3600.00),
    (5, 1, 'Motorista',              '2025-01-27', NULL,         3600.00),
    (5, 1, 'Auxiliar de Logística',  '2025-05-05', NULL,         2100.00),
    (6, 2, 'Professor',              '2023-02-06', '2025-12-19', 4100.00),
    (6, 2, 'Auxiliar de Logística',  '2024-08-12', '2025-10-31', 1900.00);

-- ==========================================
-- INSERÇÃO DOS COLABORADORES
-- ==========================================

INSERT INTO colaboradores (id_vinculo, id_trabalista, id_empresa, id_status, id_beneficios, nome_user, CPF, email, `endereço`, telefone)
VALUES
    (1, 1,  1, 1, 1, 'Carlos Silva',      '10000000001', 'carlos.silva@technova.com',    'Rua das Acácias, 10',   '31990000001'),
    (2, 2,  1, 1, 6, 'Fernanda Lima',     '10000000002', 'fernanda.lima@technova.com',   'Rua dos Ipês, 22',      '31990000002'),
    (1, 3,  1, 1, 2, 'Rafael Costa',      '10000000003', 'rafael.costa@technova.com',    'Av. Contorno, 305',     '31990000003'),
    (1, 4,  1, 1, 1, 'Juliana Rocha',     '10000000004', 'juliana.rocha@technova.com',   'Rua Bahia, 87',         '31990000004'),
    (1, 5,  1, 1, 3, 'Ricardo Menezes',   '10000000005', 'ricardo.menezes@technova.com', 'Rua Goiás, 140',        '31990000005'),
    (1, 6,  2, 1, 3, 'Marcos Pereira',    '10000000006', 'marcos.pereira@alfa.com',      'Rua Sergipe, 66',       '31990000006'),
    (1, 7,  2, 1, 2, 'Paulo Santos',      '10000000007', 'paulo.santos@alfa.com',        'Rua Timbiras, 12',      '31990000007'),
    (1, 8,  2, 1, 1, 'Camila Duarte',     '10000000008', 'camila.duarte@alfa.com',       'Rua Paraná, 410',       '31990000008'),
    (1, 9,  3, 1, 2, 'Ana Souza',         '10000000009', 'ana.souza@bompreco.com',       'Rua Mato Grosso, 9',    '31990000009'),
    (1, 10, 3, 1, 2, 'Bruno Carvalho',    '10000000010', 'bruno.carvalho@bompreco.com',  'Rua Piauí, 73',         '31990000010'),
    (1, 11, 3, 1, 1, 'Larissa Martins',   '10000000011', 'larissa.martins@bompreco.com', 'Rua Ceará, 58',         '31990000011'),
    (4, 12, 3, 2, 2, 'Thiago Ribeiro',    '10000000012', 'thiago.ribeiro@bompreco.com',  'Rua Alagoas, 301',      '31990000012'),
    (1, 13, 4, 1, 4, 'Beatriz Almeida',   '10000000013', 'beatriz.almeida@clinicavida.com','Rua Pernambuco, 190', '31990000013'),
    (1, 14, 4, 1, 3, 'Patrícia Gomes',    '10000000014', 'patricia.gomes@clinicavida.com','Av. do Contorno, 2500','31990000014'),
    (1, 15, 4, 1, 5, 'Diego Fernandes',   '10000000015', 'diego.fernandes@clinicavida.com','Rua Espírito Santo, 33','31990000015'),
    (1, 16, 5, 1, 1, 'André Nascimento',  '10000000016', 'andre.nascimento@lograpida.com','Rua Amazonas, 604',    '31990000016'),
    (2, 17, 5, 1, 2, 'Eduardo Barbosa',   '10000000017', 'eduardo.barbosa@lograpida.com','Rua Rio Branco, 28',    '31990000017'),
    (1, 18, 5, 1, 2, 'Vanessa Teixeira',  '10000000018', 'vanessa.teixeira@lograpida.com','Rua Curitiba, 415',    '31990000018'),
    (1, 19, 6, 2, 8, 'Helena Cardoso',    '10000000019', 'helena.cardoso@aprender.com',  'Rua Tupis, 77',         '31990000019'),
    (1, 20, 6, 2, 2, 'Gustavo Araújo',    '10000000020', 'gustavo.araujo@aprender.com',  'Rua Tamoios, 120',      '31990000020');

-- ==========================================
-- INSERÇÃO DOS BENEFÍCIOS POR COLABORADOR
-- ==========================================

INSERT INTO colaborador_beneficio (id_colaboradores, id_beneficios, id_status, data_inicio, data_fim)
VALUES
    (1,  1, 1, '2024-02-01', NULL),
    (1,  3, 1, '2024-02-01', NULL),
    (1,  9, 1, '2024-03-01', NULL),
    (2,  6, 1, '2024-09-10', NULL),
    (2,  2, 1, '2024-09-10', NULL),
    (3,  2, 1, '2024-05-15', NULL),
    (3,  1, 1, '2024-05-15', NULL),
    (4,  1, 1, '2025-01-10', NULL),
    (4,  4, 1, '2025-01-10', NULL),
    (5,  3, 1, '2023-08-01', NULL),
    (5,  5, 1, '2023-08-01', NULL),
    (5,  6, 1, '2023-09-01', NULL),
    (6,  3, 1, '2023-03-01', NULL),
    (6,  5, 1, '2023-03-01', NULL),
    (7,  2, 1, '2024-01-20', NULL),
    (7, 10, 1, '2024-02-01', NULL),
    (8,  1, 1, '2024-07-01', NULL),
    (9,  2, 1, '2025-03-01', NULL),
    (9,  1, 1, '2025-03-01', NULL),
    (10, 2, 1, '2025-04-14', NULL),
    (11, 1, 1, '2025-02-03', NULL),
    (12, 2, 2, '2025-08-18', '2026-02-28'),
    (13, 4, 1, '2025-06-01', NULL),
    (14, 3, 1, '2024-04-08', NULL),
    (14, 5, 1, '2024-04-08', NULL),
    (15, 5, 1, '2024-11-04', NULL),
    (16, 1, 1, '2024-03-11', NULL),
    (16, 11, 1, '2024-03-11', NULL),
    (17, 2, 1, '2025-01-27', NULL),
    (18, 2, 1, '2025-05-05', NULL),
    (19, 8, 2, '2023-02-06', '2025-12-19'),
    (20, 2, 2, '2024-08-12', '2025-10-31');

-- ==========================================
-- INSERÇÃO DO PLANEJAMENTO (quadro ideal por empresa e cargo)
-- ==========================================

INSERT INTO planejamento (id_empresa, id_cargo, quantidade_ideal)
VALUES
    (1, 1,  4),
    (1, 2,  2),
    (1, 3,  2),
    (1, 4,  1),
    (2, 5,  2),
    (2, 6,  3),
    (2, 14, 1),
    (3, 7,  3),
    (3, 8,  4),
    (4, 9,  2),
    (4, 10, 3),
    (5, 11, 3),
    (5, 12, 2);

-- ==========================================
-- INSERÇÃO DAS VAGAS
-- ==========================================

INSERT INTO vagas (id_empresa, id_cargo, id_vinculo, posicoes, status, cidade, modalidade, salario, descricao, data_abertura)
VALUES
    (1, 1, 1, 2, 'Aberta',     'Belo Horizonte', 'Híbrido',    6000.00, 'Desenvolvedor para o time de produtos internos.',      '2026-10-05'),
    (1, 3, 1, 1, 'Em análise', 'Belo Horizonte', 'Remoto',     4200.00, 'Designer de interfaces com foco em sistemas web.',     '2026-10-20'),
    (2, 6, 1, 1, 'Aberta',     'Contagem',       'Presencial', 4500.00, 'Mestre de obras para novo empreendimento.',            '2026-11-02'),
    (3, 8, 1, 1, 'Aberta',     'Belo Horizonte', 'Presencial', 1650.00, 'Repositor para reposição do turno da manhã.',          '2026-11-09'),
    (4, 10, 1, 1, 'Encerrada', 'Belo Horizonte', 'Presencial', 5200.00, 'Enfermeiro para o plantão diurno.',                    '2026-08-12'),
    (5, 11, 1, 1, 'Aberta',    'Betim',          'Presencial', 3600.00, 'Motorista para rotas regionais.',                      '2026-11-12');

-- ==========================================
-- INSERÇÃO DOS CANDIDATOS
-- ==========================================

INSERT INTO candidatos (id_vaga, nome, email, telefone, linkedin, origem, observacoes, etapa, data_candidatura)
VALUES
    (1, 'Lucas Andrade',     'lucas.andrade@email.com',  '31988880001', 'https://linkedin.com/in/lucasandrade',  'LinkedIn',        'Boa experiência com PHP e MySQL.',          'Entrevista', '2026-10-08'),
    (1, 'Mariana Freitas',   'mariana.freitas@email.com','31988880002', 'https://linkedin.com/in/marianafreitas','Indicação',       'Indicada pelo Carlos Silva.',               'Proposta',   '2026-10-10'),
    (1, 'Pedro Henrique',    'pedro.h@email.com',        '31988880003', NULL,                                    'Site da empresa', NULL,                                        'Triagem',    '2026-10-14'),
    (1, 'Sérgio Matos',      'sergio.matos@email.com',   '31988880004', NULL,                                    'Outro',           'Sem experiência na stack utilizada.',       'Reprovado',  '2026-10-09'),
    (2, 'Aline Monteiro',    'aline.monteiro@email.com', '31988880005', 'https://linkedin.com/in/alinemonteiro', 'LinkedIn',        'Portfólio muito bom.',                      'Entrevista', '2026-10-24'),
    (3, 'José Lourenço',     'jose.lourenco@email.com',  '31988880006', NULL,                                    'Indicação',       '15 anos de experiência em obras.',          'Triagem',    '2026-11-04'),
    (4, 'Karina Oliveira',   'karina.oliveira@email.com','31988880007', NULL,                                    'Site da empresa', 'Disponibilidade imediata.',                 'Entrevista', '2026-11-11'),
    (5, 'Renata Pacheco',    'renata.pacheco@email.com', '31988880008', 'https://linkedin.com/in/renatapacheco', 'LinkedIn',        'Contratada para o plantão diurno.',         'Contratado', '2026-08-20'),
    (6, 'Anderson Moreira',  'anderson.moreira@email.com','31988880009',NULL,                                    'Outro',           'CNH categoria D.',                          'Triagem',    '2026-11-15'),
    (6, 'Wesley Batista',    'wesley.batista@email.com', '31988880010', NULL,                                    'Indicação',       'CNH categoria E.',                          'Entrevista', '2026-11-16');

-- ==========================================
-- INSERÇÃO DOS DESLIGAMENTOS
-- ==========================================

INSERT INTO desligamento (id_colaboradores, id_acessos, motivo, data_desligamento)
VALUES
    (12, 1, 'Término de contrato temporário',        '2026-02-28 17:00:00'),
    (19, 1, 'Encerramento das atividades da escola', '2025-12-19 17:00:00'),
    (20, 1, 'Encerramento das atividades da escola', '2025-10-31 17:00:00');

-- ==========================================
-- INSERÇÃO DO HISTÓRICO DE ALTERAÇÕES
-- ==========================================

INSERT INTO historico_alteracoes (id_acessos, id_empresa, id_registro, tabela_alterada, campo_alterado, valor_anterior, valor_novo, data_alteracao)
VALUES
    (1, 1, 1, 'vagas',      'status',   'Em análise', 'Aberta',     '2026-10-06 09:15:00'),
    (1, 1, 2, 'candidatos', 'etapa',    'Triagem',    'Entrevista', '2026-10-16 14:30:00'),
    (1, 1, 2, 'candidatos', 'etapa',    'Entrevista', 'Proposta',   '2026-10-28 11:00:00'),
    (1, 3, 7, 'candidatos', 'etapa',    'Triagem',    'Entrevista', '2026-11-13 10:20:00'),
    (1, 4, 8, 'candidatos', 'etapa',    'Proposta',   'Contratado', '2026-09-02 16:45:00');