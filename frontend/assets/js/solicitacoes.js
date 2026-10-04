(async () => {
    await App.iniciar('solicitacoes');
    App.exibirNotificacaoPendente();

    const form = document.getElementById('form-filtros');
    const corpo = document.getElementById('lista');
    const CAMPOS_FILTRO = ['q', 'data_inicio', 'data_fim', 'categoria_id', 'status_id'];

    // Estado da listagem vem da URL: filtros, página e ordenação sobrevivem ao F5 e podem ser compartilhados
    const estado = new URLSearchParams(location.search);

    document.getElementById('titulo-pagina').textContent =
        App.isAtendente() ? 'Todas as solicitações' : 'Minhas solicitações';

    if (!App.isAtendente()) {
        document.getElementById('acoes-cabecalho').innerHTML = `
            <a href="solicitacao-form.html" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Nova solicitação
            </a>`;
    }

    // Categorias do filtro
    try {
        const { data: categorias } = await Api.get('/api/categorias');
        form.categoria_id.insertAdjacentHTML('beforeend', categorias
            .map((c) => `<option value="${c.id}">${App.esc(c.nome)}</option>`).join(''));
    } catch (erro) {
        App.notificar(erro.message, 'danger');
    }

    CAMPOS_FILTRO.forEach((campo) => { form[campo].value = estado.get(campo) ?? ''; });

    function atualizarIndicadoresOrdenacao() {
        const ordenar = estado.get('ordenar') ?? 'criado_em';
        const direcao = estado.get('direcao') ?? 'desc';
        document.querySelectorAll('[data-ordenar]').forEach((botao) => {
            botao.classList.remove('asc', 'desc');
            if (botao.dataset.ordenar === ordenar) {
                botao.classList.add(direcao);
            }
        });
    }

    function renderizarLinhas(itens) {
        if (itens.length === 0) {
            corpo.innerHTML = `
                <tr><td colspan="6" class="text-center py-5">
                    <div class="text-secondary mb-2"><i class="ti ti-mood-empty" style="font-size: 2rem"></i></div>
                    <div class="text-secondary">Nenhuma solicitação encontrada com esses filtros.</div>
                </td></tr>`;
            return;
        }

        corpo.innerHTML = itens.map((s) => `
            <tr data-id="${s.id}" tabindex="0">
                <td class="text-secondary">#${s.id}</td>
                <td class="fw-medium">${App.esc(s.titulo)}</td>
                <td class="d-none d-md-table-cell">${App.esc(s.categoria)}</td>
                <td class="d-none d-md-table-cell">${App.esc(s.solicitante)}</td>
                <td class="text-secondary text-nowrap">${App.formatarData(s.criado_em)}</td>
                <td>${App.badgeStatus(s.status_id, s.status)}</td>
            </tr>`).join('');

        corpo.querySelectorAll('tr[data-id]').forEach((linha) => {
            const abrir = () => { location.href = `solicitacao.html?id=${linha.dataset.id}`; };
            linha.addEventListener('click', abrir);
            linha.addEventListener('keydown', (e) => { if (e.key === 'Enter') { abrir(); } });
        });
    }

    function renderizarPaginacao(meta) {
        const inicio = meta.total === 0 ? 0 : (meta.page - 1) * meta.per_page + 1;
        const fim = Math.min(meta.page * meta.per_page, meta.total);
        document.getElementById('resumo-paginacao').textContent =
            `Mostrando ${inicio}–${fim} de ${meta.total} solicitação(ões)`;

        const paginacao = document.getElementById('paginacao');
        if (meta.total_pages <= 1) {
            paginacao.innerHTML = '';
            return;
        }

        const item = (pagina, rotulo, desabilitado = false, ativo = false) => `
            <li class="page-item ${desabilitado ? 'disabled' : ''} ${ativo ? 'active' : ''}">
                <button type="button" class="page-link" data-pagina="${pagina}" ${desabilitado ? 'disabled' : ''}>
                    ${rotulo}
                </button>
            </li>`;

        let html = item(meta.page - 1, '<i class="ti ti-chevron-left"></i>', meta.page === 1);
        for (let p = 1; p <= meta.total_pages; p++) {
            html += item(p, p, false, p === meta.page);
        }
        html += item(meta.page + 1, '<i class="ti ti-chevron-right"></i>', meta.page === meta.total_pages);
        paginacao.innerHTML = html;

        paginacao.querySelectorAll('[data-pagina]:not([disabled])').forEach((botao) => {
            botao.addEventListener('click', () => {
                estado.set('page', botao.dataset.pagina);
                carregar();
            });
        });
    }

    async function carregar() {
        history.replaceState(null, '', `${location.pathname}?${estado.toString()}`);
        atualizarIndicadoresOrdenacao();
        App.limparErros(form);
        corpo.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4">'
            + '<span class="spinner-border spinner-border-sm me-2"></span>Carregando...</td></tr>';

        try {
            const resposta = await Api.get(`/api/solicitacoes?${estado.toString()}`);
            renderizarLinhas(resposta.data);
            renderizarPaginacao(resposta.meta);
        } catch (erro) {
            corpo.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">'
                + 'Não foi possível carregar as solicitações.</td></tr>';
            if (erro.status === 422) {
                App.mostrarErros(form, erro.fields);
            }
            App.notificar(erro.message, 'danger');
        }
    }

    form.addEventListener('submit', (evento) => {
        evento.preventDefault();
        CAMPOS_FILTRO.forEach((campo) => {
            const valor = form[campo].value.trim();
            if (valor) {
                estado.set(campo, valor);
            } else {
                estado.delete(campo);
            }
        });
        estado.delete('page');
        carregar();
    });

    document.getElementById('limpar-filtros').addEventListener('click', () => {
        form.reset();
        CAMPOS_FILTRO.forEach((campo) => estado.delete(campo));
        estado.delete('page');
        carregar();
    });

    document.querySelectorAll('[data-ordenar]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const mesmaColuna = (estado.get('ordenar') ?? 'criado_em') === botao.dataset.ordenar;
            const direcaoAtual = estado.get('direcao') ?? 'desc';
            estado.set('ordenar', botao.dataset.ordenar);
            estado.set('direcao', mesmaColuna && direcaoAtual === 'desc' ? 'asc' : 'desc');
            estado.delete('page');
            carregar();
        });
    });

    carregar();
})();
