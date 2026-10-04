# Memorial Técnico de Desenvolvimento

**Projeto:** Portal de Solicitações Internas
**Autor:** Rodrigo Delgado
**Contexto:** mini-projeto Full Stack — processo seletivo para Desenvolvedor Jr da Bit Soluções.

Este documento registra as tecnologias escolhidas, as decisões de arquitetura e o raciocínio por trás delas,
além de uma análise crítica da solução entregue. As decisões foram anotadas ao longo do desenvolvimento em
[`docs/decisoes.md`](decisoes.md); a estrutura do banco está em [`docs/DICIONARIO_DE_DADOS.md`](DICIONARIO_DE_DADOS.md).

---

## 1. Visão geral

A solução é uma aplicação web em três partes:

- **Backend:** uma API REST em PHP que concentra todas as regras de negócio, a segurança e o acesso aos dados;
- **Frontend:** páginas HTML com JavaScript que consomem essa API, sem acesso direto ao banco;
- **Banco de dados:** MySQL, com scripts de criação, dados de referência e dados de demonstração.

O princípio que guiou o projeto foi: **toda regra é garantida no servidor**. O frontend esconde botões e valida
formulários para melhorar a experiência, mas quem decide o que cada usuário pode fazer é sempre a API.

---

## 2. Tecnologias utilizadas e justificativa técnica

| Tecnologia | Uso |
| --- | --- |
| PHP 8.2 | Linguagem do backend |
| MySQL 8.0 | Banco de dados relacional |
| PDO (extensão nativa do PHP) | Acesso ao banco com prepared statements |
| Composer + autoload PSR-4 | Dependências e carregamento automático de classes |
| vlucas/phpdotenv | Configuração por variáveis de ambiente |
| Sessões nativas do PHP | Autenticação |
| Tabler 1.6 (sobre Bootstrap 5) | Interface |
| JavaScript (ES2020+, `fetch`) | Consumo da API e interatividade |
| Chart.js 4 | Gráfico do dashboard |
| Tabler Icons | Ícones |
| PHP CodeSniffer (PSR-12) | Padronização do código |
| PHPUnit | Instalado para testes automatizados (ver Análise Crítica) |
| Git, GitHub, GitHub Projects | Versionamento, revisão via Pull Requests e gestão do backlog |

### PHP 8.2, sem framework

- **Motivo:** é a linguagem em que tenho mais experiência com desenvolvimento Fullstack. Optei por não usar
  framework porque o desafio avalia arquitetura, organização em camadas e boas práticas; implementar o roteador,
  o tratamento de erros e o middleware de autenticação deixa explícito que entendo o que um framework faz.
- **Benefícios:** recursos modernos tornam o código mais seguro e legível — `enum` (perfil e status),
  propriedades `readonly`, tipos estritos (`declare(strict_types=1)`), o tipo de retorno `never` e a expressão `match`.
- **Alternativas:** Laravel ou Slim trariam roteamento, validação e ORM prontos e seriam a escolha natural em um
  projeto maior. Para o escopo deste desafio, a versão sem framework tem menos dependências e nenhuma "mágica"
  escondida.
- **Impacto:** a estrutura segue os mesmos conceitos dos frameworks (front controller, controllers, services,
  repositories, middleware), então uma migração futura para Laravel ou Slim seria direta.

### MySQL 8.0

- **Motivo:** banco relacional amplamente usado, com integridade referencial, transações e o mesmo ecossistema
  do restante da stack.
- **Benefícios:** os dados do domínio são naturalmente relacionais (solicitações, categorias, status, usuários,
  histórico). Chaves estrangeiras, `ENUM`, `utf8mb4` e transações InnoDB garantem a consistência no próprio banco.
- **Alternativas:** PostgreSQL atenderia igualmente bem; um banco NoSQL não traria vantagem para dados com
  relacionamentos fixos e regras de integridade.
- **Impacto:** índices nas colunas usadas em filtros e no rate limiting mantêm as consultas rápidas com o
  crescimento dos dados.

### PDO com prepared statements nativos

- **Motivo e benefício:** protege contra SQL injection. Com `PDO::ATTR_EMULATE_PREPARES = false`, o próprio MySQL
  separa o comando dos dados, e os tipos (inteiros, textos) chegam corretos.
- **Alternativas:** um ORM (Eloquent, Doctrine) reduziria SQL manual, mas esconderia as consultas; aqui o SQL
  explícito facilita a revisão das regras de visibilidade e das consultas do dashboard.

### Composer, PSR-4 e phpdotenv

- **Composer + PSR-4:** o namespace espelha as pastas (`App\Services\AuthService` → `src/Services/AuthService.php`)
  e as classes são carregadas automaticamente, sem `require` espalhado. O `composer.lock` é versionado para que
  todos os ambientes instalem as mesmas versões.
