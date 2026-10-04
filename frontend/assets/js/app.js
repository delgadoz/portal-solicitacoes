/**
 * Funções compartilhadas por todas as páginas: autenticação, layout (navbar), tema,
 * formatação, escape de HTML e notificações.
 */
const App = (() => {
    let usuarioAtual = null;

    const STATUS = {
        1: { nome: 'Aberto', classe: 'bg-blue-lt' },
        2: { nome: 'Em Atendimento', classe: 'bg-yellow-lt' },
        3: { nome: 'Concluído', classe: 'bg-green-lt' },
    };

    /* ---------- Segurança: todo texto vindo da API passa por aqui antes de virar HTML ---------- */
    function esc(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    /* ---------- Tema claro/escuro ---------- */
    function temaPreferido() {
        try {
            const salvo = localStorage.getItem('tema');
            if (salvo === 'light' || salvo === 'dark') {
                return salvo;
            }
        } catch {
            // localStorage indisponível: segue com o tema do sistema
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function aplicarTema(tema) {
        document.documentElement.setAttribute('data-bs-theme', tema);
    }

    function alternarTema() {
        const novo = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        aplicarTema(novo);
        try {
            localStorage.setItem('tema', novo);
        } catch {
            // sem persistência; o tema vale só nesta página
        }
        atualizarBotaoTema();
    }

    function atualizarBotaoTema() {
        const escuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        document.querySelectorAll('[data-acao="tema"]').forEach((botao) => {
            botao.innerHTML = `<i class="ti ti-${escuro ? 'sun' : 'moon'} fs-2"></i>`;
            botao.title = escuro ? 'Usar tema claro' : 'Usar tema escuro';
            botao.setAttribute('aria-label', botao.title);
        });
    }

    aplicarTema(temaPreferido());

    /* ---------- Autenticação ---------- */
    async function iniciar(paginaAtiva) {
        document.body.classList.add('carregando');
        const { data } = await Api.get('/api/me'); // 401 → api.js redireciona para o login
        usuarioAtual = data.usuario;
        Api.setCsrfToken(data.csrf_token);
        renderizarCabecalho(paginaAtiva);
        document.body.classList.remove('carregando');
        return usuarioAtual;
    }

    function isAtendente() {
        return usuarioAtual?.perfil === 'ATENDENTE';
    }

    async function sair() {
        try {
            await Api.post('/api/logout');
        } finally {
            location.href = 'login.html';
        }
    }

    /* ---------- Layout: navbar horizontal ---------- */
    function renderizarCabecalho(paginaAtiva) {
        const destino = document.getElementById('app-header');
        if (!destino) {
            return;
        }

        const itens = [
            { id: 'dashboard', href: 'index.html', icone: 'layout-dashboard', texto: 'Dashboard' },
            {
                id: 'solicitacoes',
                href: 'solicitacoes.html',
                icone: 'list-details',
                texto: isAtendente() ? 'Todas as solicitações' : 'Minhas solicitações',
            },
        ];
        if (!isAtendente()) {
            itens.push({ id: 'nova', href: 'solicitacao-form.html', icone: 'plus', texto: 'Nova solicitação' });
        }

        const iniciais = usuarioAtual.nome
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((parte) => parte[0].toUpperCase())
            .join('');

        destino.innerHTML = `
            <header class="navbar navbar-expand-md d-print-none">
                <div class="container-xl">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbar-menu" aria-controls="navbar-menu"
                            aria-expanded="false" aria-label="Abrir menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a href="index.html" class="navbar-brand navbar-brand-autodark pe-0 pe-md-3">
                        <span class="brand-mark"><i class="ti ti-ticket"></i></span>
                        <span class="d-none d-sm-inline">Portal de Solicitações</span>
                    </a>
                    <div class="navbar-nav flex-row order-md-last align-items-center gap-1">
                        <button type="button" class="nav-link px-2" data-acao="tema"></button>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown"
                               aria-label="Menu do usuário">
                                <span class="avatar avatar-sm bg-primary-lt">${esc(iniciais)}</span>
                                <div class="d-none d-xl-block ps-2">
                                    <div>${esc(usuarioAtual.nome)}</div>
                                    <div class="mt-1 small text-secondary">
                                        ${isAtendente() ? 'Atendente' : 'Solicitante'}
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <span class="dropdown-header">${esc(usuarioAtual.usuario)}</span>
                                <button type="button" class="dropdown-item" data-acao="alterar-senha">
                                    <i class="ti ti-key me-2"></i>Alterar senha
                                </button>
                                <div class="dropdown-divider"></div>
                                <button type="button" class="dropdown-item" data-acao="sair">
                                    <i class="ti ti-logout me-2"></i>Sair
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <header class="navbar-expand-md">
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <div class="navbar">
                        <div class="container-xl">
                            <ul class="navbar-nav">
                                ${itens.map((item) => `
                                    <li class="nav-item ${item.id === paginaAtiva ? 'active' : ''}">
                                        <a class="nav-link" href="${item.href}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-${item.icone} fs-2"></i>
                                            </span>
                                            <span class="nav-link-title">${esc(item.texto)}</span>
                                        </a>
                                    </li>`).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            </header>`;

        destino.querySelector('[data-acao="sair"]').addEventListener('click', sair);
        destino.querySelector('[data-acao="alterar-senha"]').addEventListener('click', abrirAlterarSenha);
        destino.querySelector('[data-acao="tema"]').addEventListener('click', alternarTema);
        atualizarBotaoTema();
    }

    /* ---------- Alteração de senha (modal aberto pelo menu do usuário) ---------- */

    // Mesma política do backend (SenhaValidator); aqui serve só para orientar enquanto o usuário digita
    const REQUISITOS_SENHA = [
        { id: 'tamanho', texto: 'Pelo menos 8 caracteres', teste: (s) => [...s].length >= 8 },
        { id: 'maiuscula', texto: '1 letra maiúscula', teste: (s) => /\p{Lu}/u.test(s) },
        { id: 'numero', texto: '1 número', teste: (s) => /\d/.test(s) },
        { id: 'especial', texto: '1 caractere especial (ex.: ! @ # $ %)', teste: (s) => /[^\p{L}\p{N}\s]/u.test(s) },
    ];

    function campoSenha(nome, rotulo, autocomplete) {
        return `
            <div class="mb-3">
                <label class="form-label" for="campo-${nome}">${rotulo}</label>
                <input type="password" class="form-control" id="campo-${nome}" name="${nome}"
                       autocomplete="${autocomplete}" maxlength="72" required>
            </div>`;
    }

    function abrirAlterarSenha() {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="modal modal-blur fade" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="titulo-senha">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title" id="titulo-senha">Alterar senha</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            ${campoSenha('senha_atual', 'Senha atual', 'current-password')}
                            ${campoSenha('nova_senha', 'Nova senha', 'new-password')}
                            <ul class="list-unstyled small mb-3 requisitos-senha">
                                ${REQUISITOS_SENHA.map((r) => `
                                    <li data-requisito="${r.id}"><i class="ti ti-circle me-1"></i>${r.texto}</li>`).join('')}
                            </ul>
                            ${campoSenha('confirmacao', 'Confirme a nova senha', 'new-password')}
                            <div class="alert alert-info mb-0">
                                <div class="d-flex">
                                    <i class="ti ti-info-circle alert-icon"></i>
                                    <div>Após alterar, você será desconectado e deverá entrar com a nova senha.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>Alterar senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
        const elemento = wrapper.firstElementChild;
        document.body.appendChild(elemento);

        const form = elemento.querySelector('form');
        const modal = new tabler.bootstrap.Modal(elemento);

        form.nova_senha.addEventListener('input', () => {
            REQUISITOS_SENHA.forEach((r) => {
                const ok = r.teste(form.nova_senha.value);
                const item = form.querySelector(`[data-requisito="${r.id}"]`);
                item.classList.toggle('text-success', ok);
                item.querySelector('i').className = `ti ti-${ok ? 'circle-check' : 'circle'} me-1`;
            });
        });

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            limparErros(form);

            const dados = {
                senha_atual: form.senha_atual.value,
                nova_senha: form.nova_senha.value,
                confirmacao: form.confirmacao.value,
            };

            await comBotaoOcupado(form.querySelector('[type="submit"]'), async () => {
                try {
                    await Api.put('/api/me/senha', dados);
                    // O backend já encerrou a sessão: volta ao login com o aviso
                    notificarAposRedirecionar('Senha alterada com sucesso. Entre com a nova senha.');
                    location.href = 'login.html';
                } catch (erro) {
                    if (erro.status === 422) {
                        mostrarErros(form, erro.fields);
                    } else {
                        notificar(erro.message, 'danger');
                    }
                }
            });
        });

        elemento.addEventListener('shown.bs.modal', () => form.senha_atual.focus());
        elemento.addEventListener('hidden.bs.modal', () => elemento.remove());
        modal.show();
    }

    /* ---------- Formatação ---------- */
    function formatarData(valor, comHora = true) {
        if (!valor) {
            return '—';
        }
        // "2026-09-15 10:30:00" → "15/09/2026 10:30" (sem converter fuso: o servidor já está no horário local)
        const [data, hora = ''] = String(valor).split(' ');
        const [ano, mes, dia] = data.split('-');
        const texto = `${dia}/${mes}/${ano}`;
        return comHora && hora ? `${texto} ${hora.slice(0, 5)}` : texto;
    }

    function badgeStatus(statusId, nome) {
        const info = STATUS[statusId] ?? { nome, classe: 'bg-secondary-lt' };
        return `<span class="badge ${info.classe}">${esc(nome ?? info.nome)}</span>`;
    }

    /* ---------- Notificações ---------- */
    function notificar(mensagem, tipo = 'success') {
        let container = document.querySelector('.toast-container-portal');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container-portal';
            document.body.appendChild(container);
        }

        const icones = { success: 'circle-check', danger: 'alert-circle', warning: 'alert-triangle', info: 'info-circle' };
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible shadow-sm mb-0`;
        alerta.setAttribute('role', 'alert');
        alerta.innerHTML = `
            <div class="d-flex">
                <i class="ti ti-${icones[tipo] ?? 'info-circle'} alert-icon"></i>
                <div>${esc(mensagem)}</div>
            </div>
            <button type="button" class="btn-close" aria-label="Fechar"></button>`;
        alerta.querySelector('.btn-close').addEventListener('click', () => alerta.remove());
        container.appendChild(alerta);
        setTimeout(() => alerta.remove(), 5000);
    }

    /* Mensagens que sobrevivem a um redirecionamento (ex.: "Solicitação criada") */
    function notificarAposRedirecionar(mensagem, tipo = 'success') {
        try {
            sessionStorage.setItem('notificacao', JSON.stringify({ mensagem, tipo }));
        } catch {
            // sem sessionStorage: a mensagem simplesmente não aparece
        }
    }

    function exibirNotificacaoPendente() {
        try {
            const pendente = sessionStorage.getItem('notificacao');
            if (pendente) {
                sessionStorage.removeItem('notificacao');
                const { mensagem, tipo } = JSON.parse(pendente);
                notificar(mensagem, tipo);
            }
        } catch {
            // ignora
        }
    }

    /* ---------- Formulários: erros por campo vindos da API (422) ---------- */
    function limparErros(form) {
        form.querySelectorAll('.is-invalid').forEach((campo) => campo.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback[data-gerado]').forEach((el) => el.remove());
    }

    function mostrarErros(form, campos) {
        Object.entries(campos).forEach(([nome, mensagem]) => {
            const campo = form.querySelector(`[name="${nome}"]`);
            if (!campo) {
                return;
            }
            campo.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.dataset.gerado = '1';
            feedback.textContent = mensagem;

            // Campo dentro de um grupo (ex.: senha com botão de mostrar): a mensagem vai depois do grupo,
            // para não quebrar o alinhamento. Fora do grupo, o Bootstrap não a exibe sozinho, daí o d-block.
            const grupo = campo.closest('.input-group, .input-icon');
            if (grupo) {
                feedback.classList.add('d-block');
                grupo.insertAdjacentElement('afterend', feedback);
            } else {
                campo.insertAdjacentElement('afterend', feedback);
            }
        });
        form.querySelector('.is-invalid')?.focus();
    }

    /* Evita duplo envio: desabilita o botão e mostra um spinner enquanto a requisição roda */
    async function comBotaoOcupado(botao, acao) {
        const html = botao.innerHTML;
        botao.disabled = true;
        botao.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Aguarde...';
        try {
            return await acao();
        } finally {
            botao.disabled = false;
            botao.innerHTML = html;
        }
    }

    /* Confirmação em modal (Bootstrap via Tabler) */
    function confirmar({ titulo, mensagem, textoBotao = 'Confirmar', perigo = false }) {
        return new Promise((resolver) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
                <div class="modal modal-blur fade" tabindex="-1" role="dialog" aria-modal="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            ${perigo ? '<div class="modal-status bg-danger"></div>' : ''}
                            <div class="modal-body text-center py-4">
                                <i class="ti ti-${perigo ? 'alert-triangle text-danger' : 'help-circle text-primary'} mb-2"
                                   style="font-size: 2.5rem"></i>
                                <h3>${esc(titulo)}</h3>
                                <div class="text-secondary">${esc(mensagem)}</div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100 d-flex gap-2">
                                    <button type="button" class="btn w-100" data-resposta="nao">Cancelar</button>
                                    <button type="button" class="btn ${perigo ? 'btn-danger' : 'btn-primary'} w-100"
                                            data-resposta="sim">${esc(textoBotao)}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            const elemento = wrapper.firstElementChild;
            document.body.appendChild(elemento);

            const modal = new tabler.bootstrap.Modal(elemento);
            let resposta = false;
            elemento.querySelector('[data-resposta="sim"]').addEventListener('click', () => {
                resposta = true;
                modal.hide();
            });
            elemento.querySelector('[data-resposta="nao"]').addEventListener('click', () => modal.hide());
            elemento.addEventListener('hidden.bs.modal', () => {
                elemento.remove();
                resolver(resposta);
            });
            modal.show();
        });
    }

    function parametro(nome) {
        return new URLSearchParams(location.search).get(nome);
    }

    return {
        esc,
        iniciar,
        usuario: () => usuarioAtual,
        isAtendente,
        formatarData,
        badgeStatus,
        notificar,
        notificarAposRedirecionar,
        exibirNotificacaoPendente,
        limparErros,
        mostrarErros,
        comBotaoOcupado,
        confirmar,
        parametro,
        alternarTema,
        atualizarBotaoTema,
        STATUS,
    };
})();
