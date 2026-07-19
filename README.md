# Projeto Gerenciador — API (Laravel)

Reescrita do projeto original (HTML/jQuery + scripts PHP soltos com PDO direto)
como uma **API REST em Laravel**, seguindo arquitetura MVC, com ORM, autenticação
por token, testes automatizados, versionamento de banco de dados via migrations
e execução em containers Docker.

## O que mudou em relação ao projeto original

| Antes | Agora |
|---|---|
| Scripts soltos em `/php/*.php`, cada um abrindo sua própria conexão PDO | Camadas **Controller → Service → Repository → Model (Eloquent)** |
| Regra de negócio misturada com SQL dentro do endpoint (`apagar_item.php`, `update_tarefa.php` etc.) | Regra de negócio centralizada em `app/Services/*` — os endpoints (`Controllers`) só traduzem HTTP |
| Sem autenticação real (login/registro eram apenas telas estáticas) | Autenticação por token via **Laravel Sanctum** |
| `staff.tarefa_id` guardava o **nome** da tarefa (string) | `staff.tarefa_id` é uma **foreign key** de verdade para `tarefas.id` |
| Sem testes | Testes de Feature (endpoints) e Unit (regra de negócio) com PHPUnit |
| Sem controle de versão do schema | Migrations do Laravel |
| Credenciais de banco fixas no código (`root`, senha vazia) | Configuração via `.env` |
| Sem Docker | `docker-compose` com app (PHP-FPM), Nginx, MySQL e phpMyAdmin |

## Arquitetura

```
app/
  Http/
    Controllers/Api/   -> endpoints REST (finos, sem regra de negócio)
    Requests/           -> validação de entrada (Form Requests)
    Resources/           -> formatação da resposta JSON
  Services/             -> regras de negócio (equivalente à lógica que
                            antes vivia nos arquivos soltos em /php)
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
```

Isso resolve o pedido de **desconcentrar as regras de negócio dos arquivos de
endpoint**: um `Controller` nunca fala com o banco diretamente — ele delega
para um `Service`, que por sua vez usa um `Repository` (que encapsula o
Eloquent). Trocar de banco, adicionar cache ou reaproveitar a regra de negócio
em um comando de console, por exemplo, não exige tocar nos endpoints.

## Domínio

- **Tarefa**: `nome` (único), `data`, `descricao`.
- **Staff**: `nome`, `cargo`, `local`, `idade`, `contrato`, `salario`, e
  opcionalmente uma `tarefa` atribuída (`tarefa_id`).
- Ao remover uma tarefa, qualquer membro da equipe vinculado a ela é
  automaticamente desvinculado (regra que antes estava em `apagar_item.php`).

## Endpoints principais

Todas as rotas abaixo (exceto registro/login) exigem o header
`Authorization: Bearer {token}` obtido no login.

| Método | Rota | Descrição | Equivalente antigo |
|---|---|---|---|
| POST | `/api/register` | Cria usuário e retorna token | — |
| POST | `/api/login` | Autentica e retorna token | `login.html` (estático) |
| POST | `/api/logout` | Revoga o token atual | — |
| GET | `/api/me` | Dados do usuário autenticado | — |
| GET | `/api/tarefas` | Lista tarefas | `obter_tarefas.php` |
| POST | `/api/tarefas` | Cria tarefa (valida nome único) | `processar_formulario.php` + `verificar_nome.php` |
| GET | `/api/tarefas/{id}` | Detalha uma tarefa | `get_tarefa.php` |
| PUT/PATCH | `/api/tarefas/{id}` | Atualiza tarefa | — |
| DELETE | `/api/tarefas/{id}` | Remove tarefa e desvincula staff | `apagar_item.php` |
| GET | `/api/staff?search=` | Lista/pesquisa membros da equipe | `load_table_data.php` |
| POST | `/api/staff` | Cria um membro da equipe | — |
| POST | `/api/staff/lote` | Cria vários membros de uma vez | `save_table_data.php` |
| GET | `/api/staff/{id}` | Detalha um membro | — |
| PUT/PATCH | `/api/staff/{id}` | Atualiza dados do membro | — |
| DELETE | `/api/staff/{id}` | Remove membro | — |
| PATCH | `/api/staff/{id}/tarefa` | Atribui uma tarefa (por nome) ao membro | `update_tarefa.php` |
| GET | `/api/staff/com-tarefa` | Lista membros com tarefa atribuída | `load_staff.php` |

## Como rodar com Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

A API sobe em `http://localhost:8000`. O phpMyAdmin fica em
`http://localhost:8080`.

O seeder cria um usuário de teste: `admin@gerenciador.local` / senha gerada
pela factory (`password`, ver `database/factories/UserFactory.php`).

## Como rodar localmente (sem Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
# ajuste DB_HOST=127.0.0.1 no .env se o MySQL estiver na sua máquina
php artisan migrate --seed
php artisan serve
```

## Testes automatizados

```bash
composer install
php artisan test
# ou
./vendor/bin/phpunit
```

Os testes usam SQLite em memória (`phpunit.xml`), então não afetam o banco
de desenvolvimento. Cobrem:
- registro/login/logout e proteção de rotas por token (`tests/Feature/AuthTest.php`)
- CRUD e regras de negócio de tarefas, incluindo nome duplicado e desvínculo
  em cascata (`tests/Feature/TarefaApiTest.php`, `tests/Unit/TarefaServiceTest.php`)
- CRUD, cadastro em lote e atribuição de tarefas do staff (`tests/Feature/StaffApiTest.php`)

## Versionamento de banco de dados

O schema é controlado inteiramente por migrations (`database/migrations`).
Para aplicar: `php artisan migrate`. Para reverter a última leva de
alterações: `php artisan migrate:rollback`. Nunca altere o banco manualmente;
qualquer mudança de schema deve virar uma nova migration.

## Adaptando o front-end existente

As páginas estáticas (`index.html`, `tables.html`, `js/form.js`, `js/table.js`)
continuam funcionando como referência visual, mas apontam para os antigos
scripts PHP. Para usá-las com a nova API, troque as URLs dos `$.ajax(...)` de
`http://localhost/Projeto-Gerenciador/php/*.php` para `http://localhost:8000/api/*`,
enviando o token obtido em `/api/login` no header `Authorization`. O ideal a
médio prazo é migrar esse front-end para um SPA (React/Vue) consumindo a API.

## Próximos passos sugeridos

- Adicionar `spatie/laravel-permission` se surgir necessidade de papéis/permissões.
- Gerar documentação OpenAPI/Swagger automaticamente (ex.: `dedoc/scramble` ou
  `darkaonline/l5-swagger`).
- Adicionar paginação em `/api/tarefas` e `/api/staff` quando o volume de dados
  crescer.
