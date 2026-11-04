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


