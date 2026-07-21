# Projeto Gerenciador — Front-end

Front-end estático (HTML + CSS + JavaScript puro, sem build step) que consome
a [API Laravel](../projeto-gerenciador) do projeto: autenticação, dashboard,
CRUD de tarefas e CRUD de equipe (staff), incluindo atribuição de tarefas.

Não depende de Node/npm/bundler — é só apontar um servidor estático (ou o
próprio navegador) para a pasta e configurar a URL da API.

## Como rodar

```bash
cd frontend
python3 -m http.server 5173
# ou: npx serve .
```

Acesse `http://localhost:5173/login.html`. Se a API não estiver em
`http://localhost:8000`, abra **"Configuração avançada"** na tela de login e
informe a URL correta (fica salva no `localStorage`).

## Telas

| Arquivo | Descrição |
|---|---|
| `login.html` | Autenticação (`POST /api/login`) |
| `register.html` | Criação de conta (`POST /api/register`), com validação em tempo real |
| `index.html` | Dashboard: cards de totais, tarefas próximas, gráfico de equipe por contrato, gráfico de tarefas por mês |
| `tarefas.html` | Listagem com busca, filtro por período, ordenação, paginação, CRUD |
| `staff.html` | Listagem com busca via API, filtro por contrato, ordenação, paginação, CRUD, atribuição de tarefa e cadastro em lote |

## Melhorias de UI/UX desta revisão

### 1. Responsividade
- Sidebar colapsa automaticamente em telas `< 992px`, com **menu hambúrguer**
  (`aria-expanded`/`aria-controls`) e um *backdrop* que fecha o menu ao
  clicar fora ou ao navegar.
- Tabelas com rolagem horizontal (`.table-scroll`) em telas estreitas.
- Cards do dashboard reorganizam-se em coluna única no celular (grid do
  Bootstrap).
- Modais usam `modal-fullscreen-sm-down`: em telas pequenas o formulário
  ocupa a tela inteira, mais confortável para digitar.

### 2. Estados da interface
- **Loading**: linhas de tabela em *skeleton* (`Layout.loadingRows()`) em vez
  de "Carregando...".
- **Vazio**: componente com ícone, título, mensagem e call-to-action
  (`Layout.emptyState()`) — ex. "Nenhuma tarefa cadastrada" + botão
  "Criar primeira tarefa".
- **Erro**: componente dedicado com botão "Tentar novamente"
  (`Layout.errorState()`), usado no dashboard, em tarefas e em staff sempre
  que uma chamada à API falha.

### 3. Notificações (toast) e confirmações
- Integração com **SweetAlert2** (via CDN) para toasts (`Layout.toast()`) e
  confirmações de exclusão (`Layout.confirmAction()`), com *fallback*
  automático para um toast/modal próprios caso o CDN não carregue.

### 4. Dashboard mais rico
- Cards de tarefas: total, **pendentes**, **em andamento**, **concluídas** e
  **atrasadas** — vindos de `GET /api/tarefas/estatisticas` (contagem
  agregada no banco, cacheada em Redis).
- Cards de equipe: total, com/sem tarefa atribuída — vindos de
  `GET /api/staff/estatisticas`.
- Gráfico de pizza (equipe por tipo de contrato, a partir de
  `estatisticas.por_contrato`) e gráfico de barras (tarefas cadastradas por
  mês, a partir de `estatisticas.por_mes`).
- "Próximas tarefas" usa a própria listagem paginada
  (`GET /api/tarefas?sort_by=data&sort_dir=asc&per_page=6`).
