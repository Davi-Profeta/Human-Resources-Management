/* BD RH Management */

CREATE DATABASE rhmanagement;
USE rhrhmanagement;

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
