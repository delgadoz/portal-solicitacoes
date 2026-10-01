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