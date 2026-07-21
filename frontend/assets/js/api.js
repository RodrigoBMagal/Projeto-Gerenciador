/**
 * Camada fina sobre fetch() para falar com a API Laravel:
 * - injeta o header Authorization: Bearer {token} automaticamente
 * - decodifica JSON e normaliza erros (inclusive erros 422 de validacao)
 * - em 401, limpa a sessao local e redireciona para o login
 */
const Api = (() => {
  const TOKEN_KEY = 'gerenciador_token';
  const USER_KEY = 'gerenciador_user';

  function getToken() {
    return window.localStorage.getItem(TOKEN_KEY);
  }

  function setSession(token, user) {
    window.localStorage.setItem(TOKEN_KEY, token);
    window.localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  function clearSession() {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(USER_KEY);
  }

  function getUser() {
    const raw = window.localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  function isAuthenticated() {
    return Boolean(getToken());
  }

  /**
   * Erro padronizado lancado por toda chamada que falhar.
   * `status` = codigo HTTP; `errors` = mapa de validacao (422), quando houver.
   */
  class ApiError extends Error {
    constructor(message, status, errors) {
      super(message);
      this.name = 'ApiError';
      this.status = status;
      this.errors = errors || null;
    }
  }

  async function request(path, options = {}) {
    const token = getToken();
    const headers = Object.assign(
      {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      options.headers || {}
    );

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    let response;
    try {
      response = await fetch(`${window.APP_CONFIG.API_BASE_URL}${path}`, {
        ...options,
        headers,
      });
    } catch (networkError) {
      throw new ApiError(
        'Nao foi possivel conectar a API. Verifique se o backend esta rodando e se a URL configurada esta correta.',
        0
      );
    }

    let data = null;
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      data = await response.json().catch(() => null);
    }

    if (response.status === 401) {
      clearSession();
      if (!window.location.pathname.endsWith('login.html')) {
        const next = encodeURIComponent(window.location.pathname.split('/').pop());
        window.location.href = `login.html?expired=1&next=${next}`;
      }
      throw new ApiError('Sessao expirada. Faca login novamente.', 401);
    }

    if (!response.ok) {
      const message = (data && data.message) || 'Ocorreu um erro ao falar com a API.';
      throw new ApiError(message, response.status, data ? data.errors : null);
    }

    return data;
  }

  return {
    ApiError,
    getToken,
    setSession,
    clearSession,
    getUser,
    isAuthenticated,
    get: (path) => request(path, { method: 'GET' }),
    post: (path, body) => request(path, { method: 'POST', body: JSON.stringify(body || {}) }),
    put: (path, body) => request(path, { method: 'PUT', body: JSON.stringify(body || {}) }),
    patch: (path, body) => request(path, { method: 'PATCH', body: JSON.stringify(body || {}) }),
    delete: (path) => request(path, { method: 'DELETE' }),
    /**
     * Monta "?a=1&b=2" a partir de um objeto, ignorando chaves com valor
     * vazio/nulo/indefinido — usado para montar as URLs de listagem
     * paginada/filtrada (ex.: GET /tarefas?status=pendente&page=2).
     */
    queryString(params) {
      const usp = new URLSearchParams();
      Object.entries(params || {}).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        usp.set(key, value);
      });
      const qs = usp.toString();
      return qs ? `?${qs}` : '';
    },
  };
})();
