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