# Registro de decisões

## Fase 0 — Preparação

- **API REST separada do frontend.** Backend retorna só JSON; frontend consome via fetch. Separa responsabilidades e atende o requisito de "consumo da API".
- **Composer com autoload PSR-4 (`App\` → `src/`).** Padrão de mercado; elimina `require_once` manuais e facilita os testes.
- **composer.lock versionado.** Garante as mesmas versões das dependências em todos os ambientes (builds reproduzíveis).
- **Dependências separadas em `require` e `require-dev`.** PHPUnit e phpcs só existem em desenvolvimento; produção usa `composer install --no-dev`.
- **Configuração via `.env` (phpdotenv).** Credenciais fora do código; `.env.example` documenta as variáveis necessárias.
- **PSR-12 verificado com PHP CodeSniffer.** Padroniza formatação e nomenclatura de forma automática.
- **Conventional Commits, branches por funcionalidade e PRs com "Closes #N".** Histórico legível e rastreável entre issue, código e entrega.
- **GitHub Projects com issues e labels.** Backlog organizado em uma sprint, aplicando Scrum na prática.
- **Premissa: atendente não cria solicitações.**

## Fase 1 — Banco de dados

- **InnoDB, utf8mb4 e utf8mb4_unicode_ci.** InnoDB suporta FKs e transações; utf8mb4 aceita acentos e emojis (o `utf8` do MySQL não aceita emojis); collation `_ci` permite busca sem diferenciar maiúsculas.
- **Ids `INT UNSIGNED AUTO_INCREMENT`.** Ids nunca são negativos; UNSIGNED dobra a faixa positiva.
- **Categorias e status em tabelas próprias; perfil como ENUM.** Status tem atributo próprio (`ordem`, base da máquina de estados) e aparece em filtros e no dashboard; perfil é um rótulo fixo com dois valores. Trade-off: um novo perfil exigiria `ALTER TABLE`.
- **`DATETIME` em vez de `TIMESTAMP`.** Sistema de um único fuso; evita conversões automáticas e o limite do ano 2038. PHP e MySQL configurados no mesmo fuso (America/Fortaleza).
- **Tamanhos alinhados à validação.** `titulo VARCHAR(150)` igual ao limite do backend; `descricao TEXT` por ter tamanho variável; `observacao VARCHAR(500)` para reforçar o limite no próprio banco.
- **`solicitante_id NOT NULL`; `atendente_id` e `concluido_em` NULL.** Toda solicitação nasce com dono; atendente e conclusão só existem em etapas posteriores.
- **`ON DELETE RESTRICT` como padrão nas FKs.** Impede apagar categorias, status ou usuários com registros vinculados; usuários são desativados pela coluna `ativo`, preservando o histórico.
- **`sla_horas` por categoria.** Permite calcular prazo, solicitações vencidas e % dentro do SLA no dashboard.
- **Índice em `solicitacoes.criado_em`.** Acelera o filtro por período. Índices das FKs são criados automaticamente pelo InnoDB. Limitação conhecida: busca por título com `LIKE '%texto%'` não usa índice.
- **Histórico registra também a criação.** A linha do tempo começa em "nada → Aberto"; por isso `status_anterior_id` é NULL e a coluna de autor se chama `usuario_id` (serve para solicitante e atendente).
- **`ON DELETE CASCADE` no histórico em relação à solicitação.** Como só solicitações Abertas podem ser excluídas, o CASCADE só apaga o registro de criação, nunca um atendimento real. A regra de negócio protege o histórico relevante.
- **`login_tentativas` com uma linha por tentativa, sem contador.** A janela de 15 minutos funciona por contagem (`COUNT` + intervalo de data), sem lógica de reset, sem concorrência em UPDATE, e mantém auditoria completa.
- **`login_tentativas` sem FK para usuarios.** Registra o texto digitado, incluindo usuários inexistentes, que também precisam ser contados e bloqueados.
- **Índice composto (usuario_informado, ip, criado_em).** Colunas de igualdade primeiro, intervalo por último: o padrão que torna a consulta do rate limiting rápida.
- **`ip VARCHAR(45)`.** Comporta endereços IPv6.
- **Migrações numeradas, com dados de referência separados.** Estrutura (001, 003) separada dos dados que o sistema precisa para funcionar (002); a ordem de execução fica explícita.
- **Fim de linha LF fixado com `.gitattributes`.** Evita que scripts com CRLF (Windows) quebrem nos containers Linux.
- **Seeds separados das migrações.** Migrações criam a estrutura e os dados essenciais; seeds trazem dados de demonstração, que não existiriam em produção.
- **Senhas do seed já em hash bcrypt.** Nem os dados de demonstração guardam senha em texto puro; a credencial de teste fica documentada no README.
- **Seed de solicitações com ids explícitos e histórico coerente.** Garante que as linhas do histórico apontem para as solicitações certas e que os dados respeitem as regras de status e SLA.
- **Dados de demonstração cobrindo todos os cenários.** Os três status, solicitações dentro e fora do SLA e os dois solicitantes, para demonstrar filtros, indicadores e isolamento entre usuários.

## Fase 2 — Autenticação

- **Sessão PHP em vez de JWT.** Frontend e API no mesmo domínio; sessão é simples, revogável no logout e não expõe token ao JavaScript. JWT faria sentido com vários clientes ou serviços.
- **Cookie HttpOnly + SameSite=Strict.** HttpOnly impede leitura via JavaScript (mitiga roubo por XSS); SameSite=Strict impede envio a partir de outros sites (mitiga CSRF).
- **`session_regenerate_id(true)` no login.** Impede fixação de sessão; `use_strict_mode` rejeita IDs não criados pelo servidor.
- **Expiração por inatividade (30 min, configurável).**
- **Token CSRF em métodos de escrita, comparado com `hash_equals`.** Segunda camada além do SameSite; comparação em tempo constante.
- **Rate limiting por usuário + IP antes de conferir a senha.** Um atacante bloqueado não consegue mais testar senhas; limites configuráveis no `.env`.
- **Mensagem única "Usuário ou senha inválidos" e hash fictício.** Não revela se o usuário existe, nem pela mensagem nem pelo tempo de resposta.
- **Middleware como decorator (`Auth::protect`).** Envolve o handler da rota; login, CSRF e perfil ficam em um só lugar, e a rota declara o que exige.
- **Perfil como enum do PHP 8.2.** Valores possíveis garantidos pela linguagem, sem strings soltas no código.
- **Arquitetura em camadas: Controller → Service → Repository.** Controller valida entrada e responde; Service tem a regra; Repository tem o SQL.
- **Cabeçalhos de segurança e remoção do `X-Powered-By`.**