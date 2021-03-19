<?PHP
namespace App;

const DB_DRIVER = "sqlite";
const DB_DATABASE = FILE_ROOT . "/db/wtw.db";
const URL_SIG_KEY = "237d2e25c28596336d7edff0340f382c07427bb4f74c180252602d713bb907c";

class Config
{
    public static function init() {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Powered-By: Me");
        header("X-Content-Type-Options: NOSNIFF");
    }
}
