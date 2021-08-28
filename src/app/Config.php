<?PHP

namespace App;

use Illuminate\Filesystem\Filesystem;
use Exception;

const DB_DRIVER = "sqlite";
const DB_DATABASE = FILE_ROOT . "/data/phinx-dev.db"; //"/db/wtw.db";

const MIN_PASSWORD_LENGTH = 8;
const MAX_DATA_REQUEST_RANGE = 366; // days

const DB_DSN = DB_DRIVER . ':' . DB_DATABASE;

Secrets::init();

class Config
{
    public static function init()
    {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Powered-By: Me");
        header("X-Content-Type-Options: NOSNIFF");
    }
}
