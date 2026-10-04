-- =====================================================================
-- Portal de Solicitações Internas — criação completa do banco de dados
--
-- Cria o banco, as tabelas, os dados de referência e os dados de
-- demonstração em um único passo. Pode ser executado de novo: as tabelas
-- existentes são removidas e recriadas (os dados anteriores são perdidos).
--
-- Uso (MySQL 8):
--   mysql -u root -p --default-character-set=utf8mb4 < database/setup.sql
--
-- Gerado a partir de database/migrations e database/seeds, na ordem.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS portal_solicitacoes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE portal_solicitacoes;

SET NAMES utf8mb4;

-- Remove na ordem inversa das chaves estrangeiras
DROP TABLE IF EXISTS solicitacao_historico;
DROP TABLE IF EXISTS login_tentativas;
DROP TABLE IF EXISTS solicitacoes;
DROP TABLE IF EXISTS status;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;


-- ---------------------------------------------------------------------
-- migrations/001_create_tables.sql
-- ---------------------------------------------------------------------

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(20) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('SOLICITANTE', 'ATENDENTE') NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    sla_horas SMALLINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE status (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL UNIQUE,
    ordem TINYINT UNSIGNED NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE solicitacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    status_id INT UNSIGNED NOT NULL,
    solicitante_id INT UNSIGNED NOT NULL,
    atendente_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    concluido_em DATETIME NULL,
    INDEX idx_solicitacoes_criado_em (criado_em),

    CONSTRAINT fk_solicitacoes_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacoes_status
        FOREIGN KEY (status_id) REFERENCES status(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacoes_solicitante
        FOREIGN KEY (solicitante_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacoes_atendente
        FOREIGN KEY (atendente_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- migrations/002_dados_referencia.sql
-- ---------------------------------------------------------------------

INSERT INTO categorias (nome, sla_horas) VALUES
    ('TI', 24),
    ('RH', 48),
    ('Compras', 72),
    ('Financeiro', 72),
    ('Infraestrutura', 72);


INSERT INTO status (nome, ordem) VALUES
    ('Aberto', 1),
    ('Em Atendimento', 2),
    ('Concluído', 3);

-- ---------------------------------------------------------------------
-- migrations/003_create_auditoria.sql
-- ---------------------------------------------------------------------

CREATE TABLE solicitacao_historico(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT UNSIGNED NOT NULL,
    status_anterior_id INT UNSIGNED NULL,
    status_novo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    observacao VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_solicitacao_historico_solicitacao_id
        FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
        
    CONSTRAINT fk_solicitacao_historico_status_anterior_id
        FOREIGN KEY (status_anterior_id) REFERENCES status(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
        
    CONSTRAINT fk_solicitacao_historico_status_novo_id
        FOREIGN KEY (status_novo_id) REFERENCES status(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    CONSTRAINT fk_solicitacao_historico_usuario_id
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
        

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_tentativas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_informado VARCHAR(100) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    sucesso BOOLEAN NOT NULL,
    user_agent VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_login_tentativas_usuario_ip_data (usuario_informado, ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- seeds/001_usuarios_demo.sql
-- ---------------------------------------------------------------------

INSERT INTO usuarios (nome, usuario, senha_hash, perfil)
VALUES
    (
        'Rodrigo Delgado',
        'rodrigodelgado',
        '$2y$10$aLk7ff6SzSTuQJcX8NiRduV0PDIU1NjDUlhK4qHS9wo4UkHUs1rUS',
        'SOLICITANTE'
    ),
    (
        'Frederico Augusto',
        'fredericoaugusto',
        '$2y$10$qvvxuMlUrZxCT.WqwEmkTOj9dN/cQKucakt.MlCFOsse6Ig7Tv7gy',
        'ATENDENTE'
    ),
    (
        'Mateus Jesus',
        'mateusjesus',
        '$2y$10$0obBqC.M6VgiEdCqBB/b4uHsVYFex41Jebt9Gm8YiJXiIGoO/o3ie',
        'SOLICITANTE'
    );

-- ---------------------------------------------------------------------
-- seeds/002_solicitacoes_demo.sql
-- ---------------------------------------------------------------------

-- Solicitações de demonstração: 6 concluídas, 5 em atendimento, 7 abertas.
-- Status: 1 = Aberto, 2 = Em Atendimento, 3 = Concluído
-- Usuários: 1 = Rodrigo (solicitante), 2 = Frederico (atendente), 3 = Mateus (solicitante)

INSERT INTO solicitacoes
    (id, titulo, descricao, categoria_id, status_id, solicitante_id, atendente_id, criado_em, atualizado_em, concluido_em)
VALUES
    (1,  'Computador não liga', 'O computador da recepção não liga desde a manhã. Já verifiquei a tomada.', 1, 3, 1, 2, '2026-09-01 08:15:00', '2026-09-01 15:40:00', '2026-09-01 15:40:00'),
    (2,  'Solicitação de férias', 'Gostaria de agendar minhas férias para o mês de novembro.', 2, 3, 3, 2, '2026-09-02 10:00:00', '2026-09-03 14:20:00', '2026-09-03 14:20:00'),
    (3,  'Compra de cadeiras para o auditório', 'Precisamos de 20 cadeiras novas para o auditório.', 3, 3, 1, 2, '2026-09-03 09:30:00', '2026-09-09 16:00:00', '2026-09-09 16:00:00'),
    (4,  'Reembolso de despesas de viagem', 'Solicito reembolso das despesas da viagem a João Pessoa.', 4, 3, 3, 2, '2026-09-05 14:00:00', '2026-09-07 11:00:00', '2026-09-07 11:00:00'),
    (5,  'Ar-condicionado da sala 12 com vazamento', 'O ar-condicionado está pingando água sobre a mesa de trabalho.', 5, 3, 1, 2, '2026-09-08 08:00:00', '2026-09-10 17:30:00', '2026-09-10 17:30:00'),
    (6,  'Acesso ao sistema financeiro', 'Preciso de acesso de leitura ao sistema financeiro para conferir relatórios.', 1, 3, 3, 2, '2026-09-10 13:00:00', '2026-09-10 16:45:00', '2026-09-10 16:45:00'),
    (7,  'Impressora do setor sem conexão', 'A impressora não aparece na rede desde ontem.', 1, 2, 1, 2, '2026-09-15 09:00:00', '2026-09-15 10:30:00', NULL),
    (8,  'Atualização de cadastro bancário', 'Mudei de banco e preciso atualizar a conta para recebimento do salário.', 2, 2, 3, 2, '2026-09-18 11:00:00', '2026-09-19 08:30:00', NULL),
    (9,  'Troca de lâmpadas do corredor', 'Três lâmpadas do corredor do 2º andar estão queimadas.', 5, 2, 1, 2, '2026-09-21 07:50:00', '2026-09-22 09:00:00', NULL),
    (10, 'Compra de toner', 'O toner da impressora do RH está no fim.', 3, 2, 3, 2, '2026-09-24 15:00:00', '2026-09-25 10:00:00', NULL),
    (11, 'Divergência no contracheque', 'O valor das horas extras de agosto veio diferente do esperado.', 4, 2, 1, 2, '2026-09-28 10:20:00', '2026-09-29 14:00:00', NULL),
    (12, 'Instalação de editor de PDF', 'Preciso de um editor de PDF para assinar documentos digitalmente.', 1, 1, 1, NULL, '2026-09-29 09:10:00', '2026-09-29 09:10:00', NULL),
    (13, 'Declaração de vínculo empregatício', 'Preciso de uma declaração de vínculo para apresentar no banco.', 2, 1, 3, NULL, '2026-09-29 14:30:00', '2026-09-29 14:30:00', NULL),
    (14, 'Compra de monitor adicional', 'Solicito um segundo monitor para melhorar a produtividade.', 3, 1, 1, NULL, '2026-09-30 08:40:00', '2026-09-30 08:40:00', NULL),
    (15, 'Pagamento de fornecedor em atraso', 'O fornecedor de material de limpeza informou atraso no pagamento.', 4, 1, 3, NULL, '2026-09-30 10:15:00', '2026-09-30 10:15:00', NULL),
    (16, 'Tomada sem energia na sala 5', 'A tomada ao lado da janela parou de funcionar.', 5, 1, 1, NULL, '2026-09-30 16:00:00', '2026-09-30 16:00:00', NULL),
    (17, 'Senha do e-mail expirada', 'Não consigo acessar meu e-mail corporativo desde hoje cedo.', 1, 1, 3, NULL, '2026-10-01 08:05:00', '2026-10-01 08:05:00', NULL),
    (18, 'Infiltração no teto do almoxarifado', 'Há uma mancha de umidade crescendo no teto do almoxarifado.', 5, 1, 1, NULL, '2026-10-01 08:30:00', '2026-10-01 08:30:00', NULL);

INSERT INTO solicitacao_historico
    (solicitacao_id, status_anterior_id, status_novo_id, usuario_id, observacao, criado_em)
VALUES
    -- Concluídas: criação, início do atendimento, conclusão
    (1, NULL, 1, 1, 'Solicitação criada.', '2026-09-01 08:15:00'),
    (1, 1, 2, 2, 'Verificando a fonte de alimentação.', '2026-09-01 09:00:00'),
    (1, 2, 3, 2, 'Fonte substituída; computador funcionando.', '2026-09-01 15:40:00'),
    (2, NULL, 1, 3, 'Solicitação criada.', '2026-09-02 10:00:00'),
    (2, 1, 2, 2, NULL, '2026-09-02 11:30:00'),
    (2, 2, 3, 2, 'Férias aprovadas e registradas.', '2026-09-03 14:20:00'),
    (3, NULL, 1, 1, 'Solicitação criada.', '2026-09-03 09:30:00'),
    (3, 1, 2, 2, 'Cotação iniciada com três fornecedores.', '2026-09-04 10:00:00'),
    (3, 2, 3, 2, 'Pedido de compra emitido.', '2026-09-09 16:00:00'),
    (4, NULL, 1, 3, 'Solicitação criada.', '2026-09-05 14:00:00'),
    (4, 1, 2, 2, 'Conferindo comprovantes.', '2026-09-06 09:00:00'),
    (4, 2, 3, 2, 'Reembolso aprovado e agendado.', '2026-09-07 11:00:00'),
    (5, NULL, 1, 1, 'Solicitação criada.', '2026-09-08 08:00:00'),
    (5, 1, 2, 2, 'Técnico de manutenção acionado.', '2026-09-08 10:00:00'),
    (5, 2, 3, 2, 'Dreno desobstruído.', '2026-09-10 17:30:00'),
    (6, NULL, 1, 3, 'Solicitação criada.', '2026-09-10 13:00:00'),
    (6, 1, 2, 2, NULL, '2026-09-10 14:00:00'),
    (6, 2, 3, 2, 'Acesso de leitura liberado.', '2026-09-10 16:45:00'),

    -- Em atendimento: criação e início do atendimento
    (7, NULL, 1, 1, 'Solicitação criada.', '2026-09-15 09:00:00'),
    (7, 1, 2, 2, 'Verificando configuração de rede.', '2026-09-15 10:30:00'),
    (8, NULL, 1, 3, 'Solicitação criada.', '2026-09-18 11:00:00'),
    (8, 1, 2, 2, 'Aguardando comprovante da nova conta.', '2026-09-19 08:30:00'),
    (9, NULL, 1, 1, 'Solicitação criada.', '2026-09-21 07:50:00'),
    (9, 1, 2, 2, 'Lâmpadas solicitadas ao almoxarifado.', '2026-09-22 09:00:00'),
    (10, NULL, 1, 3, 'Solicitação criada.', '2026-09-24 15:00:00'),
    (10, 1, 2, 2, 'Cotação em andamento.', '2026-09-25 10:00:00'),
    (11, NULL, 1, 1, 'Solicitação criada.', '2026-09-28 10:20:00'),
    (11, 1, 2, 2, 'Conferindo lançamentos da folha.', '2026-09-29 14:00:00'),

    -- Abertas: só a criação
    (12, NULL, 1, 1, 'Solicitação criada.', '2026-09-29 09:10:00'),
    (13, NULL, 1, 3, 'Solicitação criada.', '2026-09-29 14:30:00'),
    (14, NULL, 1, 1, 'Solicitação criada.', '2026-09-30 08:40:00'),
    (15, NULL, 1, 3, 'Solicitação criada.', '2026-09-30 10:15:00'),
    (16, NULL, 1, 1, 'Solicitação criada.', '2026-09-30 16:00:00'),
    (17, NULL, 1, 3, 'Solicitação criada.', '2026-10-01 08:05:00'),
    (18, NULL, 1, 1, 'Solicitação criada.', '2026-10-01 08:30:00');