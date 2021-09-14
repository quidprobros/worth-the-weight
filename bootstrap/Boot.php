<?PHP

/*
  Note, these variables aren't directly accessible. Use globals
  EG: Illuminate\Support\Facades\Config::get("app.cnx")
*/

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

defined("FILE_ROOT") ? true : define("FILE_ROOT", realpath(".."));

// create dummy app
$app = new Container();

// configure cache access
$cacheC = new Container();
$cache_config = new Repository(require(FILE_ROOT . "/config/cache.php"));
$cacheC['config'] = $cache_config->get('cache');
$cacheC["files"] = new Filesystem();
$cacheManager = new CacheManager($cacheC);
$cache = $cacheManager->store();

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
    $app_config->set(require(FILE_ROOT . "/config/app.php"));
    $cache->forever('app', $app_config->get('app'));
    error_log("used .env with config file");
} catch (Exception $e) {
    error_log(print_r($e->getMessage(), true));
    error_log("reading app configuration from app cache.");
    $app_config->set(['app' => $cache->get('app')]);
} finally {
    if (empty($app_config)) {
        throw new Exception('Configuration is missing!');
    }
}

// bind $config to $app
$app->instance(
    'config',
    $app_config
);

// bind $cache to $app
$app->instance(
    'cache',
    $cache
);

Facade::setFacadeApplication($app);
