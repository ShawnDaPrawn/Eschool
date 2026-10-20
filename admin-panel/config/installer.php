<?php

return [
    'icon' => '/assets/logo.svg',

    //    'background' => 'assets/logo.svg',

    'support_url' => 'mailto:info@tokensoft.tech',

    'server' => [
        'php' => [
            'name' => 'PHP Version',
            'version' => '>= 8.3.0',
            'check' => [
                'type' => 'php',
                'value' => 80300
            ]
        ],
        'pdo' => [
            'name' => 'PDO',
            'check' => [
                'type' => 'extension',
                'value' => 'pdo_mysql'
            ]
        ],
        'mbstring' => [
            'name' => 'Mbstring extension',
            'check' => [
                'type' => 'extension',
                'value' => 'mbstring'
            ]
        ],
        'fileinfo' => [
            'name' => 'Fileinfo extension',
            'check' => [
                'type' => 'extension',
                'value' => 'fileinfo'
            ]
        ],
        'openssl' => [
            'name' => 'OpenSSL extension',
            'check' => [
                'type' => 'extension',
                'value' => 'openssl'
            ]
        ],
        'tokenizer' => [
            'name' => 'Tokenizer extension',
            'check' => [
                'type' => 'extension',
                'value' => 'tokenizer'
            ]
        ],
        'json' => [
            'name' => 'Json extension',
            'check' => [
                'type' => 'extension',
                'value' => 'json'
            ]
        ],
        'curl' => [
            'name' => 'Curl extension',
            'check' => [
                'type' => 'extension',
                'value' => 'curl'
            ]
        ]
    ],

    'folders' => [
        'storage.framework' => [
            'name' => '/storage/framework',
            'check' => [
                'type' => 'directory',
                'value' => '../storage/framework'
            ]
        ],
        'storage.logs' => [
            'name' => '/storage/logs',
            'check' => [
                'type' => 'directory',
                'value' => '../storage/logs'
            ],
        ],
        'storage.cache' => [
            'name' => '/bootstrap/cache',
            'check' => [
                'type' => 'directory',
                'value' => '../bootstrap/cache'
            ]
        ],
    ],

    'database' => [
        'seeders' => false
    ],

    'commands' => [
        'db:seed --class=InstallationSeeder --force',
        'db:seed --class=AddSuperAdminSeeder --force',
        'db:seed --class=emailseeder --force',
        // Give every install its own APP_KEY instead of the one in .env.example.
        'key:generate --force',
    ],

    'admin_area' => [
        'user' => [
            'email' => 'superadmin@gmail.com',
            'password' => 'superadmin'
        ]
    ],

    'login' => '/login'
];
