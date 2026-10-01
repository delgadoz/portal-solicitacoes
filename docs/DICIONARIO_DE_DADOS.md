# Dicionário de Dados

Banco: `portal_solicitacoes` · MySQL 8.0 · InnoDB · utf8mb4 / utf8mb4_unicode_ci

## usuarios

Usuários do sistema, com o perfil que define suas permissões.

| Coluna | Tipo | Nulo | Chave | Padrão | Descrição |
| --- | --- | --- | --- | --- | --- |
| id | INT UNSIGNED | Não | PK | AUTO_INCREMENT | Identificador único do usuário. |
| nome | VARCHAR(100) | Não | | | Nome completo, exibido na interface e nas listagens. |
| usuario | VARCHAR(20) | Não | UNIQUE | | Login usado na autenticação; não pode se repetir. |
| senha_hash | VARCHAR(255) | Não | | | Hash da senha gerado por `password_hash` (bcrypt, salt embutido). A senha em texto puro nunca é armazenada. Tamanho folgado para suportar algoritmos futuros. |
| perfil | ENUM('SOLICITANTE','ATENDENTE') | Não | | | Define as permissões: SOLICITANTE cria e acompanha as próprias solicitações; ATENDENTE visualiza todas e altera o status. |
| ativo | BOOLEAN | Não | | TRUE | Indica se o usuário pode acessar o sistema. Usuários são desativados em vez de excluídos, preservando o histórico. |
| criado_em | DATETIME | Não | | CURRENT_TIMESTAMP | Data e hora de cadastro do usuário. |

## categorias

Áreas responsáveis pelo atendimento, cada uma com seu prazo de SLA.

| Coluna | Tipo | Nulo | Chave | Padrão | Descrição |
| --- | --- | --- | --- | --- | --- |
| id | INT UNSIGNED | Não | PK | AUTO_INCREMENT | Identificador único da categoria. |
| nome | VARCHAR(50) | Não | UNIQUE | | Nome da categoria (TI, RH, Compras, Financeiro, Infraestrutura). |
| sla_horas | SMALLINT UNSIGNED | Não | | | Prazo-alvo, em horas, para concluir solicitações da categoria. O prazo de cada solicitação é `criado_em + sla_horas`; base dos indicadores de SLA. |

## status

Etapas do ciclo de vida de uma solicitação.

| Coluna | Tipo | Nulo | Chave | Padrão | Descrição |
| --- | --- | --- | --- | --- | --- |
| id | INT UNSIGNED | Não | PK | AUTO_INCREMENT | Identificador único do status. |
| nome | VARCHAR(30) | Não | UNIQUE | | Nome exibido (Aberto, Em Atendimento, Concluído). |
| ordem | TINYINT UNSIGNED | Não | UNIQUE | | Posição da etapa no fluxo. A única transição permitida é para o status de `ordem` imediatamente seguinte, sem retorno. |

## solicitacoes

Demandas internas registradas pelos solicitantes.

| Coluna | Tipo | Nulo | Chave | Padrão | Descrição |
| --- | --- | --- | --- | --- | --- |
| id | INT UNSIGNED | Não | PK | AUTO_INCREMENT | Identificador único; exibido como "código" na listagem. |
| titulo | VARCHAR(150) | Não | | | Resumo da demanda. Limite igual ao validado no backend; usado na busca por texto livre. |
| descricao | TEXT | Não | | | Detalhamento da demanda, de tamanho variável. |
| categoria_id | INT UNSIGNED | Não | FK → categorias.id | | Área responsável pelo atendimento; define o SLA aplicável. |
| status_id | INT UNSIGNED | Não | FK → status.id | | Etapa atual. Sempre Aberto na criação; alterado apenas pelo atendente, seguindo o fluxo. |
| solicitante_id | INT UNSIGNED | Não | FK → usuarios.id | | Usuário que abriu a solicitação, definido automaticamente pelo servidor. Base do controle de acesso: o solicitante só visualiza as próprias. |
| atendente_id | INT UNSIGNED | Sim | FK → usuarios.id | NULL | Atendente que iniciou o atendimento. Vazio enquanto a solicitação está Aberta. |
| criado_em | DATETIME | Não | INDEX | CURRENT_TIMESTAMP | Data e hora de abertura. Indexada para o filtro por período. |
| atualizado_em | DATETIME | Não | | CURRENT_TIMESTAMP (atualiza automaticamente) | Data e hora da última alteração no registro, atualizada pelo próprio banco. |
| concluido_em | DATETIME | Sim | | NULL | Data e hora da conclusão. Vazio até o status Concluído; usado no cálculo do tempo de atendimento e do SLA. |

## solicitacao_historico

Trilha de auditoria das mudanças de status, incluindo a criação.

| Coluna | Tipo | Nulo | Chave | Padrão | Descrição |
| --- | --- | --- | --- | --- | --- |
| id | INT UNSIGNED | Não | PK | AUTO_INCREMENT | Identificador único do registro. |
| solicitacao_id | INT UNSIGNED | Não | FK → solicitacoes.id (CASCADE) | | Solicitação a que o registro pertence. Excluída a solicitação (permitido só quando Aberta), o histórico é removido junto. |
| status_anterior_id | INT UNSIGNED | Sim | FK → status.id | NULL | Status antes da mudança. Vazio no registro de criação. |
| status_novo_id | INT UNSIGNED | Não | FK → status.id | | Status após a mudança. |
| usuario_id | INT UNSIGNED | Não | FK → usuarios.id | | Quem realizou a ação: o solicitante, na criação, ou o atendente, nas mudanças de status. |
| observacao | VARCHAR(500) | Sim | | NULL | Comentário opcional sobre a mudança (ex.: o que foi feito na conclusão). |
| criado_em | DATETIME | Não | | CURRENT_TIMESTAMP | Data e hora da mudança. |

## login_tentativas

Registro de todas as tentativas de login, usado no rate limiting e na auditoria de acesso.

| Coluna | Tipo | Nulo | Chave | Padrão | Descrição |
| --- | --- | --- | --- | --- | --- |
| id | INT UNSIGNED | Não | PK | AUTO_INCREMENT | Identificador único da tentativa. |
| usuario_informado | VARCHAR(100) | Não | INDEX (composto) | | Texto digitado no campo usuário. Sem FK, para registrar também tentativas com usuários inexistentes. |
| ip | VARCHAR(45) | Não | INDEX (composto) | | Endereço IP de origem; comporta IPv6. |
| sucesso | BOOLEAN | Não | | | Indica se a tentativa autenticou. Apenas as falhas contam para o bloqueio. |
| user_agent | VARCHAR(255) | Sim | | NULL | Identificação do navegador/cliente, para auditoria. |
| criado_em | DATETIME | Não | INDEX (composto) | CURRENT_TIMESTAMP | Data e hora da tentativa. A contagem de falhas considera uma janela de tempo sobre esta coluna. |

Índice composto `(usuario_informado, ip, criado_em)`: atende a consulta do rate limiting, com as colunas de igualdade primeiro e a de intervalo por último.

## Relacionamentos

- Um usuário (solicitante) abre N solicitações; um usuário (atendente) atende N solicitações.
- Uma categoria classifica N solicitações; um status é atribuído a N solicitações.
- Uma solicitação possui N registros de histórico; excluí-la remove seu histórico (CASCADE).
- Um usuário realiza N registros de histórico.