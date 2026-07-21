Layout.mount('tarefas');
Layout.setBreadcrumb([{ label: 'Dashboard', href: 'index.html' }, { label: 'Tarefas' }]);

const PER_PAGE = 8;

let tarefaModal;
let cacheTarefaPorId = new Map(); // guarda os itens da pagina atual, usado ao editar/remover
let searchDebounce = null;

const estado = {
  page: 1,
  search: '',
  status: '',
  prioridade: '',
  data_de: '',
  data_ate: '',
  sort_by: 'data',
  sort_dir: 'asc',
};

document.addEventListener('DOMContentLoaded', () => {
  tarefaModal = new bootstrap.Modal(document.getElementById('tarefaModal'));

  document.getElementById('novaTarefaBtn').addEventListener('click', () => abrirModal());
  document.getElementById('tarefaForm').addEventListener('submit', salvarTarefa);
  document.getElementById('tarefaNome').addEventListener('input', verificarNomeDuplicado);

  document.getElementById('tarefaSearch').addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      estado.search = e.target.value.trim();
      estado.page = 1;
      carregarTarefas();
    }, 300);
  });

  document.getElementById('filtroStatus').addEventListener('change', (e) => {
    estado.status = e.target.value;
    estado.page = 1;
    carregarTarefas();
  });
  document.getElementById('filtroPrioridade').addEventListener('change', (e) => {
    estado.prioridade = e.target.value;
    estado.page = 1;
    carregarTarefas();
  });
  document.getElementById('filtroDataDe').addEventListener('change', (e) => {
    estado.data_de = e.target.value;
    estado.page = 1;
    carregarTarefas();
  });
  document.getElementById('filtroDataAte').addEventListener('change', (e) => {
    estado.data_ate = e.target.value;
    estado.page = 1;
    carregarTarefas();
  });
  document.getElementById('limparFiltros').addEventListener('click', () => {
    estado.status = '';
    estado.prioridade = '';
    estado.data_de = '';
    estado.data_ate = '';
    estado.page = 1;
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroPrioridade').value = '';
    document.getElementById('filtroDataDe').value = '';
    document.getElementById('filtroDataAte').value = '';
    carregarTarefas();
  });

  carregarTarefas();
});

async function carregarTarefas() {
  document.getElementById('tarefasTbody').innerHTML = Layout.loadingRows(6);
  document.getElementById('tarefasEmpty').innerHTML = '';
  document.getElementById('tarefasPagination').innerHTML = '';

  const qs = Api.queryString({
    search: estado.search,
    status: estado.status,
    prioridade: estado.prioridade,
    data_de: estado.data_de,
    data_ate: estado.data_ate,
    sort_by: estado.sort_by,
    sort_dir: estado.sort_dir,
    per_page: PER_PAGE,
    page: estado.page,
  });

  try {
    const resposta = await Api.get(`/tarefas${qs}`);
    const itens = resposta.data || [];
    cacheTarefaPorId = new Map(itens.map((t) => [t.id, t]));

    document.getElementById('tarefaCount').textContent = `${resposta.meta.total} tarefa${resposta.meta.total === 1 ? '' : 's'}`;
    renderTabela(itens, resposta.meta.total);

    const paginationEl = document.getElementById('tarefasPagination');
    paginationEl.innerHTML = Layout.paginationHtml(resposta.meta.current_page, resposta.meta.last_page);
    Layout.bindPagination(paginationEl, (novaPagina) => { estado.page = novaPagina; carregarTarefas(); });

    Layout.bindSortableHeaders(document, { key: estado.sort_by, dir: estado.sort_dir }, (novoEstado) => {
      estado.sort_by = novoEstado.key;
      estado.sort_dir = novoEstado.dir;
      estado.page = 1;
      carregarTarefas();
    });
  } catch (err) {
    document.getElementById('tarefasTbody').innerHTML = '';
    document.getElementById('tarefasEmpty').innerHTML = Layout.errorState({ message: err.message, onRetry: carregarTarefas });
  }
}

