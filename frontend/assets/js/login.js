(() => {
    const form = document.getElementById('form-login');
    const caixaErro = document.getElementById('erro-login');
    const campoSenha = document.getElementById('senha');

    document.querySelector('[data-acao="tema"]').addEventListener('click', App.alternarTema);
    App.atualizarBotaoTema();

    // Já logado? Vai direto para o dashboard
    Api.get('/api/me').then(() => { location.href = 'index.html'; }).catch(() => {});

    document.getElementById('mostrar-senha').addEventListener('click', (evento) => {
        const visivel = campoSenha.type === 'text';
        campoSenha.type = visivel ? 'password' : 'text';
        evento.currentTarget.innerHTML = `<i class="ti ti-${visivel ? 'eye' : 'eye-off'}"></i>`;
    });

    function mostrarErro(mensagem) {
        caixaErro.textContent = mensagem;
        caixaErro.classList.remove('d-none');
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        caixaErro.classList.add('d-none');
        App.limparErros(form);

        const dados = {
            usuario: form.usuario.value.trim(),
            senha: form.senha.value,
        };

        const botao = form.querySelector('[type="submit"]');
        await App.comBotaoOcupado(botao, async () => {
            try {
                await Api.post('/api/login', dados);
                location.href = 'index.html';
            } catch (erro) {
                if (erro.status === 422) {
                    App.mostrarErros(form, erro.fields);
                } else {
                    // 401 (credenciais), 429 (bloqueio temporário) ou falha de rede
                    mostrarErro(erro.message);
                    form.senha.value = '';
                    form.senha.focus();
                }
            }
        });
    });
})();
