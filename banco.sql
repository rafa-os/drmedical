
--  DR Medical Center — Banco de Dados
--  Este arquivo cria toda a estrutura do banco e insere os
--  dados iniciais para teste. Basta importar uma vez.

--  Credenciais de teste (todos com senha: 123456):
--    Paciente:  joao@email.com
--    Médico:    123456/SP  ou  carlos@drmedical.com
--    Admin:     admin@drmedical.com

CREATE DATABASE IF NOT EXISTS drmedical
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE drmedical;

CREATE TABLE especialidades (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL
);

INSERT INTO especialidades (nome) VALUES
  ('Clínica Geral'),
  ('Cardiologia'),
  ('Dermatologia'),
  ('Ortopedia'),
  ('Neurologia'),
  ('Pediatria'),
  ('Ginecologia'),
  ('Psiquiatria');

CREATE TABLE administradores (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  nome  VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL
);

INSERT INTO administradores (nome, email, senha) VALUES
('Administrador', 'admin@drmedical.com', '123456');

CREATE TABLE medicos (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  nome             VARCHAR(150) NOT NULL,
  crm              VARCHAR(30)  NOT NULL UNIQUE, -- formato: 123456/SP
  especialidade_id INT          NOT NULL,
  email            VARCHAR(150) NOT NULL UNIQUE,
  senha            VARCHAR(255) NOT NULL,
  bio              TEXT,                          -- apresentação exibida aos pacientes
  ativo            TINYINT(1)   DEFAULT 1,        -- 1 = ativo, 0 = desativado
  criado_por       INT          DEFAULT NULL,
  FOREIGN KEY (especialidade_id) REFERENCES especialidades(id),
  FOREIGN KEY (criado_por) REFERENCES administradores(id) ON DELETE SET NULL
);

INSERT INTO medicos (nome, crm, especialidade_id, email, senha, bio) VALUES
('Dr. Carlos Mendes',   '123456/SP', 1, 'carlos@drmedical.com',   '123456', 'Clínico geral com 16 anos de experiência em medicina preventiva.'),
('Dra. Ana Rodrigues',  '234567/SP', 2, 'ana@drmedical.com',      '123456', 'Cardiologista especializada em prevenção cardiovascular, formada pela USP.'),
('Dr. Felipe Santos',   '345678/SP', 3, 'felipe@drmedical.com',   '123456', 'Dermatologista com foco em dermatologia clínica e cirúrgica.'),
('Dra. Beatriz Souza',  '456789/SP', 4, 'beatriz@drmedical.com',  '123456', 'Ortopedista com foco em joelho, quadril e medicina esportiva.'),
('Dr. Rafael Lima',     '567890/SP', 5, 'rafael@drmedical.com',   '123456', 'Neurologista com especialização em cefaleia e doenças neurodegenerativas.'),
('Dra. Juliana Rocha',  '678901/SP', 6, 'juliana@drmedical.com',  '123456', 'Pediatra dedicada ao desenvolvimento infantil e saúde do adolescente.'),
('Dra. Camila Ferreira','789012/SP', 7, 'camila@drmedical.com',   '123456', 'Ginecologista com foco em saúde da mulher e medicina reprodutiva.'),
('Dr. Marcelo Costa',   '890123/SP', 8, 'marcelo@drmedical.com',  '123456', 'Psiquiatra com experiência em saúde mental e transtornos do humor.');


CREATE TABLE pacientes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(150) NOT NULL,
  cpf         VARCHAR(14)  NOT NULL UNIQUE,    -- formato: 000.000.000-00
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha       VARCHAR(255) NOT NULL,
  telefone    VARCHAR(20),                      -- opcional no cadastro
  nascimento  DATE,                             -- opcional no cadastro
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  criado_por  INT      DEFAULT NULL,            -- NULL = auto-cadastro pelo portal
  FOREIGN KEY (criado_por) REFERENCES administradores(id) ON DELETE SET NULL
);

INSERT INTO pacientes (nome, cpf, email, senha, telefone, nascimento) VALUES
('João Costa', '123.456.789-00', 'joao@email.com', '123456', '(11) 98765-4321', '1990-05-15');

CREATE TABLE consultas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id  INT         NOT NULL,
  medico_id    INT         NOT NULL,
  data         DATE        NOT NULL,
  hora         TIME        NOT NULL,
  tipo         VARCHAR(20) DEFAULT 'presencial',  -- presencial, teleconsulta, retorno
  convenio     VARCHAR(80) DEFAULT 'Particular',
  motivo       TEXT,                               -- sintomas informados pelo paciente
  status       VARCHAR(20) DEFAULT 'agendada',
  criado_em    DATETIME    DEFAULT CURRENT_TIMESTAMP,
  agendado_por INT         DEFAULT NULL,           -- NULL = agendado pelo próprio paciente
  FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
  FOREIGN KEY (medico_id)    REFERENCES medicos(id),
  FOREIGN KEY (agendado_por) REFERENCES administradores(id) ON DELETE SET NULL,
  UNIQUE KEY sem_dupla (medico_id, data, hora)     -- impede duplo agendamento no banco
);

INSERT INTO consultas (paciente_id, medico_id, data, hora, tipo, convenio, status) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  '14:00', 'presencial',   'Unimed',    'confirmada'),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 9 DAY),  '09:30', 'teleconsulta', 'Particular','agendada'),
(1, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY),  '10:00', 'presencial',   'Amil',      'realizada'),
(1, 4, DATE_SUB(CURDATE(), INTERVAL 20 DAY), '11:00', 'presencial',   'Unimed',    'realizada'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 60 DAY), '15:30', 'presencial',   'Unimed',    'cancelada');
