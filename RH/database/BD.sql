CREATE DATABASE IF NOT EXISTS rhmanagement;

USE rhmanagement;

-- ==========================================
-- TABELA: ACESSOS
-- ==========================================
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

-- ==========================================
-- TABELA: TIPO DE VÍNCULO
-- ==========================================
CREATE TABLE tipo_vinculo (
    id_vinculo INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nome_vinculo VARCHAR(100)
);

-- ==========================================
-- TABELA: STATUS
-- ==========================================
CREATE TABLE statuss (
    id_status INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    status_agora VARCHAR(100)
);

-- ==========================================
-- TABELA: EMPRESAS
-- ==========================================
CREATE TABLE empresas (
    id_empresas INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nome_empresas VARCHAR(100) NOT NULL,
    CNPJ CHAR(14) UNIQUE NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(13) NOT NULL,
    endereco VARCHAR(100) NOT NULL,
    razao_social VARCHAR(200) NOT NULL,
    id_stats int,
    FOREIGN KEY(id_stats) REFERENCES statuss(id_status)
);

-- ==========================================
-- TABELA: BENEFÍCIOS
-- ==========================================
CREATE TABLE beneficios (
    id_beneficios INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_status INT,
    id_empresa INT NULL,
    nome_beneficios VARCHAR(100) NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'Outro',
    valor DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    desconto DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_status) REFERENCES statuss(id_status),
    CONSTRAINT fk_beneficios_empresa FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas)
);

-- ==========================================
-- TABELA: VÍNCULO TRABALHISTA
-- ==========================================
CREATE TABLE vinculo_trabalista (
    id_trabalista INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_empresa INT,
    id_status INT,
    cargo VARCHAR(150) NOT NULL,
    data_inicio DATE NOT NULL,
    data_termino DATE,
    salario DECIMAL(10, 2) NOT NULL,
    jornada_entrada TIME NOT NULL DEFAULT '08:00:00',
    jornada_intervalo_inicio TIME NULL DEFAULT '12:00:00',
    jornada_intervalo_fim TIME NULL DEFAULT '13:00:00',
    jornada_saida TIME NOT NULL DEFAULT '17:00:00',
    jornada_tolerancia INT NOT NULL DEFAULT 10,
    jornada_dias VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5',
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas),
    FOREIGN KEY (id_status) REFERENCES statuss(id_status)
);

-- ==========================================
-- TABELA: COLABORADORES
-- ==========================================
CREATE TABLE colaboradores (
    id_colaboradores INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_vinculo INT,
    id_trabalista INT,
    id_empresa INT,
    id_status INT,
    id_beneficios INT,
    nome_user VARCHAR(100),
    CPF CHAR(11) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL,
    `endereço` VARCHAR(100) NOT NULL,
    telefone VARCHAR(13) NOT NULL,
    FOREIGN KEY (id_vinculo) REFERENCES tipo_vinculo(id_vinculo),
    FOREIGN KEY (id_trabalista) REFERENCES vinculo_trabalista(id_trabalista),
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas),
    FOREIGN KEY (id_status) REFERENCES statuss(id_status),
    FOREIGN KEY (id_beneficios) REFERENCES beneficios(id_beneficios),
    img VARCHAR(500)
);

-- ==========================================
-- TABELA: ACESSOS DOS COLABORADORES AO PONTO
-- ==========================================
CREATE TABLE colaborador_acessos (
    id_colaborador_acesso INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_colaboradores INT NOT NULL UNIQUE,
    pin_hash VARCHAR(255) NOT NULL,
    exigir_troca TINYINT(1) NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso DATETIME NULL,
    FOREIGN KEY (id_colaboradores) REFERENCES colaboradores(id_colaboradores) ON DELETE CASCADE
);

-- ==========================================
-- TABELA: MARCAÇÕES DE PONTO
-- ==========================================
CREATE TABLE ponto_marcacoes (
    id_marcacao BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_colaboradores INT NOT NULL,
    id_empresa INT NULL,
    data_hora DATETIME NOT NULL,
    data_local DATE NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    origem VARCHAR(30) NOT NULL DEFAULT 'portal',
    ip VARCHAR(45) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ponto_colaborador_data (id_colaboradores, data_local, data_hora),
    FOREIGN KEY (id_colaboradores) REFERENCES colaboradores(id_colaboradores) ON DELETE CASCADE,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas) ON DELETE SET NULL
);

