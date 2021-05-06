<?PHP
namespace App;

use Illuminate\Filesystem\Filesystem;
use Exception;

const DB_DRIVER = "sqlite";
const DB_DATABASE = FILE_ROOT . "/db/wtw.db";
const URL_SIG_KEY = "237d2e25c28596336d7edff0340f382c07427bb4f74c180252602d713bb907c";

const MIN_PASSWORD_LENGTH = 8;
const MAX_DATA_REQUEST_RANGE = 366; // days

const DB_DSN = DB_DRIVER . ':' . DB_DATABASE;

const MAILGUN_API_KEY = '15dfcdcf3f6864f3c609ed93e196d963-2a9a428a-0ebd6d7a';
//const MAILGUN_DOMAIN = "sandboxb6b85466eff04dfc980456f2f72be64d.mailgun.org";
const APP_DOMAIN = "paxperscientiam.com";

const MAILGUN_SANDBOX_EMAIL = 'Mailgun Sandbox <postmaster@sandboxb6b85466eff04dfc980456f2f72be64d.mailgun.org>';

const NOREPLY_EMAIL = 'noreply@paxperscientiam.com';

class Config
{
    public static function init()
    {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Powered-By: Me");
        header("X-Content-Type-Options: NOSNIFF");
    }
}
