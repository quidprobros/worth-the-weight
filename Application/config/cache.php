<?PHP

return [
    'cache' => [
        'cache.default' => 'file',
        'cache.stores.file' => [
            'driver' => 'file',
            'path' => realpath(FILE_ROOT . "/storage/appcache")
        ]
    ]
];
