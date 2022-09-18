<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'sqlite',
            'name' => './storage/db/phinx-pro',
            'suffix' => '.db',
        ],
        'development' => [
            'adapter' => 'sqlite',
            'name' => './storage/db/phinx-dev',
            'suffix' => '.db',
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'name' => './storage/db/phinx-test',
            'suffix' => '.db',
        ]
    ],
    'version_order' => 'creation'
];
