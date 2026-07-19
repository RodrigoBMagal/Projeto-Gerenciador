<?php

namespace App\Providers;

use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\TarefaRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Vincula as interfaces de repositorio as suas implementacoes Eloquent.
     * Isso mantem os Services e Controllers independentes de detalhes do ORM.
     */
    public function register(): void
    {
        $this->app->bind(TarefaRepositoryInterface::class, TarefaRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
