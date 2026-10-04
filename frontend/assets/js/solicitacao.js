(async () => {
    const usuario = await App.iniciar('solicitacoes');
    App.exibirNotificacaoPendente();

    const id = Number.parseInt(App.parametro('id') ?? '', 10);
    if (!Number.isInteger(id) || id < 1) {
        location.href = 'solicitacoes.html';
        return;
    }

    // Próximo status permitido (a regra de verdade está no backend; aqui é só para montar o botão)
    const PROXIMO = {
        1: { id: 2, texto: 'Iniciar atendimento', icone: 'player-play' },
        2: { id: 3, texto: 'Concluir solicitação', icone: 'circle-check' },
    };

    const formStatus = document.getElementById('form-status');
    const campoObservacao = document.getElementById('observacao');

    campoObservacao.addEventListener('input', () => {
        document.getElementById('contador-observacao').textContent = `${campoObservacao.value.length}/500`;
    });

    function item(titulo, valor) {
        return `
            <div class="datagrid-item">
                <div class="datagrid-title">${App.esc(titulo)}</div>
                <div class="datagrid-content">${valor}</div>
            </div>`;
    }

    function renderizar(s) {
        document.title = `#${s.id} · Portal de Solicitações`;
        document.getElementById('titulo').textContent = `#${s.id} · ${s.titulo}`;
        document.getElementById('status-atual').innerHTML = App.badgeStatus(s.status_id, s.status);

        document.getElementById('dados').innerHTML = [
            item('Categoria', App.esc(s.categoria)),
            item('Solicitante', App.esc(s.solicitante)),
            item('Abertura', App.formatarData(s.criado_em)),
            item('Atendente', s.atendente ? App.esc(s.atendente) : '<span class="text-secondary">—</span>'),
            item('Última atualização', App.formatarData(s.atualizado_em)),
            item('Conclusão', App.formatarData(s.concluido_em)),
        ].join('');

        // textContent: a descrição é exibida como texto puro, nunca interpretada como HTML
        document.getElementById('descricao').textContent = s.descricao;

        document.getElementById('historico').innerHTML = s.historico.map((h) => `
            <li>
                <span class="timeline-ponto"></span>
                <div class="fw-medium">
                    ${h.status_anterior ? `${App.esc(h.status_anterior)} → ` : ''}${App.esc(h.status_novo)}
                </div>
                <div class="text-secondary small">${App.esc(h.usuario)} · ${App.formatarData(h.criado_em)}</div>
                ${h.observacao ? `<div class="mt-1">${App.esc(h.observacao)}</div>` : ''}
            </li>`).join('');

        renderizarAcoes(s);
    }

    function renderizarAcoes(s) {
        const acoes = document.getElementById('acoes');
        acoes.innerHTML = '';
        formStatus.classList.add('d-none');

        const dono = !App.isAtendente() && s.solicitante_id === usuario.id;

        if (dono && s.status_id === 1) {
            acoes.innerHTML = `
                <a href="solicitacao-form.html?id=${s.id}" class="btn">
                    <i class="ti ti-pencil me-2"></i>Editar
                </a>
                <button type="button" class="btn btn-outline-danger" id="excluir">
                    <i class="ti ti-trash me-2"></i>Excluir
                </button>`;
            document.getElementById('excluir').addEventListener('click', (e) => excluir(s, e.currentTarget));
        }

        const proximo = PROXIMO[s.status_id];
        if (App.isAtendente() && proximo) {
            formStatus.classList.remove('d-none');
            formStatus.dataset.destino = proximo.id;
            document.getElementById('botao-status').innerHTML =
                `<i class="ti ti-${proximo.icone} me-2"></i>${proximo.texto}`;
        }
    }

    async function excluir(s, botao) {
        const confirmado = await App.confirmar({
            titulo: 'Excluir solicitação?',
            mensagem: `A solicitação #${s.id} será excluída definitivamente.`,
            textoBotao: 'Excluir',
            perigo: true,
        });
        if (!confirmado) {
            return;
        }

        await App.comBotaoOcupado(botao, async () => {
            try {
                await Api.delete(`/api/solicitacoes/${s.id}`);
                App.notificarAposRedirecionar('Solicitação excluída.');
                location.href = 'solicitacoes.html';
            } catch (erro) {
                App.notificar(erro.message, 'danger');
            }
        });
    }

    formStatus.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        App.limparErros(formStatus);

        const botao = document.getElementById('botao-status');
        await App.comBotaoOcupado(botao, async () => {
            try {
                const { data } = await Api.patch(`/api/solicitacoes/${id}/status`, {
                    status_id: Number(formStatus.dataset.destino),
                    observacao: campoObservacao.value.trim() || null,
                });
                campoObservacao.value = '';
                campoObservacao.dispatchEvent(new Event('input'));
                App.notificar(`Status alterado para "${data.status}".`);
                renderizar(data);
            } catch (erro) {
                if (erro.status === 422) {
                    App.mostrarErros(formStatus, erro.fields);
                }
                App.notificar(erro.message, erro.status === 409 ? 'warning' : 'danger');
                if (erro.status === 409) {
                    carregar(); // outra pessoa alterou: recarrega para mostrar o estado atual
                }
            }
        });
    });

    async function carregar() {
        try {
            const { data } = await Api.get(`/api/solicitacoes/${id}`);
            renderizar(data);
        } catch (erro) {
            if (erro.status === 404) {
                App.notificarAposRedirecionar('Solicitação não encontrada.', 'warning');
                location.href = 'solicitacoes.html';
                return;
            }
            App.notificar(erro.message, 'danger');
        }
    }

    carregar();
})();
