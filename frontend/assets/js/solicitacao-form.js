(async () => {
    const id = App.parametro('id');
    const editando = id !== null;

    await App.iniciar(editando ? 'solicitacoes' : 'nova');

    // Atendente não abre nem edita solicitações (o backend também bloqueia)
    if (App.isAtendente()) {
        location.href = 'solicitacoes.html';
        return;
    }

    const form = document.getElementById('form-solicitacao');
    const contador = document.getElementById('contador-titulo');
    let categorias = [];

    form.titulo.addEventListener('input', () => { contador.textContent = form.titulo.value.length; });

    form.categoria_id.addEventListener('change', () => {
        const categoria = categorias.find((c) => String(c.id) === form.categoria_id.value);
        document.getElementById('dica-sla').textContent = categoria
            ? `Prazo de atendimento previsto: ${categoria.sla_horas} horas.`
            : '';
    });

    try {
        ({ data: categorias } = await Api.get('/api/categorias'));
        form.categoria_id.insertAdjacentHTML('beforeend', categorias
            .map((c) => `<option value="${c.id}">${App.esc(c.nome)}</option>`).join(''));
    } catch (erro) {
        App.notificar(erro.message, 'danger');
    }

    if (editando) {
        document.title = 'Editar solicitação · Portal de Solicitações';
        document.getElementById('titulo').textContent = `Editar solicitação #${Number(id)}`;
        document.getElementById('salvar').innerHTML = '<i class="ti ti-device-floppy me-2"></i>Salvar alterações';
        document.getElementById('cancelar').href = `solicitacao.html?id=${encodeURIComponent(id)}`;
        document.getElementById('voltar').href = `solicitacao.html?id=${encodeURIComponent(id)}`;

        try {
            const { data: s } = await Api.get(`/api/solicitacoes/${encodeURIComponent(id)}`);
            if (s.status_id !== 1) {
                App.notificarAposRedirecionar('Só é possível editar solicitações com status Aberto.', 'warning');
                location.href = `solicitacao.html?id=${s.id}`;
                return;
            }
            form.titulo.value = s.titulo;
            form.categoria_id.value = String(s.categoria_id);
            form.descricao.value = s.descricao;
            form.titulo.dispatchEvent(new Event('input'));
            form.categoria_id.dispatchEvent(new Event('change'));
        } catch (erro) {
            App.notificarAposRedirecionar(erro.message, 'warning');
            location.href = 'solicitacoes.html';
            return;
        }
    }

    // Validação no navegador: dá retorno imediato, mas quem garante as regras é o backend
    function validarNoCliente() {
        const erros = {};
        const titulo = form.titulo.value.trim();
        if (titulo.length < 5 || titulo.length > 150) {
            erros.titulo = 'O título deve ter entre 5 e 150 caracteres.';
        }
        if (!form.categoria_id.value) {
            erros.categoria_id = 'Selecione uma categoria.';
        }
        if (form.descricao.value.trim().length < 10) {
            erros.descricao = 'A descrição deve ter pelo menos 10 caracteres.';
        }
        return erros;
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        App.limparErros(form);

        const erros = validarNoCliente();
        if (Object.keys(erros).length > 0) {
            App.mostrarErros(form, erros);
            return;
        }

        const dados = {
            titulo: form.titulo.value.trim(),
            descricao: form.descricao.value.trim(),
            categoria_id: Number(form.categoria_id.value),
        };

        await App.comBotaoOcupado(document.getElementById('salvar'), async () => {
            try {
                const { data } = editando
                    ? await Api.put(`/api/solicitacoes/${encodeURIComponent(id)}`, dados)
                    : await Api.post('/api/solicitacoes', dados);
                App.notificarAposRedirecionar(editando ? 'Alterações salvas.' : `Solicitação #${data.id} aberta.`);
                location.href = `solicitacao.html?id=${data.id}`;
            } catch (erro) {
                if (erro.status === 422) {
                    App.mostrarErros(form, erro.fields);
                }
                App.notificar(erro.message, erro.status === 409 ? 'warning' : 'danger');
            }
        });
    });
})();
