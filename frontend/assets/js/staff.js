Layout.mount('staff');
Layout.setBreadcrumb([{ label: 'Dashboard', href: 'index.html' }, { label: 'Equipe' }]);

const PER_PAGE = 8;

let staffModal, atribuirModal, loteModal;
let searchDebounce = null;
let cacheStaffPorId = new Map();

const estado = {
  page: 1,
  search: '',
  contratos: [],
  comTarefa: null,
  sort_by: 'nome',
  sort_dir: 'asc',
};

document.addEventListener('DOMContentLoaded', () => {
  staffModal = new bootstrap.Modal(document.getElementById('staffModal'));
  atribuirModal = new bootstrap.Modal(document.getElementById('atribuirModal'));
  loteModal = new bootstrap.Modal(document.getElementById('loteModal'));

  document.getElementById('novoStaffBtn').addEventListener('click', () => abrirModal());
  document.getElementById('novoLoteBtn').addEventListener('click', abrirLoteModal);
  document.getElementById('staffForm').addEventListener('submit', salvarStaff);
  document.getElementById('atribuirForm').addEventListener('submit', salvarAtribuicao);
  document.getElementById('loteSubmitBtn').addEventListener('click', salvarLote);

  document.getElementById('staffSearch').addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      estado.search = e.target.value.trim();
      estado.page = 1;
      carregarStaff();
    }, 300);
  });

  document.getElementById('somenteComTarefa').addEventListener('change', (e) => {
    estado.comTarefa = e.target.checked ? true : null;
    estado.page = 1;
    carregarStaff();
  });

  document.querySelectorAll('#filtroContratoPanel input[type="checkbox"]').forEach((cb) => {
    cb.addEventListener('change', () => {
      estado.contratos = Array.from(document.querySelectorAll('#filtroContratoPanel input:checked')).map((el) => el.value);
      estado.page = 1;
      carregarStaff();
    });
  });
  document.getElementById('limparFiltroContrato').addEventListener('click', () => {
    document.querySelectorAll('#filtroContratoPanel input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
    estado.contratos = [];
    estado.page = 1;
    carregarStaff();
  });

  carregarStaff();
});

async function carregarStaff() {
  document.getElementById('staffTbody').innerHTML = Layout.loadingRows(8);
  document.getElementById('staffEmpty').innerHTML = '';
  document.getElementById('staffPagination').innerHTML = '';

  const qs = Api.queryString({
    search: estado.search,
    contrato: estado.contratos.join(',') || null,
    com_tarefa: estado.comTarefa === null ? null : (estado.comTarefa ? 'true' : 'false'),
    sort_by: estado.sort_by,
    sort_dir: estado.sort_dir,
    per_page: PER_PAGE,
    page: estado.page,
  });

  try {
    const resposta = await Api.get(`/staff${qs}`);
    const itens = resposta.data || [];
    cacheStaffPorId = new Map(itens.map((s) => [s.id, s]));

    document.getElementById('staffCount').textContent = `${resposta.meta.total} membro${resposta.meta.total === 1 ? '' : 's'}`;
    renderTabela(itens, resposta.meta.total);

    const paginationEl = document.getElementById('staffPagination');
    paginationEl.innerHTML = Layout.paginationHtml(resposta.meta.current_page, resposta.meta.last_page);
    Layout.bindPagination(paginationEl, (novaPagina) => { estado.page = novaPagina; carregarStaff(); });

    Layout.bindSortableHeaders(document, { key: estado.sort_by, dir: estado.sort_dir }, (novoEstado) => {
      estado.sort_by = novoEstado.key;
      estado.sort_dir = novoEstado.dir;
      estado.page = 1;
      carregarStaff();
    });
  } catch (err) {
    document.getElementById('staffTbody').innerHTML = '';
    document.getElementById('staffEmpty').innerHTML = Layout.errorState({ message: err.message, onRetry: carregarStaff });
  }
}

