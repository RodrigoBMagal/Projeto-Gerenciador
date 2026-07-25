# Deploy no servidor caseiro (umbrelOS)

Roteiro para rodar este projeto num umbrelOS em hardware modesto (ex.: 4GB RAM, Celeron).
O umbrelOS é Linux + Docker por baixo, então o compose deste repo roda direto via SSH,
sem precisar passar pela App Store do Umbrel.

## 1. Habilitar SSH no Umbrel

No dashboard do Umbrel: **Settings > Advanced > Enable SSH**.

```bash
ssh umbrel@umbrel.local
```

## 2. Clonar e subir o projeto

```bash
git clone <seu-repo> ~/gerenciador
cd ~/gerenciador/projeto-gerenciador
cp .env.example .env   # ajustar DB_PASSWORD, APP_KEY etc.
docker compose up -d --build
docker compose exec app php artisan key:generate   # se APP_KEY ainda não estiver setada
```

phpMyAdmin fica fora do `up -d` padrão (ver [docker-compose.yml](../docker-compose.yml)).
Para subir só quando precisar mexer no banco pela UI:

```bash
docker compose --profile tools up -d phpmyadmin
```

## 3. Cachear config/rotas/views (importante no Celeron)

Depois de cada deploy/atualização de código:

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Evita reparsear config e rotas a cada request.

## 4. Acesso remoto sem abrir porta no roteador

Não expor a máquina diretamente à internet (hardware frágil, sem folga de recursos
para lidar com tráfego malicioso/scanners). Duas opções, ambas via App Store do Umbrel:

- **Tailscale** — cria uma rede privada; acessa o app como se estivesse na rede local.
- **Cloudflare Tunnel** — se precisar de um domínio público de verdade.

## 5. Swap de segurança

Com 4GB de RAM total e MySQL + Redis + PHP-FPM + Nginx rodando, um swapfile evita que o
kernel mate containers em picos de memória (deploys, migrations grandes, etc.):

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
# persistir após reboot:
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## 6. Acompanhar consumo de memória

```bash
docker stats
```

Os limites (`mem_limit`) já configurados no compose (app 400m, db 350m, redis/nginx 64m)
evitam que um serviço tome memória dos outros, mas vale monitorar em produção e ajustar
conforme o uso real (ex.: `innodb-buffer-pool-size` do MySQL em [docker-compose.yml](../docker-compose.yml)).
