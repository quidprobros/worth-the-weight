<?PHP

/*
  Note, these variables aren't directly accessible. Use globals
  EG: Illuminate\Support\Facades\Config::get("app.cnx")
 */

use Illuminate\Support\Facades\Config;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

$dotenv = Dotenv\Dotenv::createImmutable(FILE_ROOT);
$dotenv->load();

$config = new Repository(require(FILE_ROOT . "/config/app.php"));
$app = new Container();

$app->instance(
    'config',
    $config
);

Facade::setFacadeApplication($app);
