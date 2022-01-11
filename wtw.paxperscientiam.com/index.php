<?PHP
error_reporting(error_reporting() & ~E_DEPRECATED);

date_default_timezone_set('US/Eastern');

use Tracy\Debugger;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\UrlSigner\MD5UrlSigner;
use App\Models\ActiveUser;
use Delight\Base64\Throwable\Exception;
use Illuminate\Support\Facades\Config;

use function League\Uri\parse;
// use function League\Uri\build;

const FILE_ROOT = __DIR__ . "/../";

require_once FILE_ROOT . "/vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main-channel');
// test if for Docker (if docker, do "php://stderr")
$log->pushHandler(new StreamHandler(Config::get('app.log_file'), Logger::DEBUG));

Flight::map("log", function () use ($log) {
    return $log;
});

session_start();

Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::$strictMode = true;
Debugger::$showLocation = true;
//Debugger::setLogger(new App\TracyStreamLogger());
Debugger::getBar()->addPanel(new App\TracyExtension());


Flight::set("debug_mode", "DEBUG" == Config::get("app.run_mode"));
Flight::set("domain", Config::get("domain"));

if (true == Flight::get("debug_mode")) {
    Debugger::enable(Debugger::DEVELOPMENT, Config::get('app.tracy_log'));
} else {
    Debugger::enable(Debugger::PRODUCTION, Config::get('app.tracy_log'));
}

Flight::set('flight.log_errors', true);
Flight::set('flight.views.path', Config::get("app.view_root"));
Flight::set('flight.views.extension', ".phtml");

$connection = \Delight\Db\PdoDatabase::fromDsn(new \Delight\Db\PdoDsn(Config::get('app.cnx.dsn')));

Flight::register(
    'auth',
    'Delight\Auth\Auth',
    [$connection, null, null, false]
);

Flight::register(
    'validate',
    'App\Validate'
);

Flight::register(
    'url',
    'App\SignUrl',
    [(new MD5UrlSigner(Config::get("app.url_sign_key")))]
);

Flight::before('route', function () {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Powered-By: Me");
    header("X-Content-Type-Options: NOSNIFF");
});

$capsule = new Capsule();

$capsule->addConnection([
    "driver" => Config::get('app.cnx.driver'),
    "database" => Config::get('app.cnx.database'),
]);


$capsule->setAsGlobal();
$capsule->bootEloquent();

if (true == Flight::get("debug_mode")) {
    Capsule::enableQueryLog();
}

Flight::register(
    'stats',
    'App\Stats'
);

Flight::register(
    'payload',
    'Aura\Payload\Payload'
);

Flight::register(
    'journalItem',
    'App\Models\JournalItem'
);

Flight::register(
    'food',
    'App\Models\Food'
);

Flight::map("offset2date", function ($offset) {
    return Carbon::now()->addDays($offset);
});

Flight::map("verifySignature", function () {
    if (
        empty(Flight::request()->query->signature) ||
        true != Flight::url()->validate(Flight::request()->url)
    ) {
        Flight::log("Signature invalid");
        return false;
    }
    return true;
});

Flight::map("hxheader", function ($message, $status = "success", $exception = null) {
    $x = json_encode([
        "showMessage" => [
            "level" => $status,
            "message" => $message,
        ],
    ]);

    header('HX-Trigger: ' . $x);

    if (empty($exception)) {
        Flight::log($message);
    } else {
        Flight::log($exception->getMessage());
    }
});

Flight::map("hxtrigger", function ($actions) {
    $z = json_encode($actions);
    header('HX-Trigger: ' . $z);
});

// end routing after redirect
Flight::after("redirect", function () {
    exit;
});

/*
 * routes begin!
 */
Flight::route("GET /info", function () {
    if (true == Flight::get("debug_mode")) {
        phpinfo();
        //xdebug_info();
    } else {
        Flight::redirect("/home", 302);
    }
});

Flight::route("GET /login", function () {
    if (true == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/home", 302);
    }
    Flight::render("login");
});

Flight::route("POST /login", function () {
    if (true == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/home", 302);
    }

    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->loginUser();
        header("HX-Redirect: /home");
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader('Unrecognized email address. Have you registered yet?', 'error');
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader('Wrong password', 'error');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader('Email not verified', 'error');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader('Too many requests', 'error');
    } catch (\App\Exceptions\FormException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Exception $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("Unable to login at this time. Please contact Chris.", "error");
    } catch (\Error $er) {
        Flight::log($er->getMessage());
        Flight::hxheader("Unable to login at this time. PLease contact Chris.", "error");
    }

    // for iframe
    echo 'success';
});

