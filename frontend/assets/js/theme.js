/**
 * Tema claro/escuro. A escolha e salva em localStorage e reaplicada em toda
 * pagina antes da renderizacao (ver o pequeno script inline no <head> de
 * cada HTML, que evita "flash" de tema errado ao carregar a pagina).
 */
const Theme = (() => {
  const KEY = 'gerenciador_theme';

  function get() {
    return window.localStorage.getItem(KEY) || 'light';
  }

  function apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  function set(theme) {
    window.localStorage.setItem(KEY, theme);
    apply(theme);
    updateToggleIcon();
  }

  function toggle() {
    set(get() === 'dark' ? 'light' : 'dark');
  }

  function updateToggleIcon() {
    document.querySelectorAll('[data-theme-toggle] i').forEach((icon) => {
      icon.className = get() === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    });
  }

  function bind() {
    updateToggleIcon();
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
      btn.addEventListener('click', toggle);
    });
  }

  return { get, apply, set, toggle, bind };
})();