- **phpdotenv:** credenciais e parâmetros (banco, fuso, limites do rate limiting) ficam fora do código, em um `.env`
  que nunca vai para o repositório; o `.env.example` documenta as variáveis.

### Sessões do PHP para autenticação

- **Motivo:** frontend e API são servidos pela mesma origem e há um único tipo de cliente (o navegador).
- **Benefícios:** a sessão é revogável no logout, expira por inatividade e o identificador fica em um cookie
  `HttpOnly`, inacessível ao JavaScript.
- **Alternativas:** JWT é mais adequado quando há vários clientes (aplicativos móveis, outros serviços) ou APIs
  distribuídas. Aqui ele traria complexidade (armazenamento seguro no navegador, revogação) sem benefício.

### Tabler (Bootstrap 5), JavaScript, Chart.js

- **Motivo:** o Tabler é um tema de painel administrativo construído sobre o Bootstrap 5; mantém a stack proposta
  (Bootstrap) e oferece componentes prontos para dashboard, tabelas, formulários e tema escuro.
- **Benefícios:** interface consistente, responsiva e acessível com pouco CSS próprio; a cor da identidade visual
  (#293E93) é aplicada por variáveis CSS.
- **JavaScript sem framework:** as telas são poucas e simples; `fetch` com um cliente HTTP centralizado (`api.js`)
  atende sem build ou dependências. Angular ou React fariam sentido com mais telas e estado compartilhado.
- **Chart.js:** biblioteca leve e consolidada para o gráfico por categoria.

### Qualidade e processo

- **PHP CodeSniffer (PSR-12):** padroniza formatação e nomenclatura (`composer lint`).
- **Git e GitHub:** branch por funcionalidade, Conventional Commits e Pull Requests vinculados às issues
  (`Closes #N`), mantendo o histórico rastreável.
- **GitHub Projects:** backlog em issues organizado em uma sprint (Todo / In Progress / Done).

---

## 3. Justificativa conceitual

### 3.1 Estrutura geral

```
Navegador ──(JSON + cookie de sessão)──► API PHP ──(PDO)──► MySQL
   frontend/                              backend/            database/
```

Frontend e API são servidos pela **mesma origem** (`composer serve`): requisições que começam com `/api` vão para
o PHP; as demais são arquivos estáticos do frontend. Isso dispensa configuração de CORS e permite usar o cookie de
sessão com `SameSite=Strict`.

### 3.2 Organização das camadas (backend)

| Camada | Responsabilidade | Exemplo |
| --- | --- | --- |
| `public/index.php` | Ponto de entrada único: autoload, `.env`, fuso, cabeçalhos de segurança, tratamento de erros | — |
| `Core/Router` | Associa método + caminho a um handler; extrai `{id}`; diferencia 404 de 405 | — |
| `Core/Auth` (middleware) | Exige login, valida CSRF e, quando indicado, o perfil | `Auth::protect(fn, Perfil::Atendente)` |
| Controllers | Leem a requisição e montam a resposta HTTP | `SolicitacaoController` |
| Services | Regras de negócio, permissões e transações | `SolicitacaoService::alterarStatus()` |
| Validators | Validam e normalizam a entrada | `SolicitacaoValidator`, `FiltrosSolicitacao`, `SenhaValidator` |
| Repositories | SQL e acesso a dados | `SolicitacaoRepository` |

O Controller nunca executa SQL e o Repository nunca decide regra. As dependências são passadas pelo construtor
(**injeção de dependência**), o que deixa cada classe com uma responsabilidade e facilita testes.

Erros são **exceções** com o código HTTP correspondente (`ValidationException` → 422, `ConflictException` → 409,
etc.). Um único `ErrorHandler` converte qualquer exceção em JSON padronizado; erros inesperados vão para o log e
o usuário recebe uma mensagem genérica, sem detalhes internos.

### 3.3 Modelagem de dados

- **Tabelas:** `usuarios`, `categorias`, `status`, `solicitacoes`, `solicitacao_historico` e `login_tentativas`.
- **Status e categorias em tabelas próprias:** o status tem atributo (`ordem`), aparece em filtros e indicadores e
  pode crescer; a categoria guarda o `sla_horas`, base dos indicadores de prazo.
- **Perfil como `ENUM`:** apenas dois valores fixos, sem atributos próprios.
- **Integridade:** chaves estrangeiras com `ON DELETE RESTRICT`; usuários são desativados (`ativo`) em vez de
  excluídos. A única exceção é o histórico, removido em cascata com a solicitação — permitido porque apenas
  solicitações Abertas podem ser excluídas, e elas só têm o registro de criação.
- **Histórico como trilha de auditoria:** cada mudança de status, inclusive a criação, vira uma linha com autor,
  data e observação.
- **Tipos e índices:** `utf8mb4`, `DATETIME` (sistema de um único fuso), tamanhos alinhados à validação
  (`titulo VARCHAR(150)`), índice em `criado_em` para o filtro por período e índice composto
  `(usuario_informado, ip, criado_em)` para o rate limiting.

### 3.4 Regras de negócio e padrões de projeto

| Padrão / técnica | Onde | Por quê |
| --- | --- | --- |
| Front Controller | `public/index.php` | Uma entrada para todas as requisições |
| Service Layer + Repository | `Services/`, `Repositories/` | Separar regra de negócio de acesso a dados |
| Middleware (decorator) | `Auth::protect()` | Autenticação, CSRF e perfil declarados na rota, em um só lugar |
| Máquina de estados | `StatusSolicitacao::proximo()` | Aberto → Em Atendimento → Concluído; sem pular, voltar ou reabrir |
| Controle otimista de concorrência | `UPDATE ... WHERE status_id = :status_lido` | Dois atendentes simultâneos não geram transição duplicada |
| Transações | Criação e mudança de status | Solicitação e histórico gravados juntos, ou nenhum dos dois |
| Whitelist | Ordenação da listagem | Nomes de coluna não podem ser parâmetros de prepared statement |

Regras de negócio implementadas no servidor:

- Data de criação, solicitante e status inicial são definidos pela API, nunca aceitos do cliente.
- O solicitante só encontra as próprias solicitações: o filtro é aplicado na consulta SQL e a solicitação de outra
  pessoa responde **404**, sem revelar que existe.
- Edição e exclusão: somente pelo solicitante dono e somente com status Aberto (caso contrário, 403 ou 409).
- Mudança de status: somente pelo atendente, apenas para a próxima etapa. O cliente informa o **status de
  destino**; assim, uma requisição repetida (clique duplo) é rejeitada em vez de avançar duas etapas.

### 3.5 Estratégia de autenticação e segurança

- Senhas com `password_hash` (bcrypt, salt embutido); nem os dados de demonstração guardam senha em texto puro.
- Cookie de sessão `HttpOnly` e `SameSite=Strict`; `session_regenerate_id(true)` no login (contra fixação de
  sessão); `use_strict_mode`; expiração por inatividade.
- Token CSRF exigido em `POST`, `PUT`, `PATCH` e `DELETE`, comparado com `hash_equals`.
- **Rate limiting:** cada tentativa de login é registrada; com 5 falhas em 15 minutos para o mesmo usuário e IP,
  a API responde 429 **antes** de conferir a senha.
- **Alteração de senha** (`PUT /api/me/senha`): exige a senha atual, aplica a política de senha forte (mínimo de 8
  caracteres, com 1 maiúscula, 1 número e 1 caractere especial; máximo de 72 bytes, limite do bcrypt) e, em caso de
  sucesso, encerra a sessão para que o usuário entre de novo com a nova senha. Senha atual errada responde 422 no
  campo (o usuário está logado; 401 o mandaria para o login) e conta no mesmo limite de tentativas do login, para
  que uma sessão alheia não sirva para testar senhas.
- Mensagem única "Usuário ou senha inválidos" e verificação de senha mesmo para usuários inexistentes (hash
  fictício), para que nem a mensagem nem o tempo de resposta revelem quais usuários existem.
- Cabeçalhos de segurança (`X-Content-Type-Options`, `X-Frame-Options`, `Content-Security-Policy`) e remoção do
  `X-Powered-By`.
- No frontend, todo texto vindo da API é escapado antes de ser inserido no HTML (proteção contra XSS).

### 3.6 Comunicação entre frontend e backend

- JSON em ambas as direções, com formato padronizado:
  sucesso `{"data": ...}` (listas com `"meta"` de paginação) e erro `{"error": {"code", "message", "fields"}}`.
- Códigos HTTP com significado: 200, 201, 204, 400, 401, 403, 404, 405, 409, 422, 429 e 500.
- O módulo `frontend/assets/js/api.js` centraliza as chamadas: envia o token CSRF, redireciona ao login no 401 e
  entrega os erros por campo para os formulários exibirem junto a cada input.

### 3.7 Organização do código-fonte

- `backend/` — API (`public/`, `routes/`, `src/` por camada);
- `frontend/` — uma página HTML por tela e um script por página, mais `api.js` e `app.js` compartilhados
  (autenticação, navbar por perfil, tema, notificações, formatação);
- `database/` — `migrations/` (estrutura e dados essenciais), `seeds/` (demonstração) e `setup.sql` (tudo em um passo);
- `docs/` — memorial, dicionário de dados, registro de decisões e prints.

Nomenclatura em português para o domínio (solicitação, atendente, categoria), por ser a linguagem do negócio, e
padrão PSR-12 verificado automaticamente.

### 3.8 Processo de desenvolvimento

O trabalho foi organizado como uma sprint de cinco dias no GitHub Projects, com uma issue por funcionalidade.
Cada funcionalidade foi desenvolvida em uma branch própria e integrada à `main` por Pull Request, com descrição do
que foi feito e de como testar.

Usei um assistente de IA como par de programação: para discutir alternativas, revisar meu código e acelerar
partes repetitivas. As decisões de modelagem e arquitetura foram tomadas e justificadas por mim (registradas em
`docs/decisoes.md`), implementei pessoalmente regras centrais como o rate limiting, as permissões de edição e a
máquina de estados, e cada funcionalidade foi testada manualmente pela API (curl) e pela interface antes do merge.

---

## 4. Análise crítica

### 4.1 Limitações da solução

- **Sem testes automatizados:** o PHPUnit está instalado, mas a validação foi feita com testes manuais
  roteirizados. Os repositórios são classes concretas acopladas ao PDO, o que dificulta simular o banco nos testes
  de serviço.
- **Sem cadastro de usuários pela interface:** os usuários vêm do script de demonstração; não há perfil administrador.
- **Troca de senha não encerra outras sessões:** a sessão atual é encerrada, mas uma sessão aberta em outro
  navegador continua válida até expirar. Resolver exigiria guardar a data da última troca de senha e compará-la a
  cada requisição (ou manter as sessões no banco).
- **Rate limiting no banco:** funciona bem para o volume esperado, mas cada tentativa gera uma escrita.
- **Busca por título com `LIKE '%termo%'`:** não aproveita índice; com grande volume, seria lenta.
- **Dependência de CDN no frontend:** sem internet, a interface perde estilos e o gráfico.
- **Servidor embutido do PHP:** adequado para avaliação e desenvolvimento, não para produção. Por ele, arquivos
  `.js` são entregues sem `charset` no cabeçalho (o navegador os interpreta corretamente pelo HTML, mas a visualização
  direta do arquivo pode exibir acentos incorretamente).

### 4.2 Melhorias futuras

- Testes unitários da máquina de estados, dos validadores e dos serviços (com interfaces para os repositórios e
  banco de testes) e testes de integração da API.
- Docker Compose (aplicação + MySQL) para subir o ambiente com um comando, e CI no GitHub Actions executando
  lint e testes a cada Pull Request.
- Notificações por e-mail ao solicitante a cada mudança de status.
- Anexos (prints, documentos) nas solicitações e comentários entre solicitante e atendente.
- Escalonamento automático e alertas para solicitações próximas do vencimento do SLA.
- Cadastro de usuários, categorias e SLAs pela interface, com perfil administrador.
- Busca textual com índice `FULLTEXT`.

### 4.3 Requisitos que poderiam ser aperfeiçoados

- **Reabertura:** hoje uma solicitação concluída é definitiva. Em um cenário real, um prazo curto para o
  solicitante reabrir (ou avaliar o atendimento) melhoraria o controle de qualidade.
- **Atribuição e visibilidade:** o atendente que inicia o atendimento já é registrado (atendente_id), mas todo atendente vê todas as solicitações e não há atribuição prévia. Evolução: cada atendente veria a fila de abertas ainda não assumidas mais as suas, com atribuição por categoria ou equipe, e um perfil Administrador veria todas, redistribuiria solicitações e acompanharia o dashboard geral.
- **SLA por prioridade:** o prazo depende só da categoria; uma prioridade (baixa, média, alta) tornaria o SLA mais fiel.

### 4.4 O que seria diferente em produção

- HTTPS obrigatório (o cookie de sessão já é marcado como `Secure` automaticamente quando há HTTPS).
- Servidor web (Nginx ou Apache) com PHP-FPM, codificação UTF-8 padrão e cache de arquivos estáticos;
  dependências do frontend servidas localmente, sem CDN.
- `APP_ENV=prod`, usuário do banco com permissões mínimas (sem `root`) e segredos fora do repositório.
- Sessões e rate limiting em Redis, permitindo vários servidores atrás de um balanceador de carga.
- Logs centralizados e monitoramento de erros; backups automáticos do banco.
- Migrations versionadas com ferramenta própria (Phinx ou as do framework) e pipeline de CI/CD.
- Integração com a autenticação corporativa (LDAP/Active Directory ou SSO), evitando senhas próprias do sistema.
