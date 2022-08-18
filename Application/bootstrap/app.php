<?PHP

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Cache\CacheManager;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Env;
use Illuminate\Session\SessionManager;

defined("FILE_ROOT") ? true : define("FILE_ROOT", realpath("./"));

// create dummy app
$app = new Container();

$app_config = new Repository();

// would want to refactor to make sure cache is used in production
try {
    // check if file exists. if not, presume production enviroment
    $dotenv = Dotenv::createImmutable(FILE_ROOT);
    $dotenv->load();
    $dotenv
        ->required([
            'DB_DATABASE',
            'DB_PATH',
            'DB_DSN',
            'URL_SIGNATURE_KEY'
        ])
        ->notEmpty();
    error_log("used .env with config file");
} catch (Exception $e) {
    error_log(print_r($e->getMessage(), true));
} finally {
    $app_config->set(require(FILE_ROOT . "/Application/config/app.php"));
    $app_config->set("session", require(FILE_ROOT . "/Application/config/session.php"));

    if (empty($app_config)) {
        throw new Exception('Configuration is missing!');
    }
}

// bind $config to $app
$app->instance(
    'config',
    $app_config
);


$sessionManager = new SessionManager($app);
// d($sessionManager->driver());
// d($sessionManager->getSessionConfig());
// d($sessionManager);

//$app["session.store"] = $sessionManager->driver();
$app['session'] = $sessionManager;

d($app->get('session')->getName());
exit;

Facade::setFacadeApplication($app);
