<?PHP

return [
    "app" => [
        'file_root' => realpath(".."),
        'view_root' => realpath("../views"),
        'web_root' => realpath("../wtw.paxperscientiam.com"),
        'site_name' => "Worth the Weight",
        'url_sign_key' => env("URL_SIGNATURE_KEY"),
        'cnx' => [
            'driver' => 'sqlite',
            'database' => env("DB_PATH"),
            'dsn' => env("DB_DSN")
        ],
        'run_mode' => env('RUN_MODE') ?? "live",
        'min_password_length' => 8,
        'max_data_request_range' => 366,
    ],
];
