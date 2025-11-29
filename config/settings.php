<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Repository
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the repositories registered in the `repositories`
    | array you wish to use as your default repository for storing settings.
    |
    */
    'repository' => 'eloquent',

    /*
    |--------------------------------------------------------------------------
    | List of Repositories
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the repositories that will be used to store
    | settings. You can add your own custom repositories as needed.
    |
    */
    'repositories' => [
        'eloquent' => [
            'class' => \Coyotito\LaravelSettings\Repositories\EloquentRepository::class,

            'model' => \Coyotito\LaravelSettings\Models\Setting::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registered Settings Classes
    |--------------------------------------------------------------------------
    |
    | Here you may list all of the settings classes that should be registered
    | by the package. These classes will be accessible via the Laravel Container,
    | and can be resolved using the Laravel dependency injection.
    |
    */
    'classes' => [
        //
    ],
];
