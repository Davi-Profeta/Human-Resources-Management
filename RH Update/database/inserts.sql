USE rhmanagement;

-- ==========================================
-- INSERÇÃO DOS STATUS INICIAIS
-- ==========================================

INSERT INTO statuss (status_agora)
VALUES
    ('Ativo'),
    ('Inativo');

-- ==========================================
-- INSERÇÃO DOS VÍNCULOS
-- ==========================================

INSERT INTO tipo_vinculo (nome_vinculo)
VALUES
    ('CLT'),
    ('PJ'),
    ('Estágio'),
    ('Temporário'),
    ('Autônomo');

-- ==========================================
-- INSERÇÃO DO LOGIN PRINCIPAL
-- ==========================================

INSERT INTO acessos (nome_user, CPF, email, telefone, endereco, senha, nivel)
VALUES ('ADM', '00000000000', 'admaster@gmail.com', '31999999999', 'Rua ctti - 477', '1234', 1);

-- ==========================================
-- INSERÇÃO DOS BENEFÍCIOS INICIAIS
-- (id_status 1 = Ativo)
-- ==========================================

INSERT INTO beneficios (id_status, nome_beneficios, descricao, tipo, valor, desconto)
VALUES
    (1, 'Vale-refeição',      'Cartão para refeições em restaurantes',      'Alimentação',  600.00,  0.00),
    (1, 'Vale-transporte',    'Auxílio para o deslocamento casa-trabalho',  'Transporte',   220.00,  0.00),
    (1, 'Plano de saúde',     'Plano médico e hospitalar nacional',         'Saúde',        450.00, 90.00),
    (1, 'Plano odontológico', 'Cobertura odontológica completa',            'Odontológico',  45.00,  0.00),
    (1, 'Seguro de vida',     'Seguro de vida em grupo',                    'Seguro',        30.00,  0.00),
    (1, 'Gympass',            'Acesso a academias e atividades físicas',    'Bem-estar',     90.00, 30.00),
    (1, 'Auxílio-creche',     'Reembolso de mensalidade de creche',         'Família',      350.00,  0.00),
    (1, 'Auxílio-educação',   'Bolsa para cursos e graduações',             'Educação',     400.00,  0.00);

-- ==========================================
-- VERIFICAÇÃO FINAL
-- ==========================================

SHOW TABLES;

SELECT * FROM statuss;

SELECT * FROM beneficios;