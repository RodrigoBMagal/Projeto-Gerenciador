/**
 * Configuracao do front-end.
 * Ajuste API_BASE_URL para onde a API Laravel esta rodando.
 * Com o docker-compose do backend, o padrao e http://localhost:8000.
 */
window.APP_CONFIG = {
  API_BASE_URL: (window.localStorage.getItem('api_base_url') || 'http://localhost:8000') + '/api',
};
