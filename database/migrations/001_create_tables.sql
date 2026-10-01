CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(20) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('SOLICITANTE', 'ATENDENTE') NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    sla_horas SMALLINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE status (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL UNIQUE,
    ordem TINYINT UNSIGNED NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE solicitacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    status_id INT UNSIGNED NOT NULL,
    atendente_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    concluido_em DATETIME NULL,

    CONSTRAINT fk_solicitacoes_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacoes_status
        FOREIGN KEY (status_id) REFERENCES status(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacoes_atendente
        FOREIGN KEY (atendente_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


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