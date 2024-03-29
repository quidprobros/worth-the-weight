<?PHP
use App\DB_DSN;
use Illuminate\Database\Capsule\Manager as Capsule;

const FILE_ROOT = __DIR__ . "/..";

require_once FILE_ROOT . "/vendor/autoload.php";

App\Config::init();

$connection = \Delight\Db\PdoDatabase::fromDsn(new \Delight\Db\PdoDsn(\App\DB_DSN));

$capsule = new Capsule();

$capsule->addConnection([
    "driver" => App\DB_DRIVER,
    "database" => App\DB_DATABASE,
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    $tables = $capsule::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
    foreach ($tables as $table) {
        if ("food_records" == $table->name) {
            continue;
        }
        Capsule::table($table->name)->truncate();
        ~d("$table->name truncated");
    }
} catch (Exception $e) {
    ~d($e->getMessage());
}
~d('successfully truncated all tables!');
