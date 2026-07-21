Layout.mount('dashboard');
Layout.setBreadcrumb([{ label: 'Dashboard' }]);

let contratoChartInstance = null;
let evolucaoChartInstance = null;

initDashboard();

async function initDashboard() {
  renderSkeletons();

  try {
    const [estatisticasTarefas, estatisticasStaff, proximasResposta] = await Promise.all([
      Api.get('/tarefas/estatisticas'),
      Api.get('/staff/estatisticas'),
      Api.get('/tarefas?sort_by=data&sort_dir=asc&per_page=6'),
    ]);

    renderStats(estatisticasTarefas, estatisticasStaff);
    renderProximasTarefas(proximasResposta.data || []);
    renderContratoChart(estatisticasStaff.por_contrato || {});
    renderEvolucaoChart(estatisticasTarefas.por_mes || {});
  } catch (err) {
    document.getElementById('proximasTarefas').innerHTML = Layout.errorState({
      message: err.message,
      onRetry: initDashboard,
    });
  }
}

function renderSkeletons() {
  ['statTotal', 'statPendente', 'statEmAndamento', 'statConcluida', 'statAtrasada', 'statStaffTotal', 'statStaffComTarefa', 'statStaffSemTarefa'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = '<span class="skeleton d-inline-block" style="width:2.5rem;height:1.2rem"></span>';
  });
  document.getElementById('proximasTarefas').innerHTML = `
    <div class="skeleton mb-2" style="height:1.6rem"></div>
    <div class="skeleton mb-2" style="height:1.6rem"></div>
    <div class="skeleton" style="height:1.6rem"></div>
  `;
}

function renderStats(estatisticasTarefas, estatisticasStaff) {
  setStat('statTotal', estatisticasTarefas.total);
  setStat('statPendente', estatisticasTarefas.pendente);
  setStat('statEmAndamento', estatisticasTarefas.em_andamento);
  setStat('statConcluida', estatisticasTarefas.concluida);
  setStat('statAtrasada', estatisticasTarefas.atrasada);

  setStat('statStaffTotal', estatisticasStaff.total);
  setStat('statStaffComTarefa', estatisticasStaff.com_tarefa);
  setStat('statStaffSemTarefa', estatisticasStaff.sem_tarefa);
}

function setStat(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value ?? 0;
}

function renderProximasTarefas(tarefas) {
  const container = document.getElementById('proximasTarefas');

  if (!tarefas.length) {
    container.innerHTML = Layout.emptyState({
      icon: 'fa-clipboard-list',
      title: 'Nenhuma tarefa cadastrada',
      message: 'Crie a primeira tarefa para comecar a acompanhar o time.',
      actionLabel: 'Criar primeira tarefa',
      onAction: () => { window.location.href = 'tarefas.html'; },
    });
    return;
  }

  container.innerHTML = `
    <div class="table-scroll">
      <table class="table app-table mb-0">
        <thead><tr><th>Tarefa</th><th>Data</th><th>Status</th></tr></thead>
        <tbody>
          ${tarefas.map((t, i) => `
            <tr class="fade-in fade-in-${Math.min(i, 4)}">
              <td class="fw-bold">${Layout.escapeHtml(t.nome)}</td>
              <td>${formatDate(t.data)}</td>
              <td>${badgeStatus(t)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

function badgeStatus(tarefa) {
  if (tarefa.atrasada) {
    return '<span class="badge-status badge-status-atrasada"><i class="fa-solid fa-circle"></i> Atrasada</span>';
  }
  const labels = { pendente: 'Pendente', em_andamento: 'Em andamento', concluida: 'Concluida' };
  return `<span class="badge-status badge-status-${tarefa.status}"><i class="fa-solid fa-circle"></i> ${labels[tarefa.status] || tarefa.status}</span>`;
}

function renderContratoChart(porContrato) {
  const canvas = document.getElementById('contratoChart');
  const emptyState = document.getElementById('contratoChartEmpty');

  if (contratoChartInstance) contratoChartInstance.destroy();

  const entradas = Object.entries(porContrato);
  if (!entradas.length) {
    canvas.classList.add('d-none');
    emptyState.classList.remove('d-none');
    emptyState.innerHTML = Layout.emptyState({ icon: 'fa-chart-pie', title: 'Sem dados ainda', message: 'Cadastre membros da equipe para ver este grafico.' });
    return;
  }
  canvas.classList.remove('d-none');
  emptyState.classList.add('d-none');

  contratoChartInstance = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: entradas.map(([label]) => label),
      datasets: [{
        data: entradas.map(([, total]) => total),
        backgroundColor: ['#4e5df9', '#1cae7a', '#2ba8c4', '#d99a1e', '#e0433a', '#8b8fa3'],
        borderWidth: 0,
      }],
    },
    options: {
      plugins: { legend: { position: 'bottom', labels: { color: getComputedStyle(document.body).color } } },
      cutout: '65%',
    },
  });
}

function renderEvolucaoChart(porMes) {
  const canvas = document.getElementById('evolucaoChart');
  const emptyState = document.getElementById('evolucaoChartEmpty');

  if (evolucaoChartInstance) evolucaoChartInstance.destroy();

  const chavesOrdenadas = Object.keys(porMes).sort();
  if (!chavesOrdenadas.length) {
    canvas.classList.add('d-none');
    emptyState.classList.remove('d-none');
    emptyState.innerHTML = Layout.emptyState({ icon: 'fa-chart-column', title: 'Sem dados ainda', message: 'Cadastre tarefas para ver a evolucao mensal.' });
    return;
  }
  canvas.classList.remove('d-none');
  emptyState.classList.add('d-none');

  const labels = chavesOrdenadas.map((chave) => {
    const [ano, mes] = chave.split('-');
    return new Date(Number(ano), Number(mes) - 1, 1).toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
  });

  evolucaoChartInstance = new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Tarefas',
        data: chavesOrdenadas.map((chave) => porMes[chave]),
        backgroundColor: '#4e5df9',
        borderRadius: 6,
        maxBarThickness: 28,
      }],
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: getComputedStyle(document.body).color }, grid: { color: 'rgba(128,128,128,0.15)' } },
        x: { ticks: { color: getComputedStyle(document.body).color }, grid: { display: false } },
      },
    },
  });
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('pt-BR');
}