function renderTabela(tarefas, totalGeral) {
  const tbody = document.getElementById('tarefasTbody');
  const emptyState = document.getElementById('tarefasEmpty');

  const semFiltros = !estado.search && !estado.status && !estado.prioridade && !estado.data_de && !estado.data_ate;

  if (!totalGeral) {
    tbody.innerHTML = '';
    emptyState.innerHTML = semFiltros
      ? Layout.emptyState({
        icon: 'fa-clipboard-list',
        title: 'Nenhuma tarefa cadastrada',
        message: 'Crie a primeira tarefa para comecar a organizar o time.',
        actionLabel: 'Criar primeira tarefa',
        onAction: () => abrirModal(),
      })
      : Layout.emptyState({
        icon: 'fa-magnifying-glass',
        title: 'Nenhum resultado',
        message: 'Nenhuma tarefa corresponde aos filtros aplicados.',
      });
    return;
  }
  emptyState.innerHTML = '';

  tbody.innerHTML = tarefas.map((t, i) => `
    <tr class="fade-in fade-in-${Math.min(i, 4)}">
      <td class="fw-bold">${Layout.escapeHtml(t.nome)}</td>
      <td>${formatDate(t.data)}</td>
      <td>${badgeStatus(t)}</td>
      <td>${badgePrioridade(t.prioridade)}</td>
      <td class="text-muted">${Layout.escapeHtml(truncar(t.descricao, 60))}</td>
      <td>
        <div class="row-actions">
          <button class="btn btn-sm btn-outline-secondary" onclick="abrirModal(${t.id})" title="Editar" aria-label="Editar ${Layout.escapeHtml(t.nome)}">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="removerTarefa(${t.id})" title="Remover" aria-label="Remover ${Layout.escapeHtml(t.nome)}">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function badgeStatus(tarefa) {
  if (tarefa.atrasada) {
    return '<span class="badge-status badge-status-atrasada"><i class="fa-solid fa-circle"></i> Atrasada</span>';
  }
  const labels = { pendente: 'Pendente', em_andamento: 'Em andamento', concluida: 'Concluida' };
  return `<span class="badge-status badge-status-${tarefa.status}"><i class="fa-solid fa-circle"></i> ${labels[tarefa.status] || tarefa.status}</span>`;
}

function badgePrioridade(prioridade) {
  const labels = { baixa: 'Baixa', media: 'Media', alta: 'Alta' };
  return `<span class="badge-prioridade badge-prioridade-${prioridade}">${labels[prioridade] || prioridade}</span>`;
}

function abrirModal(id) {
  const form = document.getElementById('tarefaForm');
  form.reset();
  Layout.applyValidationErrors(form, null);
  document.getElementById('tarefaNomeHint').textContent = '';

  const tarefa = id ? cacheTarefaPorId.get(id) : null;

  document.getElementById('tarefaModalTitle').textContent = tarefa ? 'Editar tarefa' : 'Nova tarefa';
  document.getElementById('tarefaId').value = tarefa ? tarefa.id : '';
  document.getElementById('tarefaNome').value = tarefa ? tarefa.nome : '';
  document.getElementById('tarefaData').value = tarefa ? tarefa.data : '';
  document.getElementById('tarefaStatus').value = tarefa ? tarefa.status : 'pendente';
  document.getElementById('tarefaPrioridade').value = tarefa ? tarefa.prioridade : 'media';
  document.getElementById('tarefaDescricao').value = tarefa ? tarefa.descricao || '' : '';

  tarefaModal.show();
}

/**
 * Feedback instantaneo (sem esperar o submit): avisa se ja existe uma
 * tarefa com esse nome, comparando com os itens ja carregados na pagina
 * atual (uma checagem exaustiva exigiria round-trip a API a cada tecla;
 * o backend segue sendo a fonte de verdade e valida de novo no submit).
 */
function verificarNomeDuplicado() {
  const hint = document.getElementById('tarefaNomeHint');
  const idAtual = document.getElementById('tarefaId').value;
  const nome = document.getElementById('tarefaNome').value.trim();

  if (!nome) {
    hint.className = 'field-hint neutral';
    hint.textContent = '';
    return;
  }

  const duplicada = Array.from(cacheTarefaPorId.values())
    .some((t) => t.nome.toLowerCase() === nome.toLowerCase() && String(t.id) !== idAtual);

  if (duplicada) {
    hint.className = 'field-hint bad';
    hint.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Ja existe uma tarefa com esse nome nesta pagina';
  } else {
    hint.className = 'field-hint neutral';
    hint.innerHTML = '';
  }
}

async function salvarTarefa(event) {
  event.preventDefault();
  const form = event.target;
  const id = document.getElementById('tarefaId').value;
  const btn = document.getElementById('tarefaSubmitBtn');

  const payload = {
    nome: document.getElementById('tarefaNome').value.trim(),
    data: document.getElementById('tarefaData').value,
    status: document.getElementById('tarefaStatus').value,
    prioridade: document.getElementById('tarefaPrioridade').value,
    descricao: document.getElementById('tarefaDescricao').value.trim() || null,
  };

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-inline"></span>';

  try {
    if (id) {
      await Api.put(`/tarefas/${id}`, payload);
      Layout.toast('Tarefa atualizada com sucesso.');
    } else {
      await Api.post('/tarefas', payload);
      Layout.toast('Tarefa criada com sucesso.');
    }
    tarefaModal.hide();
    await carregarTarefas();
  } catch (err) {
    if (err.status === 422) {
      Layout.applyValidationErrors(form, err.errors);
    } else {
      Layout.toast(err.message || 'Erro ao salvar a tarefa.', 'danger');
    }
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Salvar';
  }
}

async function removerTarefa(id) {
  const tarefa = cacheTarefaPorId.get(id);
  const confirmado = await Layout.confirmAction({
    title: 'Remover tarefa',
    message: `Tem certeza que deseja remover a tarefa <strong>${Layout.escapeHtml(tarefa?.nome || '')}</strong>? Membros da equipe vinculados a ela serao desvinculados automaticamente.`,
    confirmLabel: 'Remover',
  });
  if (!confirmado) return;

  try {
    await Api.delete(`/tarefas/${id}`);
    Layout.toast('Tarefa removida.');
    await carregarTarefas();
  } catch (err) {
    Layout.toast(err.message || 'Erro ao remover a tarefa.', 'danger');
  }
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('pt-BR');
}

function truncar(texto, tamanho) {
  if (!texto) return '—';
  return texto.length > tamanho ? `${texto.slice(0, tamanho)}…` : texto;
}
