-- Seed separado gerado a partir do schema principal.
USE rh_sistema;

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
