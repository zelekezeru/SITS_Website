<?php

/*
|------------------------------------------------------------------------------
| Inertia
|------------------------------------------------------------------------------
| Published purely to correct the page directory's case.
|
| inertia-laravel defaults `pages.paths` to resource_path('js/pages') — the
| lowercase name used by the Laravel 12 starter kits. This project has always
| used `resources/js/Pages`, which resolves fine on a case-insensitive
| filesystem (Windows, macOS) and not at all on Linux. The symptom is
| `assertInertia(...)->component('X')` failing with "page component file does
| not exist" for every component, on CI or on any Linux machine.
|
| Only the `pages` block is overridden; `ssr` and `testing` continue to come
| from the package defaults via mergeConfigFrom.
*/

return [

    'pages' => [

        // Runtime page-existence checking stays off, as per the package default.
        'ensure_pages_exist' => false,

        'paths' => [

            resource_path('js/Pages'),

        ],

        'extensions' => [

            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',

        ],

    ],

];
