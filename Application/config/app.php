<?PHP

return [
    "app" => [
        'file_root' => realpath(".."),
        'view_root' => realpath("../Application/views"),
        'log_file' => realpath("../logs") . "/main.log",
        'tracy_log' => realpath("../logs/tracy"),
        'web_root' => realpath("../wtw.paxperscientiam.com"),
        'site_name' => "Worth the Weight",
        'url_sign_key' => env("URL_SIGNATURE_KEY", '9oyhbasdf97y8h89oo7mnhsaf9987pyhkjHKJH978nkjdsaf'),
        'cnx' => [
            'driver' => 'sqlite',
            'database' => env("DB_PATH", "../storage/db/phinx-dev.db"),
            'dsn' => env("DB_DSN", "sqlite:../storage/db/phinx-dev.db")
        ],
        'email' => [
            'sender' => 'webmaster@paxperscientiam.com',
        ],
        'run_mode' => env('RUN_MODE', 'live'),
        'min_password_length' => 8,
        'max_data_request_range' => 366,
    ],
    "domain" => env("DOMAIN", "wtw.paxperscientiam.com")
];
