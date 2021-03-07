<?PHP
namespace App;

const DB_DRIVER = "sqlite";
const DB_DATABASE = "db/wtw.db";

class Config
{
    public static function init() {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Powered-By: Me");
        header("X-Content-Type-Options: NOSNIFF");
    }


}