- > O gráfico de **"produtividade por funcionário"** sugerido no pedido
  > original ainda não foi implementado: hoje cada membro da equipe só pode
  > ter **uma** tarefa por vez (`staff.tarefa_id`), então não há um
  > histórico de tarefas concluídas por pessoa para basear esse gráfico. Ver
  > [Limitações e próximos passos](#limitações-e-próximos-passos).

### 5. Pesquisa instantânea
- Tarefas: busca com *debounce* de 300 ms direto na API (`GET /tarefas?search=`).
- Staff: busca com *debounce* de 300 ms direto na API (`GET /staff?search=`).

### 6. Filtros
- Tarefas: filtro por **status** (pendente/em andamento/concluída),
  **prioridade** (baixa/média/alta) e por período (data de/até) — todos
  aplicados no backend via query string.
- Staff: filtro por tipo de contrato (CLT/PJ/Estágio/Temporário, múltipla
  escolha) e por "somente com tarefa atribuída" — também resolvidos no
  backend (`GET /staff?contrato=CLT,PJ&com_tarefa=true`).

### 7. Ordenação
- Cabeçalhos clicáveis (`Layout.bindSortableHeaders()`) com indicador de
  direção (▲/▼), agora **resolvidos pela API** (`sort_by`/`sort_dir`): tarefas
  por nome/data/status/prioridade; staff por nome/cargo/idade/salário.

### 8. Paginação
- Paginação **server-side de verdade**: `GET /tarefas` e `GET /staff` retornam
  um envelope `{ data, meta, links }` (Laravel `LengthAwarePaginator`); o
  front só desenha os botões (`Layout.paginationHtml()` +
  `Layout.bindPagination()`) a partir de `meta.current_page`/`meta.last_page`
  e troca de página trocando o `?page=` da requisição — 8 itens por página.

### 9. Tema claro/escuro

- Botão 🌙/🌞 na barra superior (`assets/js/theme.js`), com preferência
  salva em `localStorage` e aplicada antes do primeiro *paint* (script
  inline no `<head>` de cada página, evitando "flash" de tema errado).
- Paleta inteira definida via CSS custom properties, com um conjunto
  dedicado para `[data-theme="dark"]`.

### 10. Formulários com validação em tempo real
- Registro: feedback instantâneo de formato de e-mail, tamanho mínimo de
  senha e coincidência da confirmação, enquanto o usuário digita.
- Tarefas: aviso imediato se já existe uma tarefa com o nome digitado na
  **página atual já carregada** (checagem definitiva continua sendo feita
  pela API no submit, que é quem valida contra a tabela inteira).
- Erros de validação `422` retornados pela API continuam sendo aplicados
  nos campos certos via `Layout.applyValidationErrors()`.

### 11. Confirmação de exclusões
- Todas as remoções (tarefa e staff) passam por `Layout.confirmAction()`
  (SweetAlert2), nunca são feitas direto no clique.

### 12. Avatar e saudação do usuário
- Avatar com iniciais + nome/e-mail no menu do usuário na barra superior.

### 13. Breadcrumb
- `Layout.setBreadcrumb([...])` renderiza a trilha (`Dashboard > Tarefas`,
  `Dashboard > Equipe`) no topo de cada página.

### 14. Ícones padronizados
- Font Awesome com tamanho/cor consistentes; ícones decorativos marcados
  `aria-hidden="true"`; ícones que são a única pista de uma ação (editar,
  remover, atribuir) recebem `aria-label`.

### 15. Componentização (sem framework)
- `assets/js/layout.js` concentra os "componentes" reutilizáveis:
  `emptyState`, `errorState`, `loadingRows`, `paginationHtml` +
  `bindPagination`, `bindSortableHeaders`, `toast`, `confirmAction`,
  `applyValidationErrors`, `setBreadcrumb`. Cada página (`dashboard.js`,
  `tarefas.js`, `staff.js`) só monta a página chamando essas funções — não
  há HTML de layout duplicado entre elas.

### 16. Centralização das requisições
- Já existia e continua sendo o único ponto de acesso à API:
  `Api.get/post/put/patch/delete` (`assets/js/api.js`), com tratamento
  uniforme de token, erros e expiração de sessão (401).

### 17. Acessibilidade
- `aria-label` em botões só-ícone; `aria-current="page"` no item ativo do
  menu; `aria-expanded`/`aria-controls` no menu mobile; região
  `aria-live="polite"` para toasts; link "Pular para o conteúdo"
  (*skip link*); estados de foco visíveis (`:focus-visible`) em toda a
  aplicação, inclusive no tema escuro.

### 18. Animações discretas
- *Fade-in* leve e escalonado em linhas de tabela e cards ao carregar;
  `prefers-reduced-motion: reduce` respeitado (anima menos para quem pediu
  isso no sistema operacional).

### 19. Atualização "em tempo real" das telas
- Após criar/editar/remover uma tarefa ou membro, a tabela e os contadores
  da própria página são atualizados imediatamente (sem recarregar a
  página), pois cada ação já dispara um novo carregamento dos dados
  daquela tela.
- > Atualização automática do dashboard **em outra aba/página aberta** (sem
  > polling) exigiria WebSockets/SSE — fora do escopo de um front estático
  > sem backend de push; não implementado.

### 20. Visual geral
- Paleta neutra com acentos de cor (inspirada em Notion/Linear/GitHub),
  cantos mais arredondados, sombras suaves, tipografia **Inter** para UI e
  **Nunito** como fallback, mais espaçamento e hierarquia visual mais clara
  entre cards, tabelas e painéis.

## Limitações e próximos passos

A rodada anterior desta revisão apontava que status, prioridade e paginação
"de verdade" dependiam de mudanças no backend — isso **já foi feito**: veja o
[README do backend](../projeto-gerenciador#endpoints-principais) para a nova
migration (`status`/`prioridade` em `tarefas`), os endpoints de estatísticas
(`/tarefas/estatisticas`, `/staff/estatisticas`) e a paginação/filtros
server-side em `GET /tarefas` e `GET /staff`.

O que ainda fica de fora, por depender de uma mudança maior de modelo de
dados (não só um campo novo):

- **Produtividade por funcionário** — hoje cada membro da equipe tem no
  máximo **uma** tarefa por vez (`staff.tarefa_id`). Um gráfico de
  produtividade "de verdade" (quantas tarefas cada pessoa concluiu ao longo
  do tempo) exigiria uma relação muitos-para-muitos entre `staff` e
  `tarefas` com histórico, em vez da FK simples atual.
- **Dashboard atualizando sozinho em outra aba/página aberta** (sem
  recarregar) — exigiria WebSockets/SSE no backend; fora do escopo de um
  front estático sem servidor de push. Dentro da mesma tela, a atualização já
  é imediata após qualquer criação/edição/remoção (ver item 19 acima).

Se esses dois pontos importarem no futuro, o caminho natural é: uma tabela
pivô `staff_tarefa` com histórico (substituindo o `tarefa_id` único) para
produtividade, e Laravel Echo + um driver de broadcast (Pusher/Soketi) para
tempo real entre abas.

## Estrutura

```
frontend/
  login.html / register.html   -> páginas de autenticação
  index.html                   -> dashboard
  tarefas.html                 -> CRUD de tarefas
  staff.html                   -> CRUD de equipe + atribuição de tarefa
  assets/
    css/style.css               -> design tokens (claro/escuro), layout,
                                    componentes, responsividade, animações
    js/config.js                 -> URL base da API
    js/api.js                    -> wrapper de fetch (token, erros, 401)
    js/theme.js                  -> alternância de tema claro/escuro
    js/layout.js                 -> shell (sidebar/topbar/breadcrumb) +
                                     biblioteca de componentes reutilizáveis
                                     (toast, confirmação, estados, paginação,
                                     ordenação)
    js/dashboard.js              -> lógica do dashboard
    js/tarefas.js                 -> lógica da tela de tarefas
    js/staff.js                   -> lógica da tela de equipe
```

## Bibliotecas usadas (via CDN, sem instalação)

- [Bootstrap 5](https://getbootstrap.com/) — grid, modais, formulários
- [Font Awesome 6](https://fontawesome.com/) — ícones
- [Chart.js 4](https://www.chartjs.org/) — gráficos do dashboard
- [SweetAlert2](https://sweetalert2.github.io/) — toasts e confirmações

## CORS

O backend já vem configurado (`config/cors.php`) para aceitar requisições de
qualquer origem (`CORS_ALLOWED_ORIGINS=*` por padrão), então servir este
front-end em uma porta diferente da API funciona sem configuração adicional.
Como a autenticação usa token Bearer (não cookies), não é necessário
`supports_credentials` nem configurar `SANCTUM_STATEFUL_DOMAINS`.
