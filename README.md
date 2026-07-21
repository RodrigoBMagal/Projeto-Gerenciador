# Projeto Gerenciador

Sistema de gerenciamento de tarefas e equipe, reescrito a partir do template
original (HTML estático + scripts PHP soltos) como uma aplicação moderna de
duas partes:

- **[`projeto-gerenciador/`](./projeto-gerenciador)** — API REST em Laravel
  (MVC, ORM, autenticação via Sanctum, testes com cobertura, documentação
  OpenAPI/Swagger automática, cache em Redis, logs estruturados, CI no
  GitHub Actions, Docker). Veja o README dentro da pasta para todos os
  detalhes de arquitetura e como rodar.

- **[`frontend/`](./frontend)** — Front-end estático (HTML/CSS/JS puro, sem
  build step) que consome essa API: login/registro reais, dashboard, CRUD de
  tarefas e CRUD de equipe (com atribuição de tarefas e cadastro em lote).

## Como rodar os dois juntos

```bash
# 1) Backend (API)
cd projeto-gerenciador
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 2) Front-end (em outro terminal)
cd ../frontend
python3 -m http.server 5173
```

Depois abra `http://localhost:5173/login.html`, crie uma conta (ou use o
usuário do seed: `admin@gerenciador.local`) e navegue pelo dashboard, tarefas
e equipe. A documentação interativa da API fica em
`http://localhost:8000/docs/api`.
