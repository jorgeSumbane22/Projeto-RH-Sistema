-- =====================================================================
-- Sistema de RH - Schema do Banco de Dados
-- Motor: MySQL / MariaDB (compatível com XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS rh_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rh_sistema;

-- ---------------------------------------------------------------------
-- DEPARTAMENTOS
-- ---------------------------------------------------------------------
CREATE TABLE departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    gestor_id INT NULL, -- FK para funcionarios.id (definida após criar a tabela)
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CARGOS
-- ---------------------------------------------------------------------
CREATE TABLE cargos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    departamento_id INT NOT NULL,
    salario_base DECIMAL(10,2) NOT NULL DEFAULT 0,
    descricao TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cargos_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FUNCIONARIOS
-- ---------------------------------------------------------------------
CREATE TABLE funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(20) NOT NULL UNIQUE, -- usado como "BI"/documento nacional; ajuste a máscara conforme país
    rg VARCHAR(30) NULL,
    data_nascimento DATE NULL,
    genero ENUM('masculino','feminino','outro') NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefone VARCHAR(30) NULL,
    endereco VARCHAR(255) NULL,
    data_admissao DATE NOT NULL,
    data_demissao DATE NULL,
    cargo_id INT NOT NULL,
    departamento_id INT NOT NULL,
    salario DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('ativo','inativo','ferias','licenca') NOT NULL DEFAULT 'ativo',
    foto VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_func_cargo FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_func_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE departamentos
    ADD CONSTRAINT fk_dep_gestor FOREIGN KEY (gestor_id) REFERENCES funcionarios(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- USUARIOS (login/controle de acesso)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('super_admin','administrador_rh','gestor','funcionario','auditor') NOT NULL DEFAULT 'funcionario',
    funcionario_id INT NULL, -- vincula o usuário a um registro de funcionário (null para admin puro)
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    tentativas_login INT NOT NULL DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    tema_preferido ENUM('claro','escuro') NOT NULL DEFAULT 'claro',
    ultimo_login DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DOCUMENTOS (contratos, BI, certificados etc.)
-- ---------------------------------------------------------------------
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionario_id INT NOT NULL,
    tipo ENUM('contrato','bi','certificado','curriculo','outro') NOT NULL DEFAULT 'outro',
    nome_original VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    tamanho_bytes INT NULL,
    enviado_por INT NULL, -- usuarios.id
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRESENCA (ponto)
-- ---------------------------------------------------------------------
CREATE TABLE presencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionario_id INT NOT NULL,
    data DATE NOT NULL,
    hora_entrada TIME NULL,
    hora_saida TIME NULL,
    horas_extras DECIMAL(5,2) NOT NULL DEFAULT 0,
    status ENUM('presente','falta','atraso','justificado') NOT NULL DEFAULT 'presente',
    observacao VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_presenca_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE,
    UNIQUE KEY uk_funcionario_data (funcionario_id, data)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FERIAS E LICENCAS
-- ---------------------------------------------------------------------
CREATE TABLE ferias_licencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionario_id INT NOT NULL,
    tipo ENUM('ferias','licenca_medica','licenca_maternidade','licenca_paternidade','outro') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    dias INT NOT NULL,
    status ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
    aprovado_por INT NULL, -- usuarios.id
    motivo VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ferias_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_ferias_aprovador FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FOLHA DE PAGAMENTO
-- ---------------------------------------------------------------------
CREATE TABLE folha_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionario_id INT NOT NULL,
    mes_referencia CHAR(7) NOT NULL, -- formato 'YYYY-MM'
    salario_base DECIMAL(10,2) NOT NULL,
    bonus DECIMAL(10,2) NOT NULL DEFAULT 0,
    horas_extras_valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    descontos DECIMAL(10,2) NOT NULL DEFAULT 0,
    faltas_desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
    salario_liquido DECIMAL(10,2) NOT NULL,
    status ENUM('pendente','pago') NOT NULL DEFAULT 'pendente',
    data_pagamento DATE NULL,
    observacao VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_folha_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE,
    UNIQUE KEY uk_funcionario_mes (funcionario_id, mes_referencia)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RECRUTAMENTO: VAGAS
-- ---------------------------------------------------------------------
CREATE TABLE vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    departamento_id INT NOT NULL,
    descricao TEXT NULL,
    requisitos TEXT NULL,
    status ENUM('aberta','fechada','pausada') NOT NULL DEFAULT 'aberta',
    data_publicacao DATE NOT NULL,
    criado_por INT NULL, -- usuarios.id
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vaga_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RECRUTAMENTO: CANDIDATOS
-- ---------------------------------------------------------------------
CREATE TABLE candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NULL,
    curriculo_arquivo VARCHAR(255) NULL,
    status ENUM('inscrito','triagem','entrevista','aprovado','rejeitado') NOT NULL DEFAULT 'inscrito',
    data_inscricao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidato_vaga FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RECRUTAMENTO: ENTREVISTAS
-- ---------------------------------------------------------------------
CREATE TABLE entrevistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidato_id INT NOT NULL,
    entrevistador_id INT NULL, -- usuarios.id
    data_hora DATETIME NOT NULL,
    local_ou_link VARCHAR(255) NULL,
    resultado ENUM('pendente','aprovado','reprovado') NOT NULL DEFAULT 'pendente',
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrevista_candidato FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE CASCADE,
    CONSTRAINT fk_entrevista_entrevistador FOREIGN KEY (entrevistador_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- AVALIACAO DE DESEMPENHO
-- ---------------------------------------------------------------------
CREATE TABLE avaliacoes_desempenho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionario_id INT NOT NULL,
    avaliador_id INT NULL, -- usuarios.id
    periodo VARCHAR(20) NOT NULL, -- ex: '2026-T1'
    pontualidade TINYINT NOT NULL CHECK (pontualidade BETWEEN 1 AND 5),
    qualidade_trabalho TINYINT NOT NULL CHECK (qualidade_trabalho BETWEEN 1 AND 5),
    trabalho_equipe TINYINT NOT NULL CHECK (trabalho_equipe BETWEEN 1 AND 5),
    proatividade TINYINT NOT NULL CHECK (proatividade BETWEEN 1 AND 5),
    nota_final DECIMAL(3,2) NOT NULL,
    comentarios TEXT NULL,
    data_avaliacao DATE NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaliacao_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_avaliador FOREIGN KEY (avaliador_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTIFICACOES
-- ---------------------------------------------------------------------
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('ferias','aniversario','contrato','sistema','avaliacao') NOT NULL DEFAULT 'sistema',
    titulo VARCHAR(150) NOT NULL,
    mensagem VARCHAR(255) NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- LOG DE AUDITORIA (opcional, mas boa prática para RH)
-- ---------------------------------------------------------------------
CREATE TABLE log_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    acao VARCHAR(100) NOT NULL,
    detalhes VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RECUPERAÇÃO DE SENHA
-- ---------------------------------------------------------------------
CREATE TABLE senha_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DADOS INICIAIS (SEED)
-- =====================================================================

INSERT INTO departamentos (nome, descricao) VALUES
('Diretoria', 'Direção executiva da empresa'),
('Recursos Humanos', 'Gestão de pessoas e cultura'),
('Tecnologia da Informação', 'Desenvolvimento e infraestrutura'),
('Financeiro', 'Contas a pagar/receber e contabilidade'),
('Comercial', 'Vendas e relacionamento com clientes');

INSERT INTO cargos (titulo, departamento_id, salario_base, descricao) VALUES
('Diretor Geral', 1, 25000.00, 'Responsável pela direção estratégica'),
('Analista de RH', 2, 4500.00, 'Recrutamento, folha e benefícios'),
('Gerente de RH', 2, 8500.00, 'Gestão da equipe de RH'),
('Desenvolvedor Full-Stack', 3, 7000.00, 'Desenvolvimento de sistemas web'),
('Gerente de TI', 3, 10500.00, 'Gestão da equipe de tecnologia'),
('Analista Financeiro', 4, 5000.00, 'Controle financeiro e relatórios'),
('Vendedor', 5, 3500.00, 'Atendimento e vendas a clientes');

-- Funcionário administrador inicial (vinculado ao usuário admin)
INSERT INTO funcionarios (nome, cpf, data_nascimento, genero, email, telefone, data_admissao, cargo_id, departamento_id, salario, status) VALUES
('Administrador do Sistema', '000.000.000-00', '1990-01-01', 'outro', 'admin@empresa.com', '(00) 00000-0000', CURDATE(), 1, 1, 25000.00, 'ativo');

-- Funcionários fictícios para testar os perfis e os módulos do sistema.
INSERT INTO funcionarios (nome, cpf, data_nascimento, genero, email, telefone, data_admissao, cargo_id, departamento_id, salario, status) VALUES
('Ana Costa', '000.000.000-02', '1988-04-12', 'feminino', 'ana.costa@empresa.com', '(00) 90000-0002', '2022-03-10', 3, 2, 8500.00, 'ativo'),
('Bruno Almeida', '000.000.000-03', '1985-09-21', 'masculino', 'bruno.almeida@empresa.com', '(00) 90000-0003', '2021-07-05', 5, 3, 10500.00, 'ativo'),
('Carla Mendes', '000.000.000-04', '1992-11-08', 'feminino', 'carla.mendes@empresa.com', '(00) 90000-0004', '2023-01-16', 2, 2, 4500.00, 'ativo'),
('Diogo Santos', '000.000.000-05', '1995-06-30', 'masculino', 'diogo.santos@empresa.com', '(00) 90000-0005', '2024-02-01', 4, 3, 7000.00, 'ativo');

-- Utilizadores fictícios para testes.
-- Palavra-passe inicial de todos: Admin@123. Altere-a após instalar o sistema.
-- Hash gerado com password_hash('Admin@123', PASSWORD_BCRYPT).
INSERT INTO usuarios (nome, email, senha_hash, perfil, funcionario_id, ativo) VALUES
('Super Admin', 'superadmin@empresa.com', '$2y$10$cVWphvkSLnFJZyH/MTBMWuITG0iNOPptqm77/DH6m.ggI6PwPnIEK', 'super_admin', 1, 1),
('Administrador RH', 'administrador@empresa.com', '$2y$10$cVWphvkSLnFJZyH/MTBMWuITG0iNOPptqm77/DH6m.ggI6PwPnIEK', 'administrador_rh', 1, 1),
('Gestor', 'gestor@empresa.com', '$2y$10$cVWphvkSLnFJZyH/MTBMWuITG0iNOPptqm77/DH6m.ggI6PwPnIEK', 'gestor', 3, 1),
('Funcionário', 'funcionario@empresa.com', '$2y$10$cVWphvkSLnFJZyH/MTBMWuITG0iNOPptqm77/DH6m.ggI6PwPnIEK', 'funcionario', 5, 1),
('Auditor', 'auditor@empresa.com', '$2y$10$cVWphvkSLnFJZyH/MTBMWuITG0iNOPptqm77/DH6m.ggI6PwPnIEK', 'auditor', NULL, 1);
