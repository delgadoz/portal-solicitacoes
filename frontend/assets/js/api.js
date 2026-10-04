/**
 * Cliente HTTP centralizado: toda chamada à API passa por aqui.
 *
 * - Envia e recebe JSON
 * - Envia o cookie de sessão (mesma origem) e o token CSRF nas requisições que alteram dados
 * - 401 → volta para o login
 * - Erros da API viram ApiError, com status, código e erros por campo
 */
class ApiError extends Error {
    constructor(status, code, message, fields = {}) {
        super(message);
        this.status = status;
        this.code = code;
        this.fields = fields;
    }
}

const Api = (() => {
    let csrfToken = null;
    const METODOS_DE_ESCRITA = ['POST', 'PUT', 'PATCH', 'DELETE'];

    async function request(method, path, body = null) {
        const headers = { Accept: 'application/json' };

        if (body !== null) {
            headers['Content-Type'] = 'application/json';
        }
        if (csrfToken && METODOS_DE_ESCRITA.includes(method)) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        let resposta;
        try {
            resposta = await fetch(path, {
                method,
                headers,
                credentials: 'same-origin',
                body: body !== null ? JSON.stringify(body) : undefined,
            });
        } catch {
            throw new ApiError(0, 'NETWORK_ERROR', 'Não foi possível conectar ao servidor.');
        }

        if (resposta.status === 204) {
            return null;
        }

        const json = await resposta.json().catch(() => ({}));

        if (!resposta.ok) {
            const erro = json.error ?? {};
            // Sessão ausente ou expirada: volta para o login (exceto na própria tela de login)
            if (resposta.status === 401 && !location.pathname.endsWith('login.html')) {
                location.href = 'login.html';
            }
            throw new ApiError(
                resposta.status,
                erro.code ?? 'ERROR',
                erro.message ?? 'Erro inesperado.',
                erro.fields ?? {}
            );
        }

        return json;
    }

    return {
        setCsrfToken: (token) => { csrfToken = token; },
        get: (path) => request('GET', path),
        post: (path, body) => request('POST', path, body ?? {}),
        put: (path, body) => request('PUT', path, body),
        patch: (path, body) => request('PATCH', path, body),
        delete: (path) => request('DELETE', path),
    };
})();
