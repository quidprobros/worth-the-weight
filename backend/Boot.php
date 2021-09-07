<?PHP

/*
  Note, these variables aren't directly accessible. Use globals
  EG: Illuminate\Support\Facades\Config::get("app.cnx")
 */

use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

defined("FILE_ROOT") ? true : define("FILE_ROOT", realpath(".."));

$dotenv = Dotenv\Dotenv::createImmutable(FILE_ROOT);
$dotenv->load();

$config = new Repository(require(FILE_ROOT . "/backend/config/app.php"));
$app = new Container();

$app->instance(
    'config',
    $config
);

Facade::setFacadeApplication($app);