Flight::route("POST /register", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->registerUser(true); // argument: true means login immediately after successful registration
        Flight::hxtrigger([
            "action" => [
                "xpath" => "resetForms",
            ],
            "showMessage" => [
                "message" => "Success! Logging you in ...",
                "level" => "success"
            ]
        ]);
        header("HX-Redirect: /home");
    } catch (\App\Exceptions\FormException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("Invalid email address", "error");
    } catch (\Delight\Auth\DuplicateUsernameException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("That username is already taken", "error");
        echo "That username is already taken";
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("Invalid password", "error");
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("User already registered", "error");
        echo "User already registered";
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("You have done that too many times. Try again later", "error");
    } catch (\Exception $e) {
        Flight::log($e->getMessage());
        Flight::log(env("DB_PATH"));
        Flight::hxheader("Unknown error. Contact Chris.", "error");
    } catch (\Error $er) {
        Flight::log($er->getMessage());
        Flight::hxheader($er->getMessage(), "error");
    }
});

Flight::route("GET /reset-pw", function() {
    Flight::render("reset-pw");
});

Flight::route("POST /reset-password", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->resetPassword();
        $controller->useOtherRoute("partials/pw-reset");
        $controller();
        //        sleep(1);
        //header("HX-Redirect: /");
    } catch (Delight\Auth\InvalidEmailException $e) {
        Flight::hxheader('Unknown email address. Are you registered?', 'error');
    } catch (Exception $e) {
        Flight::log("exception: " . $e->getMessage());
    }
});

Flight::route("GET /verify_email", function () {

    $data = Flight::request()->query->getData();

    if (true !== (isset($data['selector']) && isset($data['token']))) {
        Debugger::log("Someone attempted to verify with invalid credentials");
        Flight::redirect("/home", 302);
    }

    Flight::render("login", [
        "selector" => $data['selector'],
        "token" => $data['token'],
        "show" => false,
        "pw_reset" => true,
    ]);
    exit;
});

Flight::route("POST /verify_email", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->setNewPassword();
        $controller->useOtherRoute();
    } catch (\App\Exceptions\FormException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (Exception $e) {
        Flight::log($e->getMessage());
    }
});

Flight::route("*", function () {
    if (false == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/login", 302);
    }

    try {
        Flight::set("ActiveUser", ActiveUser::init());
    } catch (ModelNotFoundException $e) {
        Flight::log($e->getMessage());
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->logoutUser();
        Flight::redirect("/login");
    } catch (Exception $e) {
        Flight::log($e->getMessage());
        Flight::stop();
    }
    return true;
});

Flight::route('GET *', function () {

    // $data = Flight::request()->query;

    // // debug
    // if (true == isset($data['debug'])) {
    //     $data['debug'] = true;
    // }

    // Flight::set("debug_mode", $data['debug']);

    // Flight::request()->query->__unset("debug");

    // $components = parse(Flight::request()->url);
    // $components['query'] = http_build_query(Flight::request()->query->getData());

    // // Flight::request()->url = build($components);

    return true;
}, true);

Flight::route("GET|POST /logout", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->logoutUser();
        header("HX-Redirect: /login");
    } catch (\Delight\Auth\NotLoggedInException $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("Not logged in", "info");
    } catch (Exception $e) {
        Flight::hxheader("There was an error logging out. Oops!", "error");
    }
});

Flight::route('GET /(home|index)', function () {
    Flight::redirect(Flight::url()->sign("/home/0/0"));
});

Flight::route('GET /home/(@omo:-?[0-9]+(/@bpo:-?[0-9]+))', function ($omo, $bpo) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    // big picture offset
    if (
        true != is_numeric($bpo)
    ) {
        $bpo = 0;
    }

    // offcanvas menu offset
    if (
        true != is_numeric($omo)
    ) {
        $omo = 0;
    }

    Flight::set("omo", $omo); // journal
    Flight::set("bpo", $bpo); // big-picture

    $controller = new App\Controllers\HomeController(
        Flight::request(),
        Flight::get('omo'),
        Flight::get('bpo')
    );
    $controller();
});

Flight::route('GET /goto/@date', function ($date) {
    (new App\Controllers\RedirectDateController($date))();
});

Flight::route('GET /beef/@min/@max', function ($min, $max) {
    try {
        $controller = new App\Controllers\BeefController(
            Flight::request(),
            $min,
            $max
        );
        return Flight::json($controller->getPayload());
    } catch (Exception $e) {
        Flight::log($e->getMessage());
        return Flight::json([]);
    }
});

