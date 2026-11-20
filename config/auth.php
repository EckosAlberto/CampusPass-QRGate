<?php

use App\Models\EventosUsuario;
use App\Models\GraduacionUsuario;
use App\Models\User;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'eventos' => [
            'driver' => 'session',
            'provider' => 'eventos_usuarios',
        ],

        'graduacion' => [
            'driver' => 'session',
            'provider' => 'graduacion_usuarios',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'eventos_usuarios' => [
            'driver' => 'eloquent',
            'model' => EventosUsuario::class,
        ],

        'graduacion_usuarios' => [
            'driver' => 'eloquent',
            'model' => GraduacionUsuario::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