-- ==========================================
-- TABELA: HISTÓRICO DE ALTERAÇÕES
-- ==========================================
CREATE TABLE historico_alteracoes (
    id_historicos INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_acessos INT,
    id_empresa INT,
    id_registro INT,
    tabela_alterada VARCHAR(150) NOT NULL,
    campo_alterado VARCHAR(100) NOT NULL,
    valor_anterior VARCHAR(255) NOT NULL,
    valor_novo VARCHAR(255) NOT NULL,
    data_alteracao DATETIME NOT NULL,
    FOREIGN KEY (id_acessos) REFERENCES acessos(id_acesso),
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas)
);

-- ==========================================
-- TABELA: DESLIGAMENTO
-- ==========================================
CREATE TABLE desligamento (
    id_desligamento INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_colaboradores INT,
    id_acessos INT,
    motivo VARCHAR(250) NOT NULL,
    data_desligamento DATETIME NOT NULL,
    FOREIGN KEY (id_colaboradores) REFERENCES colaboradores(id_colaboradores),
    FOREIGN KEY (id_acessos) REFERENCES acessos(id_acesso)
);

-- ==========================================
-- TABELA: COLABORADOR BENEFÍCIO
-- ==========================================
CREATE TABLE colaborador_beneficio (
    id_vinculo_beneficio INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_colaboradores INT NOT NULL,
    id_beneficios INT NOT NULL,
    id_status INT,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    valor_aplicado DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    desconto_aplicado DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_colaboradores) REFERENCES colaboradores(id_colaboradores) ON DELETE CASCADE,
    FOREIGN KEY (id_beneficios) REFERENCES beneficios(id_beneficios) ON DELETE CASCADE,
    FOREIGN KEY (id_status) REFERENCES statuss(id_status),
    UNIQUE KEY uq_colab_benef (
        id_colaboradores,
        id_beneficios
    )
);

-- ==========================================
-- TABELA: CARGOS
-- ==========================================
CREATE TABLE IF NOT EXISTS cargos (
    id_cargo INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nome_cargo VARCHAR(100) NOT NULL UNIQUE
);

-- ==========================================
-- TABELA: PLANEJAMENTO (quadro ideal por empresa e cargo)
-- ==========================================
CREATE TABLE IF NOT EXISTS planejamento (
    id_planejamento INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_empresa INT NOT NULL,
    id_cargo INT NOT NULL,
    quantidade_ideal INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_planejamento (id_empresa, id_cargo),
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas) ON DELETE CASCADE,
    FOREIGN KEY (id_cargo) REFERENCES cargos(id_cargo) ON DELETE CASCADE
);

-- ==========================================
-- TABELA: VAGAS (criadas pelo "Solicitar contratação")
-- ==========================================
CREATE TABLE IF NOT EXISTS vagas (
    id_vaga INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_empresa INT NOT NULL,
    id_cargo INT NOT NULL,
    id_vinculo INT NULL,
    posicoes INT NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'Aberta',
    cidade VARCHAR(100) NULL,
    modalidade VARCHAR(20) NOT NULL DEFAULT 'Presencial',
    salario DECIMAL(10, 2) NULL,
    descricao TEXT NULL,
    data_abertura DATE NOT NULL,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresas) ON DELETE CASCADE,
    FOREIGN KEY (id_cargo) REFERENCES cargos(id_cargo) ON DELETE CASCADE,
    FOREIGN KEY (id_vinculo) REFERENCES tipo_vinculo(id_vinculo)
);

-- ==========================================
-- TABELA: CANDIDATOS (candidaturas de cada vaga)
-- ==========================================
CREATE TABLE IF NOT EXISTS candidatos (
    id_candidato INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_vaga INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(20) NULL,
    linkedin VARCHAR(255) NULL,
    origem VARCHAR(30) NOT NULL DEFAULT 'Outro',
    observacoes VARCHAR(500) NULL,
    etapa VARCHAR(20) NOT NULL DEFAULT 'Triagem',
    data_candidatura DATE NOT NULL,
    FOREIGN KEY (id_vaga) REFERENCES vagas(id_vaga) ON DELETE CASCADE
);

-- ==========================================
-- ATUALIZAÇÃO: VALOR DO BENEFÍCIO POR COLABORADOR
-- ==========================================
-- As colunas valor_aplicado e desconto_aplicado já fazem parte da tabela
-- colaborador_beneficio. Este preenchimento mantém o valor congelado no vínculo.
-- Em um banco novo, será executado antes dos dados de exemplo e não terá linhas.
UPDATE colaborador_beneficio cb
JOIN beneficios b ON b.id_beneficios = cb.id_beneficios
SET cb.valor_aplicado = b.valor,
    cb.desconto_aplicado = b.desconto;
