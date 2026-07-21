/**
 * Monta o "shell" comum a todas as paginas autenticadas (sidebar + topbar +
 * breadcrumb), aplica o guard de autenticacao e expoe helpers de UI
 * reutilizados pelas paginas (toast, confirmacao, estados de
 * loading/vazio/erro, paginacao e ordenacao de tabelas).
 */
const Layout = (() => {
  const NAV_ITEMS = [
    { page: 'dashboard', href: 'index.html', icon: 'fa-gauge-high', label: 'Dashboard' },
    { page: 'tarefas', href: 'tarefas.html', icon: 'fa-list-check', label: 'Tarefas' },
    { page: 'staff', href: 'staff.html', icon: 'fa-users', label: 'Equipe' },
  ];

  function navHtml(activePage) {
    return NAV_ITEMS.map((item) => `
      <a href="${item.href}" class="nav-link ${item.page === activePage ? 'active' : ''}" ${item.page === activePage ? 'aria-current="page"' : ''}>
        <i class="fa-solid ${item.icon}" aria-hidden="true"></i>
        <span>${item.label}</span>
      </a>
    `).join('');
  }

  function shellHtml(activePage, user) {
    const iniciais = (user?.name || '?').trim().charAt(0).toUpperCase();
    const apiRoot = window.APP_CONFIG.API_BASE_URL.replace('/api', '');

    return `
      <a href="#pageContent" class="skip-link">Pular para o conteudo</a>

      <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

      <aside class="sidebar" id="appSidebar">
        <div class="sidebar-brand">
          <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
          <span>Gerenciador</span>
        </div>
        <nav class="sidebar-nav" aria-label="Navegacao principal">${navHtml(activePage)}</nav>
        <div class="sidebar-footer">
          <a href="${apiRoot}/docs/api" target="_blank" rel="noopener" class="nav-link">
            <i class="fa-solid fa-book" aria-hidden="true"></i>
            <span>Docs da API</span>
          </a>
        </div>
      </aside>

      <div class="main">
        <header class="topbar">
          <button id="sidebarToggle" class="icon-btn d-lg-none" aria-label="Abrir menu de navegacao" aria-controls="appSidebar" aria-expanded="false">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
          </button>

          <div class="topbar-title">
            <nav class="breadcrumb-nav" id="breadcrumbNav" aria-label="Trilha de navegacao"></nav>
          </div>

          <button class="icon-btn" data-theme-toggle aria-label="Alternar tema claro/escuro">
            <i class="fa-solid fa-moon" aria-hidden="true"></i>
          </button>

          <div class="topbar-user dropdown">
            <button class="user-chip" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu do usuario">
              <span class="avatar" aria-hidden="true">${iniciais}</span>
              <span class="user-name d-none d-sm-inline">${escapeHtml(user?.name || 'Usuario')}</span>
              <i class="fa-solid fa-chevron-down small" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li class="px-3 py-1"><span class="fw-bold small d-block">${escapeHtml(user?.name || '')}</span><span class="text-muted small">${escapeHtml(user?.email || '')}</span></li>
              <li><hr class="dropdown-divider"></li>
              <li><button class="dropdown-item text-danger" id="logoutBtn"><i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Sair</button></li>
            </ul>
          </div>
        </header>

        <main class="content" id="pageContent" tabindex="-1"></main>
      </div>

      <div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>
    `;
  }

  async function logout() {
    try {
      await Api.post('/logout');
    } catch (e) {
      // segue limpando a sessao local mesmo se a chamada falhar
    } finally {
      Api.clearSession();
      window.location.href = 'login.html';
    }
  }

  /**
   * Deve ser chamado no topo de toda pagina protegida.
   * Injeta o shell, move o conteudo existente de #pageContent para dentro
   * dele e liga os eventos comuns (logout, tema, menu mobile).
   */
  function mount(activePage) {
    if (!Api.isAuthenticated()) {
      window.location.href = 'login.html';
      return;
    }

    Theme.apply(Theme.get());

    const user = Api.getUser();
    const existingContent = document.getElementById('pageContent');
    const originalHtml = existingContent ? existingContent.innerHTML : '';

    document.body.innerHTML = shellHtml(activePage, user);
    document.getElementById('pageContent').innerHTML = originalHtml;

    document.getElementById('logoutBtn').addEventListener('click', logout);
    Theme.bind();

    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const openSidebar = () => {
      document.body.classList.add('sidebar-open');
      toggle.setAttribute('aria-expanded', 'true');
    };
    const closeSidebar = () => {
      document.body.classList.remove('sidebar-open');
      toggle.setAttribute('aria-expanded', 'false');
    };
    toggle.addEventListener('click', () => {
      document.body.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    });
    backdrop.addEventListener('click', closeSidebar);
    document.querySelectorAll('.sidebar-nav .nav-link').forEach((link) => {
      link.addEventListener('click', closeSidebar);
    });
  }

  /**
   * Renderiza a trilha de navegacao no topo da pagina.
   * items: [{ label, href? }] — o ultimo item nao deve ter href (pagina atual).
   */
  function setBreadcrumb(items) {
    const nav = document.getElementById('breadcrumbNav');
    if (!nav) return;

    nav.innerHTML = items.map((item, index) => {
      const isLast = index === items.length - 1;
      const separator = index > 0 ? '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>' : '';
      const content = item.href && !isLast
        ? `<a href="${item.href}">${escapeHtml(item.label)}</a>`
        : `<span class="${isLast ? 'current' : ''}">${escapeHtml(item.label)}</span>`;
      return separator + content;
    }).join('');
  }

  /* ---------------- Toast / confirmacao (SweetAlert2, com fallback) ---------------- */

  function toast(message, type = 'success') {
    if (window.Swal) {
      const icon = { success: 'success', danger: 'error', warning: 'warning', info: 'info' }[type] || 'info';
      Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        didOpen: (el) => {
          el.addEventListener('mouseenter', Swal.stopTimer);
          el.addEventListener('mouseleave', Swal.resumeTimer);
        },
      }).fire({ icon, title: message });
      return;
    }

    // Fallback sem SweetAlert2 (caso o CDN nao carregue)
    const stack = document.getElementById('toastStack');
    if (!stack) return;
    const icons = { success: 'fa-circle-check', danger: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
    const el = document.createElement('div');
    el.className = `app-toast app-toast-${type}`;
    el.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i><span>${escapeHtml(message)}</span>`;
    stack.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 250); }, 4000);
  }

  async function confirmAction({ title = 'Confirmar acao', message, confirmLabel = 'Confirmar', danger = true }) {
    if (window.Swal) {
      const result = await Swal.fire({
        title,
        html: message,
        icon: danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: confirmLabel,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: danger ? '#e0433a' : '#4e5df9',
        reverseButtons: true,
        focusCancel: true,
      });
      return result.isConfirmed;
    }

    // Fallback simples com o Modal do Bootstrap
    return new Promise((resolve) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'modal fade';
      wrapper.tabIndex = -1;
      wrapper.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">${title}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p class="mb-0">${message}</p></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn ${danger ? 'btn-danger' : 'btn-primary'}" id="confirmActionBtn">${confirmLabel}</button>
            </div>
          </div>
        </div>`;
      document.body.appendChild(wrapper);
      const modal = new bootstrap.Modal(wrapper);
      let confirmed = false;
      wrapper.querySelector('#confirmActionBtn').addEventListener('click', () => { confirmed = true; modal.hide(); });
      wrapper.addEventListener('hidden.bs.modal', () => { wrapper.remove(); resolve(confirmed); });
      modal.show();
    });
  }

  function applyValidationErrors(form, errors) {
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback[data-dynamic]').forEach((el) => el.remove());
    if (!errors) return;

    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      const feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      feedback.dataset.dynamic = 'true';
      feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.insertAdjacentElement('afterend', feedback);
    });
  }

  /* ---------------- Estados de loading / vazio / erro ---------------- */

  function loadingRows(columns, rows = 4) {
    const cells = Array.from({ length: columns }).map(() => `<td><div class="skeleton" style="height:1rem"></div></td>`).join('');
    return Array.from({ length: rows }).map(() => `<tr class="loading-rows">${cells}</tr>`).join('');
  }

  function emptyState({ icon = 'fa-inbox', title = 'Nada por aqui ainda', message = '', actionLabel, onAction }) {
    const id = `empty-action-${Math.random().toString(36).slice(2, 8)}`;
    setTimeout(() => {
      if (actionLabel && onAction) document.getElementById(id)?.addEventListener('click', onAction);
    });
    return `
      <div class="empty-state fade-in">
        <i class="fa-solid ${icon}" aria-hidden="true"></i>
        <h6>${title}</h6>
        <p class="mb-3">${message}</p>
        ${actionLabel ? `<button class="btn btn-primary btn-sm" id="${id}">${actionLabel}</button>` : ''}
      </div>`;
  }

  function errorState({ message = 'Nao foi possivel carregar os dados.', onRetry }) {
    const id = `error-retry-${Math.random().toString(36).slice(2, 8)}`;
    setTimeout(() => { if (onRetry) document.getElementById(id)?.addEventListener('click', onRetry); });
    return `
      <div class="error-state fade-in">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <h6>Ops, algo deu errado</h6>
        <p class="mb-3">${escapeHtml(message)}</p>
        ${onRetry ? `<button class="btn btn-outline-danger btn-sm" id="${id}"><i class="fa-solid fa-rotate-right me-1"></i>Tentar novamente</button>` : ''}
      </div>`;
  }

  /* ---------------- Paginacao client-side ---------------- */

  function paginate(items, page, perPage) {
    const totalPages = Math.max(1, Math.ceil(items.length / perPage));
    const current = Math.min(Math.max(1, page), totalPages);
    const start = (current - 1) * perPage;
    return {
      items: items.slice(start, start + perPage),
      page: current,
      totalPages,
      total: items.length,
    };
  }

  function paginationHtml(page, totalPages) {
    if (totalPages <= 1) return '';

    const pages = [];
    const window_ = 1;
    for (let p = 1; p <= totalPages; p += 1) {
      if (p === 1 || p === totalPages || Math.abs(p - page) <= window_) {
        pages.push(p);
      } else if (pages[pages.length - 1] !== '…') {
        pages.push('…');
      }
    }

    const pageButtons = pages.map((p) => (
      p === '…'
        ? `<span class="px-1 text-muted">…</span>`
        : `<button type="button" class="${p === page ? 'active' : ''}" data-page="${p}">${p}</button>`
    )).join('');

    return `
      <button type="button" data-page="${page - 1}" ${page === 1 ? 'disabled' : ''} aria-label="Pagina anterior"><i class="fa-solid fa-chevron-left"></i></button>
      <div class="pages">${pageButtons}</div>
      <button type="button" data-page="${page + 1}" ${page === totalPages ? 'disabled' : ''} aria-label="Proxima pagina"><i class="fa-solid fa-chevron-right"></i></button>
    `;
  }

  /**
   * Liga os cliques dos botoes de paginacao renderizados por paginationHtml().
   * `onChange(novaPagina)` e chamado quando o usuario troca de pagina.
   */
  function bindPagination(container, onChange) {
    container.querySelectorAll('button[data-page]').forEach((btn) => {
      btn.addEventListener('click', () => onChange(Number(btn.dataset.page)));
    });
  }

  /* ---------------- Ordenacao de tabelas ---------------- */

  /**
   * Liga cabecalhos com [data-sort-key] a um estado { key, dir } e chama
   * onSort sempre que o usuario clica em uma coluna ordenavel.
   */
  function bindSortableHeaders(container, state, onSort) {
    container.querySelectorAll('th[data-sort-key]').forEach((th) => {
      const key = th.dataset.sortKey;
      th.classList.toggle('active', state.key === key);
      const icon = th.querySelector('i');
      if (icon) {
        icon.className = state.key === key
          ? (state.dir === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down')
          : 'fa-solid fa-sort';
      }
      th.addEventListener('click', () => {
        const dir = state.key === key && state.dir === 'asc' ? 'desc' : 'asc';
        onSort({ key, dir });
      });
    });
  }

  function sortItems(items, key, dir) {
    const factor = dir === 'asc' ? 1 : -1;
    return [...items].sort((a, b) => {
      let va = a[key];
      let vb = b[key];
      if (va === null || va === undefined) va = '';
      if (vb === null || vb === undefined) vb = '';
      if (typeof va === 'number' || typeof vb === 'number') {
        return (Number(va) - Number(vb)) * factor;
      }
      return String(va).localeCompare(String(vb), 'pt-BR', { numeric: true }) * factor;
    });
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  }

  return {
    mount,
    setBreadcrumb,
    toast,
    confirmAction,
    applyValidationErrors,
    loadingRows,
    emptyState,
    errorState,
    paginate,
    paginationHtml,
    bindPagination,
    bindSortableHeaders,
    sortItems,
    escapeHtml,
    logout,
  };
})();