Flight::route('GET /modals/go-to-date-modal/@date', function ($date) {
    try {
        $controller = new App\Controllers\GotoDateModalController(Flight::request(), $date);
        $controller();
    } catch (Exception $e) {
        Flight::log($e->getMessage());
        Flight::halt(404);
    }
});

Flight::route('GET /journal/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }
    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $offset,
            Flight::get("bpo")
        );
        $controller->useOtherRoute("partials/journal");
        $controller();
    } catch (Exception $e) {
        Flight::log($e->getMessage());
    }
});

Flight::route('GET /home/right-canvas/rel', function () {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    Flight::render("partials/offcanvas-graphs", []);
});

Flight::route("GET /home/title-bar/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+", function ($omo, $bpo) {
    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $omo,
            $bpo
        );
        $controller->useOtherRoute("partials/title-bar");
        $controller();
    } catch (Exception $e) {
        Flight::log($e->getMessage());
    }
});

Flight::route('GET /home/left-canvas/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+', function ($omo, $bpo) {

    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $omo,
            $bpo
        );
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        Flight::log($e->getMessage());
    }
});

Flight::route('GET /home/big-picture/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+', function ($omo, $bpo) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $omo,
            $bpo
        );
        $controller->useOtherRoute("partials/big-picture");
        $controller();
    } catch (Exception $e) {
        Flight::log($e->getMessage());
    }
});

Flight::route('DELETE /journal-entry/@id', function ($id) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\JournalEntryRemoveController();
        $controller->deleteEntry($id);
        Flight::hxheader("Deleted.");
    } catch (\Exception $e) {
        Flight::log($e->getMessage());
        Flight::hxheader("Something went wrong. Contact Chris!!", "error");
        Flight::halt(204);
    }
});

if (true == Flight::get("debug_mode")) {
    Flight::route('POST /drop-food-log', function () {
        try {
            $controller = new App\Controllers\JournalEntryRemoveController();
            $controller->deleteAll();
            Flight::stop();
        } catch (Exception $e) {
            Flight::log()->info($e->getMessage());
            Flight::hxheader("Unable to dump food log table. Contact Chris!");
            Flight::stop();
        }
    });
}

Flight::route('POST /journal-entry/exercised/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\ExerciseController(
            Flight::request(),
            $offset
        );
        $controller->setRoute("partials/exercised-statement");
        $controller->saveUpdate();

        $controller();
    } catch (Exception $e) {
        Flight::log($e->getMessage());
    }
});

Flight::map("welcome", function () {
    if (6 <= date("H") && 12 > date("H")) {
        return "good morning";
    } elseif (12 <= date("H") && 18 > date("H")) {
        return "good afternoon";
    } else {
        return "good evening";
    }
});

Flight::route('GET /food-support-message', function () {
    $greetings = [
        'Yum!',
        "Eh, I've had better!",
        'Nice!',
        'Way to go!',
        'Woot woot!',
    ];
    shuffle($greetings);
    echo $greetings[0];
});

Flight::route('POST /journal-entry', function () {
    try {
        $controller = new App\Controllers\JournalEntryCreateController(Flight::request());
    } catch (\App\Exceptions\FormException $e) {
        echo '<div>Something went wrong!:(</div>';
        Flight::hxheader($e->getMessage(), "error");
        return;
    }

    try {
        $controller->saveEntry();
        echo "<div>Successly journaled {$controller->amount} units of this food (+{$controller->points} points)!</div>";
    } catch (\Exception $e) {
        Flight::log($e->getMessage());
        echo '<div>Something went wrong!:(</div>';
        Flight::hxheader("Sorry, your progress was not recorded. Ask chris for help.", "error");
        return;
    }

    Flight::hxheader("Success!");
});

if (true == Flight::get("debug_mode")) {
    Flight::route('GET /test', function () {
        $controller = new App\Controllers\TestController('test');
        $controller();
    });
}

Flight::map('notFound', function () {
    $message = "<p>That thing you were looking for ... it's not here. Click <a href='/'>here</a> to head home.</p>";
    Flight::halt('404', $message);
});

Flight::map('error', function ($ex) {
    Flight::log($ex->getMessage());
    if (true == Flight::get("debug_mode")) {
        Flight::log('bluescreen in debug mode');
        $bs = new Tracy\BlueScreen();
        $bs->render($ex);
    } else {
        Flight::log('bluescreen in production mode');
        throw $ex;
    }
});

Flight::start();