function renderTabela(staff, totalGeral) {
  const tbody = document.getElementById('staffTbody');
  const emptyState = document.getElementById('staffEmpty');

  const semFiltros = !estado.search && !estado.contratos.length && estado.comTarefa === null;

  if (!totalGeral) {
    tbody.innerHTML = '';
    emptyState.innerHTML = semFiltros
      ? Layout.emptyState({
        icon: 'fa-users',
        title: 'Nenhum membro cadastrado',
        message: 'Adicione o primeiro membro da equipe para comecar.',
        actionLabel: 'Novo membro',
        onAction: () => abrirModal(),
      })
      : Layout.emptyState({
        icon: 'fa-magnifying-glass',
        title: 'Nenhum resultado',
        message: 'Nenhum membro corresponde aos filtros aplicados.',
      });
    return;
  }
  emptyState.innerHTML = '';

  tbody.innerHTML = staff.map((s, i) => `
    <tr class="fade-in fade-in-${Math.min(i, 4)}">
      <td class="fw-bold">${Layout.escapeHtml(s.nome)}</td>
      <td>${Layout.escapeHtml(s.cargo || '—')}</td>
      <td>${Layout.escapeHtml(s.local || '—')}</td>
      <td>${s.idade ?? '—'}</td>
      <td>${Layout.escapeHtml(s.contrato || '—')}</td>
      <td>${formatMoney(s.salario)}</td>
      <td>
        ${s.tarefa
          ? `<span class="badge-tarefa">${Layout.escapeHtml(s.tarefa.nome)}</span>`
          : '<span class="badge-sem-tarefa">Sem tarefa</span>'}
      </td>
      <td>
        <div class="row-actions">
          <button class="btn btn-sm btn-outline-primary" onclick="abrirAtribuirModal(${s.id})" title="Atribuir tarefa" aria-label="Atribuir tarefa para ${Layout.escapeHtml(s.nome)}">
            <i class="fa-solid fa-link"></i>
          </button>
          <button class="btn btn-sm btn-outline-secondary" onclick="abrirModal(${s.id})" title="Editar" aria-label="Editar ${Layout.escapeHtml(s.nome)}">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="removerStaff(${s.id})" title="Remover" aria-label="Remover ${Layout.escapeHtml(s.nome)}">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function abrirModal(id) {
  const form = document.getElementById('staffForm');
  form.reset();
  Layout.applyValidationErrors(form, null);

  const membro = id ? cacheStaffPorId.get(id) : null;

  document.getElementById('staffModalTitle').textContent = membro ? 'Editar membro' : 'Novo membro';
  document.getElementById('staffId').value = membro ? membro.id : '';
  document.getElementById('staffNome').value = membro ? membro.nome : '';
  document.getElementById('staffCargo').value = membro ? membro.cargo || '' : '';
  document.getElementById('staffLocal').value = membro ? membro.local || '' : '';
  document.getElementById('staffIdade').value = membro ? membro.idade || '' : '';
  document.getElementById('staffContrato').value = membro ? membro.contrato || '' : '';
  document.getElementById('staffSalario').value = membro ? membro.salario || '' : '';

  staffModal.show();
}

async function salvarStaff(event) {
  event.preventDefault();
  const form = event.target;
  const id = document.getElementById('staffId').value;
  const btn = document.getElementById('staffSubmitBtn');

  const payload = {
    nome: document.getElementById('staffNome').value.trim(),
    cargo: document.getElementById('staffCargo').value.trim() || null,
    local: document.getElementById('staffLocal').value.trim() || null,
    idade: document.getElementById('staffIdade').value || null,
    contrato: document.getElementById('staffContrato').value || null,
    salario: document.getElementById('staffSalario').value || null,
  };

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-inline"></span>';

  try {
    if (id) {
      await Api.put(`/staff/${id}`, payload);
      Layout.toast('Membro atualizado com sucesso.');
    } else {
      await Api.post('/staff', payload);
      Layout.toast('Membro cadastrado com sucesso.');
    }
    staffModal.hide();
    await carregarStaff();
  } catch (err) {
    if (err.status === 422) {
      Layout.applyValidationErrors(form, err.errors);
    } else {
      Layout.toast(err.message || 'Erro ao salvar o membro.', 'danger');
    }
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Salvar';
  }
}

async function removerStaff(id) {
  const membro = cacheStaffPorId.get(id);
  const confirmado = await Layout.confirmAction({
    title: 'Remover membro',
    message: `Tem certeza que deseja remover <strong>${Layout.escapeHtml(membro?.nome || '')}</strong> da equipe?`,
    confirmLabel: 'Remover',
  });
  if (!confirmado) return;

  try {
    await Api.delete(`/staff/${id}`);
    Layout.toast('Membro removido.');
    await carregarStaff();
  } catch (err) {
    Layout.toast(err.message || 'Erro ao remover o membro.', 'danger');
  }
}

async function abrirAtribuirModal(id) {
  const membro = cacheStaffPorId.get(id);
  document.getElementById('atribuirStaffId').value = id;
  document.getElementById('atribuirStaffNome').textContent = membro?.nome || '';

  const select = document.getElementById('atribuirTarefaSelect');
  select.innerHTML = '<option value="">Carregando tarefas...</option>';
  atribuirModal.show();

  try {
    // per_page alto o suficiente para trazer as tarefas mais recentes de uma vez;
    // para catalogos muito grandes, o ideal seria um campo de busca dedicado aqui.
    const resposta = await Api.get('/tarefas?per_page=100&sort_by=nome&sort_dir=asc');
    const lista = resposta.data || [];

    select.innerHTML = '<option value="">Selecione uma tarefa...</option>' +
      lista.map((t) => `<option value="${Layout.escapeHtml(t.nome)}" ${membro?.tarefa?.nome === t.nome ? 'selected' : ''}>${Layout.escapeHtml(t.nome)}</option>`).join('');

    if (!lista.length) {
      select.innerHTML = '<option value="">Nenhuma tarefa cadastrada</option>';
    }
  } catch (err) {
    Layout.toast(err.message || 'Erro ao carregar tarefas.', 'danger');
  }
}

async function salvarAtribuicao(event) {
  event.preventDefault();
  const id = document.getElementById('atribuirStaffId').value;
  const tarefaNome = document.getElementById('atribuirTarefaSelect').value;
  const btn = document.getElementById('atribuirSubmitBtn');

  if (!tarefaNome) {
    Layout.toast('Selecione uma tarefa.', 'warning');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-inline"></span>';

  try {
    await Api.patch(`/staff/${id}/tarefa`, { tarefa_nome: tarefaNome });
    Layout.toast('Tarefa atribuida com sucesso.');
    atribuirModal.hide();
    await carregarStaff();
  } catch (err) {
    Layout.toast(err.message || 'Erro ao atribuir a tarefa.', 'danger');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Atribuir';
  }
}

function abrirLoteModal() {
  document.getElementById('loteTextarea').value = '';
  document.getElementById('loteError').classList.add('d-none');
  loteModal.show();
}

async function salvarLote() {
  const texto = document.getElementById('loteTextarea').value.trim();
  const errorBox = document.getElementById('loteError');
  const btn = document.getElementById('loteSubmitBtn');
  errorBox.classList.add('d-none');

  if (!texto) {
    errorBox.textContent = 'Cole ao menos uma linha antes de continuar.';
    errorBox.classList.remove('d-none');
    return;
  }

  const linhas = texto.split('\n').map((linha) => linha.trim()).filter(Boolean).map((linha) => {
    const [nome, cargo, local, idade, contrato, salario] = linha.split(';').map((v) => (v ?? '').trim());
    return {
      nome,
      cargo: cargo || null,
      local: local || null,
      idade: idade || null,
      contrato: contrato || null,
      salario: salario || null,
    };
  });

  if (linhas.some((l) => !l.nome)) {
    errorBox.textContent = 'Toda linha precisa ter ao menos o nome preenchido.';
    errorBox.classList.remove('d-none');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-inline"></span>';

  try {
    await Api.post('/staff/lote', { linhas });
    Layout.toast(`${linhas.length} membro(s) cadastrado(s) com sucesso.`);
    loteModal.hide();
    await carregarStaff();
  } catch (err) {
    errorBox.textContent = err.message || 'Erro ao cadastrar em lote.';
    errorBox.classList.remove('d-none');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Cadastrar';
  }
}

function formatMoney(value) {
  if (value === null || value === undefined || value === '') return '—';
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
