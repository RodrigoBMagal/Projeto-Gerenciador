# Projeto Gerenciador — API (Laravel)

[![CI](https://github.com/SEU_USUARIO/SEU_REPOSITORIO/actions/workflows/ci.yml/badge.svg)](https://github.com/SEU_USUARIO/SEU_REPOSITORIO/actions/workflows/ci.yml)

Reescrita do projeto original (HTML/jQuery + scripts PHP soltos com PDO direto)
como uma **API REST em Laravel**, com arquitetura MVC, ORM, autenticação por
token, testes automatizados com cobertura, documentação OpenAPI gerada
automaticamente, cache em Redis, logs estruturados, CI no GitHub Actions e
execução em containers Docker.

> Troque `SEU_USUARIO/SEU_REPOSITORIO` no badge acima pelo caminho real do
> repositório assim que fizer o push para o GitHub.

## Sumário

- [O que mudou em relação ao projeto original](#o-que-mudou-em-relação-ao-projeto-original)
- [Arquitetura](#arquitetura)
- [Domínio](#domínio)
- [Endpoints principais](#endpoints-principais)
- [Documentação da API (Swagger/OpenAPI)](#documentação-da-api-swaggeropenapi)
- [Como rodar com Docker](#como-rodar-com-docker)
- [Como rodar localmente (sem Docker)](#como-rodar-localmente-sem-docker)
- [Testes automatizados e cobertura](#testes-automatizados-e-cobertura)
- [Integração contínua (CI)](#integração-contínua-ci)
- [Cache (Redis)](#cache-redis)
- [Logs estruturados e monitoramento](#logs-estruturados-e-monitoramento)
- [Versionamento de banco de dados](#versionamento-de-banco-de-dados)
- [Adaptando o front-end existente](#adaptando-o-front-end-existente)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Próximos passos sugeridos](#próximos-passos-sugeridos)

## O que mudou em relação ao projeto original

| Antes | Agora |
|---|---|
| Scripts soltos em `/php/*.php`, cada um abrindo sua própria conexão PDO | Camadas **Controller → Service → Repository → Model (Eloquent)** |
| Regra de negócio misturada com SQL dentro do endpoint | Regra de negócio centralizada em `app/Services/*` — os endpoints só traduzem HTTP |
| Sem autenticação real | Autenticação por token via **Laravel Sanctum** |
| `staff.tarefa_id` guardava o **nome** da tarefa (string) | `staff.tarefa_id` é uma **foreign key** de verdade para `tarefas.id` |
| Sem testes | Testes de Feature e Unit com PHPUnit, com **relatório de cobertura** |
| Sem documentação de API | **OpenAPI/Swagger gerado automaticamente** a partir do código (Scramble) |
| Sem CI | Pipeline no **GitHub Actions**: lint, testes, cobertura, build da imagem |
| Sem cache | **Redis** para as consultas mais frequentes (listagem de tarefas e staff) |
| Logs de erro do PHP padrão | Canal de **logs estruturados em JSON** + endpoint de health-check |
| Sem controle de versão do schema | Migrations do Laravel |
| Sem Docker | `docker-compose` com app (PHP-FPM), Nginx, MySQL, Redis e phpMyAdmin |

## Arquitetura

```
app/
  Http/
    Controllers/Api/   -> endpoints REST (finos, sem regra de negócio)
    Requests/           -> validação de entrada (Form Requests)
    Resources/           -> formatação da resposta JSON
    Middleware/
      LogApiRequests.php -> loga cada requisição em formato estruturado
  Services/             -> regras de negócio + cache das consultas frequentes
  Repositories/
    Contracts/           -> interfaces (abstração sobre o ORM)
    Eloquent/             -> implementação concreta usando Eloquent
  Models/                -> entidades do ORM (Tarefa, Staff, User)
routes/api.php           -> definição das rotas REST
database/migrations/     -> versionamento do schema do banco
database/factories/      -> geração de dados fake para testes/seed
database/seeders/        -> massa de dados inicial
tests/Feature/           -> testes de ponta a ponta dos endpoints
tests/Unit/               -> testes da camada de serviço isolada
docker/                  -> configuração do Nginx e do PHP
config/scramble.php      -> configuração da documentação OpenAPI
```

> O workflow de CI (`ci.yml`) fica em `/.github/workflows/` na **raiz do
> monorepo** (um nível acima desta pasta, ao lado de `frontend/`), não aqui
> dentro — o GitHub só reconhece workflows nesse local. Ele roda os comandos
> a partir de `projeto-gerenciador/` via `working-directory`.

Um `Controller` nunca fala com o banco diretamente — ele delega para um
`Service`, que usa um `Repository` (que encapsula o Eloquent). Isso resolve o
pedido original de **desconcentrar as regras de negócio dos arquivos de
endpoint**: trocar de banco, adicionar cache ou reaproveitar a regra de
negócio em um comando de console não exige tocar nos endpoints.

## Domínio

- **Tarefa**: `nome` (único), `data`, `descricao`, `status`
  (`pendente` | `em_andamento` | `concluida`, padrão `pendente`), `prioridade`
  (`baixa` | `media` | `alta`, padrão `media`) e `atrasada` (calculado — `true`
  quando a `data` já passou e o `status` ainda não é `concluida`; nunca é
  persistido, então nunca fica desatualizado).
- **Staff**: `nome`, `cargo`, `local`, `idade`, `contrato`, `salario`, e
  opcionalmente uma `tarefa` atribuída (`tarefa_id`).
- Ao remover uma tarefa, qualquer membro da equipe vinculado a ela é
  automaticamente desvinculado.

## Endpoints principais

Todas as rotas abaixo (exceto registro/login/health) exigem o header
`Authorization: Bearer {token}` obtido no login.

| Método | Rota | Descrição | Equivalente antigo |
|---|---|---|---|
| GET | `/api/health` | Health-check (banco + cache) | — |
| POST | `/api/register` | Cria usuário e retorna token | — |
| POST | `/api/login` | Autentica e retorna token | `login.html` (estático) |
| POST | `/api/logout` | Revoga o token atual | — |
| GET | `/api/me` | Dados do usuário autenticado | — |
| GET | `/api/tarefas` | Lista tarefas, **paginada e filtrável** (ver abaixo) | `obter_tarefas.php` |
| GET | `/api/tarefas/estatisticas` | Contagens agregadas para o dashboard (cacheada) | — |
| POST | `/api/tarefas` | Cria tarefa (valida nome único) | `processar_formulario.php` + `verificar_nome.php` |
| GET | `/api/tarefas/{id}` | Detalha uma tarefa | `get_tarefa.php` |
| PUT/PATCH | `/api/tarefas/{id}` | Atualiza tarefa | — |
| DELETE | `/api/tarefas/{id}` | Remove tarefa e desvincula staff | `apagar_item.php` |
| GET | `/api/staff` | Lista/pesquisa membros, **paginada e filtrável** (ver abaixo) | `load_table_data.php` |
| GET | `/api/staff/estatisticas` | Contagens agregadas para o dashboard (cacheada) | — |
| POST | `/api/staff` | Cria um membro da equipe | — |
| POST | `/api/staff/lote` | Cria vários membros de uma vez | `save_table_data.php` |
| GET | `/api/staff/{id}` | Detalha um membro | — |
| PUT/PATCH | `/api/staff/{id}` | Atualiza dados do membro | — |
| DELETE | `/api/staff/{id}` | Remove membro | — |
| PATCH | `/api/staff/{id}/tarefa` | Atribui uma tarefa (por nome) ao membro | `update_tarefa.php` |
| GET | `/api/staff/com-tarefa` | Lista (sem paginação) membros com tarefa atribuída | `load_staff.php` |

### Paginação e filtros

`GET /api/tarefas` e `GET /api/staff` retornam um envelope paginado:

```json
{
  "data": [ /* itens da pagina atual */ ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 10, "total": 47, "from": 1, "to": 10 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

Parâmetros de query aceitos:

| Endpoint | Parâmetros |
|---|---|
| `GET /api/tarefas` | `search`, `status`, `prioridade`, `data_de`, `data_ate`, `sort_by` (`nome`\|`data`\|`status`\|`prioridade`\|`created_at`), `sort_dir` (`asc`\|`desc`), `per_page` (máx. 100), `page` |
| `GET /api/staff` | `search`, `contrato` (um valor ou vários separados por vírgula, ex. `CLT,PJ`), `com_tarefa` (`true`\|`false`), `sort_by` (`nome`\|`cargo`\|`local`\|`idade`\|`contrato`\|`salario`\|`created_at`), `sort_dir`, `per_page` (máx. 100), `page` |

Os parâmetros são validados por Form Requests dedicados
(`ListarTarefasRequest`, `ListarStaffRequest`) — valores fora da lista
permitida (ex. `status=inexistente`) retornam `422`.

### Estatísticas (para o dashboard)

- `GET /api/tarefas/estatisticas` → `{ total, pendente, em_andamento, concluida, atrasada, por_mes }`
  (`por_mes` é um mapa `"AAAA-MM": quantidade`, usado no gráfico de evolução).
- `GET /api/staff/estatisticas` → `{ total, com_tarefa, sem_tarefa, por_contrato }`
  (`por_contrato` é um mapa `"CLT": quantidade`, usado no gráfico de pizza).

Essas consultas agregadas (não a listagem paginada — que tem cardinalidade
alta demais para valer a pena cachear) são cacheadas em Redis por 5 minutos e
invalidadas automaticamente a cada criação/atualização/remoção de tarefa ou
staff (ver `TarefaService`/`StaffService`).

## Documentação da API (Swagger/OpenAPI)

A documentação **não é escrita manualmente**: o pacote
[`dedoc/scramble`](https://scramble.dedoc.co) inspeciona as rotas, os Form
Requests (regras de validação, incluindo `ListarTarefasRequest` e
`ListarStaffRequest`) e os API Resources (formato de resposta) e gera a
especificação OpenAPI 3.1 automaticamente a cada requisição em ambiente de
desenvolvimento.

- **UI interativa (Swagger-like)**: `http://localhost:8000/docs/api`
- **Especificação em JSON**: `http://localhost:8000/docs/api.json`
  (pode ser importada em Postman/Insomnia)

Em ambiente local o acesso é livre; em outros ambientes, a `Gate`
`viewApiDocs` (definida em `app/Providers/AppServiceProvider.php`) exige um
usuário autenticado — ajuste essa regra conforme a política de acesso do seu
time (ex.: liberar só para um papel `admin`).

Para melhorar a documentação gerada, adicione doc-blocks PHP nos métodos dos
`Controllers` (resumo/descrição) — o Scramble já lê os que existem hoje em
`TarefaController`, `StaffController` e `AuthController`.

## Como rodar com Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

- API: `http://localhost:8000`
- Documentação OpenAPI: `http://localhost:8000/docs/api`
- phpMyAdmin: `http://localhost:8080`
- Redis: `localhost:6379` (usado automaticamente como cache, ver `.env`)

O seeder cria um usuário de teste: `admin@gerenciador.local` / senha definida
em `database/factories/UserFactory.php` (`password` por padrão).

## Como rodar localmente (sem Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
# ajuste DB_HOST=127.0.0.1 e REDIS_HOST=127.0.0.1 no .env
# se preferir não usar Redis localmente, defina CACHE_DRIVER=file
php artisan migrate --seed
php artisan serve
```

## Testes automatizados e cobertura

```bash
composer install

# rodar a suíte de testes
composer test
# equivalente a: vendor/bin/phpunit

# rodar com relatório de cobertura (usa a extensão PCOV, já incluída na
# imagem Docker; localmente instale com `pecl install pcov` ou use Xdebug)
composer test-coverage
```

O comando de cobertura gera:
- `storage/coverage.xml` — formato Clover, para ferramentas externas (Codecov, SonarQube)
- `storage/coverage-html/index.html` — relatório navegável no browser
- resumo em texto direto no terminal (`--coverage-text`)

Os testes usam SQLite em memória e o cache `array` (configurado em
`phpunit.xml`), então não afetam o banco/cache de desenvolvimento. Cobrem:
- registro/login/logout e proteção de rotas por token (`tests/Feature/AuthTest.php`)
- CRUD e regras de negócio de tarefas, incluindo nome duplicado, desvínculo em
  cascata e invalidação de cache (`tests/Feature/TarefaApiTest.php`, `tests/Unit/TarefaServiceTest.php`)
- CRUD, cadastro em lote e atribuição de tarefas do staff (`tests/Feature/StaffApiTest.php`)

Se preferir Xdebug em vez de PCOV (mais lento, porém com mais recursos de
debug), troque `coverage: pcov` por `coverage: xdebug` no workflow de CI e
instale a extensão localmente.

## Integração contínua (CI)

O workflow `.github/workflows/ci.yml` roda automaticamente em todo `push` e
`pull request` para `main`/`develop` e executa, para PHP 8.2 e 8.3:

1. **Lint de estilo** com Laravel Pint (`vendor/bin/pint --test`)
2. **Migrations** contra SQLite (checagem rápida de que o schema é válido)
3. **Testes automatizados com cobertura** (PHPUnit + PCOV), publicando:
   - um resumo de cobertura direto no *Job Summary* do GitHub Actions
   - os artefatos `coverage.xml` e `junit.xml` para download
   - upload opcional para o [Codecov](https://about.codecov.io/) (não falha o
     pipeline se o token não estiver configurado)
4. **Build da imagem Docker**, validando que o `Dockerfile` continua
   funcionando, como último job (`build-docker`), que só roda se os testes
   passarem

Para habilitar o upload real para o Codecov, adicione o secret
`CODECOV_TOKEN` nas configurações do repositório no GitHub (opcional — o
pipeline funciona sem ele).

## Cache (Redis)

As consultas agregadas usadas pelo dashboard — `GET /api/tarefas/estatisticas`
e `GET /api/staff/estatisticas` — passam por `Cache::remember(...)` com TTL
de 5 minutos, implementado em `TarefaService` e `StaffService`. São elas (e
não a listagem paginada) que ficam em cache, porque:

- São idênticas para qualquer usuário e consultadas a cada carregamento do
  dashboard — alta taxa de reaproveitamento.
- A listagem paginada (`GET /api/tarefas`, `GET /api/staff`) tem cardinalidade
  de chave muito alta (página × filtros × ordenação × busca), então cachear
  cada combinação traria pouco benefício e muita pressão de memória no Redis.

Qualquer criação/atualização/remoção de tarefa ou staff (inclusive atribuição
de tarefa) **invalida** as chaves de estatísticas na hora (`Cache::forget`),
então não há risco de o dashboard mostrar números desatualizados.

`GET /api/staff/com-tarefa` (lista completa, sem paginação) continua sendo
cacheada da mesma forma, já que também é um endpoint de baixa cardinalidade.

- O driver padrão é `redis` (via `.env`: `CACHE_DRIVER=redis`), usando o
  pacote `predis/predis` (cliente Redis 100% PHP, sem depender da extensão
  nativa `ext-redis`). Uma conexão dedicada (`REDIS_CACHE_DB=1`) mantém o
  cache separado de filas/sessões.
- Para desabilitar o cache (ex.: debugging local), defina `CACHE_DRIVER=file`
  ou `CACHE_DRIVER=array` no `.env`.

## Logs estruturados e monitoramento

- **`GET /api/health`** — endpoint de health-check público que testa a
  conexão com o banco e com o cache, retornando `200` (saudável) ou `503`
  (degradado) com a latência de cada verificação. Use-o em probes do
  Docker/Kubernetes ou em ferramentas de uptime (UptimeRobot, Pingdom,
  Datadog Synthetics etc.).
- **Canal `structured`** (`config/logging.php`) — grava cada linha de log
  como um objeto JSON (`storage/logs/structured.json`), pronto para ser
  coletado por Filebeat/Fluentd e enviado a um ELK Stack, Grafana Loki,
  Datadog ou CloudWatch. Ative como canal padrão com `LOG_CHANNEL=structured`
  no `.env` de produção.
- **`App\Http\Middleware\LogApiRequests`** — loga toda requisição da API
  (método, rota, status, tempo de resposta em ms, usuário autenticado, IP) no
  canal `structured`, com um `request_id` único devolvido também no header
  `X-Request-Id` da resposta (útil para rastrear uma requisição de ponta a
  ponta entre logs do front-end, da API e de serviços externos).
- Falhas do health-check também são logadas como `warning` no canal
  `structured`, facilitando alertas automáticos.

Para monitoramento de erros em produção (APM), o próximo passo natural é
integrar o [Sentry](https://sentry.io) (`composer require sentry/sentry-laravel`)
ou um agente de APM (New Relic, Datadog APM) — não incluído aqui para manter
o projeto sem dependência de um serviço pago específico.

## Versionamento de banco de dados

O schema é controlado inteiramente por migrations (`database/migrations`).
Para aplicar: `php artisan migrate`. Para reverter a última leva de
alterações: `php artisan migrate:rollback`. Nunca altere o banco manualmente;
qualquer mudança de schema deve virar uma nova migration.

## Adaptando o front-end existente

As páginas estáticas (`index.html`, `tables.html`, `js/form.js`, `js/table.js`)
continuam funcionando como referência visual, mas apontam para os antigos
scripts PHP. Para usá-las com a nova API, troque as URLs dos `$.ajax(...)`
para `http://localhost:8000/api/*`, enviando o token obtido em `/api/login`
no header `Authorization`. O ideal a médio prazo é migrar esse front-end para
um SPA (React/Vue) consumindo a API — a especificação OpenAPI em
`/docs/api.json` pode inclusive gerar um client TypeScript automaticamente
(ex.: `openapi-typescript`).

## Variáveis de ambiente

Principais variáveis novas em relação à primeira versão do projeto (veja
`.env.example` para a lista completa):

| Variável | Descrição | Padrão |
|---|---|---|
| `CACHE_DRIVER` | Driver de cache (`redis`, `file`, `array`) | `redis` |
| `REDIS_HOST` / `REDIS_PORT` | Conexão com o Redis | `redis` / `6379` |
| `REDIS_CACHE_DB` | Banco Redis dedicado ao cache de consultas | `1` |
| `LOG_CHANNEL` | Canal de log padrão (`stack`, `structured`, `single`) | `stack` |
| `LOG_STACK` | Canais combinados quando `LOG_CHANNEL=stack` | `single` |
| `API_VERSION` | Versão exibida na documentação OpenAPI | `1.0.0` |

## Próximos passos sugeridos

- Adicionar `spatie/laravel-permission` se surgir necessidade de papéis/permissões.
- Integrar Sentry ou outro APM para monitoramento de exceções em produção.
- Publicar a imagem Docker em um registry a partir do pipeline de CI, após os
  testes passarem, para automatizar o deploy.
- Trocar a relação `staff.tarefa_id` (um-para-muitos, um membro só pode ter
  uma tarefa por vez) por uma tabela pivô `staff_tarefa` com histórico, caso
  seja necessário um relatório de "produtividade por funcionário" ao longo
  do tempo, e não apenas a atribuição atual.
- WebSockets/SSE (Laravel Echo + Pusher/Soketi) caso seja necessário que o
  dashboard atualize sozinho quando outra aba/usuário altera dados.
