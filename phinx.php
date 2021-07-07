<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'sqlite',
            'name' => './data/phinx-pro',
            'suffix' => '.db',
        ],
        'development' => [
            'adapter' => 'sqlite',
            'name' => './data/phinx-dev',
            'suffix' => '.db',
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'name' => './data/phinx-test',
            'suffix' => '.db',
        ]
    ],
    'version_order' => 'creation'
];
