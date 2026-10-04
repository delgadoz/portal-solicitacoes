(async () => {
    await App.iniciar('dashboard');
    App.exibirNotificacaoPendente();

    if (!App.isAtendente()) {
        document.getElementById('pretitulo').textContent = 'Suas solicitações';
        document.getElementById('acoes-cabecalho').innerHTML = `
            <a href="solicitacao-form.html" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Nova solicitação
            </a>`;
    }

    function definir(kpi, valor) {
        document.querySelectorAll(`[data-kpi="${kpi}"]`).forEach((el) => { el.textContent = valor; });
    }

    function desenharGrafico(categorias) {
        const escuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const corPrimaria = escuro ? '#4f68c7' : '#293e93';
        const corTexto = escuro ? '#9aa4b2' : '#667382';
        const corGrade = escuro ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

        new Chart(document.getElementById('grafico-categorias'), {
            type: 'bar',
            data: {
                labels: categorias.map((c) => c.nome),
                datasets: [{
                    label: 'Solicitações',
                    data: categorias.map((c) => c.total),
                    backgroundColor: corPrimaria,
                    borderRadius: 4,
                    maxBarThickness: 48,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, border: { color: corGrade }, ticks: { color: corTexto } },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        ticks: { precision: 0, color: corTexto },
                        grid: { color: corGrade },
                    },
                },
            },
        });
    }

    try {
        const [{ data: dash }, { data: ultimas }] = await Promise.all([
            Api.get('/api/dashboard'),
            Api.get('/api/solicitacoes?per_page=5'),
        ]);

        definir('total', dash.totais.total);
        definir('abertas', dash.totais.abertas);
        definir('em_atendimento', dash.totais.em_atendimento);
        definir('concluidas', dash.totais.concluidas);
        definir('concluidas_sla', dash.totais.concluidas);
        definir('vencidas', dash.sla.vencidas);
        definir('concluidas_no_prazo', dash.sla.concluidas_no_prazo);

        const percentual = dash.sla.percentual_no_prazo;
        definir('percentual_no_prazo', percentual === null ? '—' : `${percentual.toLocaleString('pt-BR')}%`);
        document.getElementById('barra-prazo').style.width = `${percentual ?? 0}%`;

        const horas = dash.tempo_medio_atendimento_horas;
        definir('tempo_medio', horas === null ? 'Sem concluídas' : `${horas.toLocaleString('pt-BR')} h`);

        if (typeof Chart !== 'undefined') {
            desenharGrafico(dash.por_categoria);
        }

        const corpo = document.getElementById('ultimas');
        corpo.innerHTML = ultimas.length === 0
            ? '<tr><td colspan="6" class="text-center text-secondary py-4">Nenhuma solicitação ainda.</td></tr>'
            : ultimas.map((s) => `
                <tr data-id="${s.id}">
                    <td class="text-secondary">#${s.id}</td>
                    <td class="fw-medium">${App.esc(s.titulo)}</td>
                    <td>${App.esc(s.categoria)}</td>
                    <td>${App.esc(s.solicitante)}</td>
                    <td class="text-secondary">${App.formatarData(s.criado_em)}</td>
                    <td>${App.badgeStatus(s.status_id, s.status)}</td>
                </tr>`).join('');

        corpo.querySelectorAll('tr[data-id]').forEach((linha) => {
            linha.addEventListener('click', () => { location.href = `solicitacao.html?id=${linha.dataset.id}`; });
        });
    } catch (erro) {
        App.notificar(erro.message, 'danger');
    }
})();
