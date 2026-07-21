<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;

return [
    /*
     * Canal usado quando nada especifico e informado. Em desenvolvimento
     * normalmente e texto legivel ("single"); em producao, recomenda-se
     * trocar LOG_CHANNEL=structured no .env para logs em JSON, prontos
     * para um coletor (ELK, Grafana Loki, CloudWatch, Datadog etc.).
     */
    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        /*
         * Canal de logs estruturados em JSON: cada linha do arquivo e um
         * objeto JSON (timestamp, level, message, context), incluindo um
         * id unico por requisicao (ver App\Http\Middleware\LogApiRequests).
         * Ideal para producao e para ser lido por um agente de log.
         */
        'structured' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => env('LOG_STRUCTURED_PATH', storage_path('logs/structured.json')),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [
                UidProcessor::class,
                PsrLogMessageProcessor::class,
            ],
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => ['stream' => 'php://stderr'],
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ],
    ],
];
