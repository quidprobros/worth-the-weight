<?PHP

return [
    "app" => [
        'file_root' => __DIR__ . "/../",
        'site_name' => "Worth the Weight",
        'url_sign_key' => env("URL_SIGNATURE_KEY"),
        'cnx' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . "/../" . env("DB_DATABASE"),
            'dsn' => 'sqlite:' . __DIR__ . "/../" . env("DB_DATABASE"),
        ],
        'run_mode' => env('RUN_MODE') ?? "live",
        'min_password_length' => 8,
        'max_data_request_range' => 366,
    ],
];
