<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Prefixo da rota (e do path) onde a UI da documentacao e o JSON
     * do OpenAPI ficam disponiveis. Com o valor abaixo:
     *   - UI:   /docs/api
     *   - JSON: /docs/api.json
     */
    'api_path' => 'docs/api',

    'api_domain' => null,

    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'API REST do Projeto Gerenciador (tarefas e equipe). '
            .'Documentacao gerada automaticamente a partir das rotas, Form Requests '
            .'e API Resources da aplicacao (sem anotacoes manuais).',
    ],

    /*
     * Servidores listados no topo da documentacao (util para trocar entre
     * ambiente local, staging e producao direto pela UI do Swagger).
     */
    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    'ui' => [
        'title' => 'Projeto Gerenciador — API Docs',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
    ],
];
