CREATE DATABASE rhmanagement;
USE rhmanagement;

CREATE TABLE acessos (
	id_acesso INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	nome_user VARCHAR(100) NOT NULL,
	CPF CHAR(11) UNIQUE NOT NULL,
	email VARCHAR(100) NOT NULL,
	telefone VARCHAR(13) NOT NULL, 
	endereco VARCHAR(100) NOT NULL,
	senha VARCHAR(100) NOT NULL,
	nivel INT NOT NULL  
);

CREATE TABLE tipo_vinculo (
	id_vinculo INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	nome_vinculo VARCHAR(100)
);

CREATE TABLE statuss (
	id_status INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	status_agora VARCHAR(100)
);

CREATE TABLE empresas (
	id_empresas INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	nome_empresas VARCHAR(100) NOT NULL,
	CNPJ CHAR(14) UNIQUE NOT NULL,
	email VARCHAR(150) NOT NULL,
	telefone VARCHAR(13) NOT NULL,
	endereco VARCHAR(100) NOT NULL,
	razao_social VARCHAR(200) NOT NULL
);

CREATE TABLE beneficios (
	id_beneficios INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	id_status INT,
	nome_beneficios VARCHAR(100) NOT NULL,
	descricao VARCHAR(250) NOT NULL,
	FOREIGN KEY (id_status) REFERENCES statuss(id_status)
);	

CREATE TABLE vinculo_trabalista(
	id_trabalista INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	id_empresa INT,
	id_status INT,
	cargo VARCHAR(150) NOT NULL,
	data_inicio DATE NOT NULL,
	data_termino DATE,
	salario DECIMAL(10,2) NOT NULL,
	FOREIGN KEY(id_empresa) REFERENCES empresas(id_empresas),
	FOREIGN KEY(id_status) REFERENCES statuss(id_status)
);
CREATE TABLE colaboradores(	
	id_colaboradores INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	id_vinculo INT,
	id_trabalista INT,
	id_empresa INT,
	id_status INT,
	id_beneficios INT,
	nome_user VARCHAR(100),
	CPF CHAR(11) NOT NULL UNIQUE,
	email VARCHAR(150) NOT NULL,
	endereço VARCHAR(100) NOT NULL,
	telefone VARCHAR(13) NOT NULL,
	FOREIGN KEY (id_vinculo) REFERENCES tipo_vinculo(id_vinculo),
	FOREIGN KEY (id_trabalista) REFERENCES vinculo_trabalista(id_trabalista),
	FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas),
	FOREIGN KEY (id_status) REFERENCES statuss(id_status),
	FOREIGN KEY (id_beneficios) REFERENCES beneficios(id_beneficios)
	);

CREATE TABLE historico_alteracoes (
		id_historicos INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
		id_acessos INT,
		id_empresa INT,
		id_registro INT,
		tabela_alterada VARCHAR(150) NOT NULL,
		campo_alterado VARCHAR(100) NOT NULL,
		valor_anterior VARCHAR(30) NOT NULL,
		valor_novo VARCHAR(30) NOT NULL,
		data_alteracao DATETIME NOT NULL,
		FOREIGN KEY (id_acessos) REFERENCES acessos(id_acesso),
		FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas)
);

CREATE TABLE desligamento (
		id_desligamento INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
		id_colaboradores INT,
		id_acessos INT,
		motivo VARCHAR(250) NOT NULL,
		data_desligamento DATETIME NOT NULL,
		FOREIGN KEY (id_colaboradores) REFERENCES colaboradores(id_colaboradores),
		FOREIGN KEY (id_acessos) REFERENCES acessos(id_acesso) 
);


-- =====================================================================
-- MÓDULO DE BENEFÍCIOS
-- Rode este arquivo DEPOIS do BD.sql (ele ajusta e complementa o banco).
-- =====================================================================
USE rhmanagement;

INSERT INTO statuss (status_agora)
SELECT 'Ativo' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM statuss WHERE status_agora = 'Ativo');

INSERT INTO statuss (status_agora)
SELECT 'Inativo' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM statuss WHERE status_agora = 'Inativo');


ALTER TABLE beneficios
    ADD COLUMN tipo VARCHAR(50)   NOT NULL DEFAULT 'Outro' AFTER descricao,
    ADD COLUMN valor DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tipo,
    ADD COLUMN desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER valor,
    ADD COLUMN id_empresa INT NULL AFTER id_status,
    ADD CONSTRAINT fk_beneficios_empresa
        FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas);

CREATE TABLE colaborador_beneficio (
    id_vinculo_beneficio INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_colaboradores     INT NOT NULL,
    id_beneficios        INT NOT NULL,
    id_status            INT,
    data_inicio          DATE NOT NULL,
    data_fim             DATE NULL,
    FOREIGN KEY (id_colaboradores) REFERENCES colaboradores(id_colaboradores) ON DELETE CASCADE,
    FOREIGN KEY (id_beneficios)    REFERENCES beneficios(id_beneficios)       ON DELETE CASCADE,
    FOREIGN KEY (id_status)        REFERENCES statuss(id_status),
    -- impede vincular o mesmo benefício duas vezes ao mesmo colaborador
    UNIQUE KEY uq_colab_benef (id_colaboradores, id_beneficios)
);

-- Exemplos para testar a tela
-- ---------------------------------------------------------------------
INSERT INTO beneficios (id_status, nome_beneficios, descricao, tipo, valor, desconto)
SELECT s.id_status, b.nome, b.descricao, b.tipo, b.valor, b.desconto
FROM statuss s
JOIN (
    SELECT 'Vale-refeição'    AS nome, 'Cartão para refeições em restaurantes'       AS descricao, 'Alimentação' AS tipo, 600.00 AS valor,  0.00 AS desconto UNION ALL
    SELECT 'Vale-transporte',          'Auxílio para o deslocamento casa-trabalho',        'Transporte',   220.00,  0.00 UNION ALL
    SELECT 'Plano de saúde',           'Plano médico e hospitalar nacional',               'Saúde',        450.00, 90.00 UNION ALL
    SELECT 'Plano odontológico',       'Cobertura odontológica completa',                  'Odontológico',  45.00,  0.00 UNION ALL
    SELECT 'Seguro de vida',           'Seguro de vida em grupo',                          'Seguro',        30.00,  0.00 UNION ALL
    SELECT 'Gympass',                  'Acesso a academias e atividades físicas',          'Bem-estar',     90.00, 30.00
) b
WHERE s.status_agora = 'Ativo'
  AND NOT EXISTS (SELECT 1 FROM beneficios WHERE nome_beneficios = b.nome);

