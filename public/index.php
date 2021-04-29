<?PHP

date_default_timezone_set('US/Eastern');
use Tracy\Debugger;
use Carbon\Carbon;
use Aura\Payload\PayloadFactory;
use App\Payload;
use Aura\Payload_Interface\PayloadStatus;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\UrlSigner\MD5UrlSigner;
use App\Models\User;
use App\Models\ActiveUser;

const WEB_ROOT = __DIR__;
const FILE_ROOT = __DIR__ . "/..";
define("DEBUG", "development" === $_SERVER['APPLICATION_ENV']);

require_once FILE_ROOT . "/vendor/autoload.php";

App\Config::init();

if (!file_exists(FILE_ROOT . '/tracy')) {
    mkdir(FILE_ROOT . '/tracy', 0755, true);
}

session_start();
Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::$strictMode = true;
Debugger::getBar()->addPanel(new App\TracyExtension());

if (true != DEBUG || "false" == Flight::request()->query['debug']) {
    Debugger::enable(Debugger::PRODUCTION, FILE_ROOT . '/tracy');
} else {
    Debugger::enable(Debugger::DETECT, FILE_ROOT . '/tracy');
}

Flight::set('flight.log_errors', true);
Flight::set('flight.views.path', '../views');
Flight::set('flight.views.extension', ".phtml");

$connection = \Delight\Db\PdoDatabase::fromDsn(new \Delight\Db\PdoDsn(\App\DB_DSN));

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
    [(new MD5UrlSigner(App\URL_SIG_KEY))],
);

$capsule = new Capsule();

$capsule->addConnection([
    "driver" => App\DB_DRIVER,
    "database" => App\DB_DATABASE,
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

if (DEBUG) {
    Capsule::enableQueryLog();
}

try {
    Flight::set("ActiveUser", \App\Models\ActiveUser::init());
} catch (ModelNotFoundException $e) {
    Flight::set("ActiveUser", null);
} catch (Exception $e) {
    echo 'Unknown error';
    Flight::stop();
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

Flight::map("date2offset", function (DateTime $date) {
    //
});

Flight::map("verifySignature", function () {
    if (
        empty(Flight::request()->query->signature) ||
        true != Flight::url()->validate(Flight::request()->url)
    ) {
        Debugger::log("Signature invalid");
        return false;
    }
    return true;
});

Flight::map("hxheader", function ($message, $status = "success") {
    $x = "{\"showMessage\":{\"level\" : \"{$status}\", \"message\" : \"{$message}\"}}";
    header('HX-Trigger: ' . $x);
});

Flight::route("GET /login", function () {
    /* This route MUST come FIRST */
    Flight::render("login", []);
});

Flight::route("POST /login", function () {
    /* This route MUST come SECOND */

    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->loginUser();
        Flight::hxheader('Logging in ...');
        header("HX-Redirect: /home");
        //
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Unrecognized email address. Have you registered yet?', 'error');
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Wrong password', 'error');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Too many requests', 'error');
    } catch (\App\Exceptions\FormException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader($e->getMessage(), "error");
    } catch (\Error $er) {
        Debugger::log($er->getMessage());
        Flight::hxheader($er->getMessage(), "error");
    }
});

Flight::route("POST /register", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->registerUser();
        Flight::hxheader("Registered successfully");
    } catch (\App\Exceptions\FormException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Invalid email address", "error");
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Invalid password", "error");
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("User already registered", "error");
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("You have done that too many times. Try again later", "error");
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Unknown error. Contact Chris.", "error");
    } catch (\Error $er) {
        Debugger::log($er->getMessage());
        Flight::hxheader($er->getMessage(), "error");
    }
});

Flight::route("POST /logout", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->logoutUser();
        Flight::hxheader("Logging out ...");
        header("HX-Redirect: /login");
    } catch (\Delight\Auth\NotLoggedInException $e) {
        Flight::hxheader("Not logged in", "info");
    } catch (Exception $e) {
        Flight::hxheader("There was an error logging out. Oops!", "error");
    }
});

Flight::route('*', function ($route) {
    /* This route MUST come SECOND */
    if (false == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/login", 302);
        return false;
    }
    return true;
}, true);

Flight::route('GET *', function ($route) {
    $data = Flight::request()->query;

    // big picture offset
    if (
        true != isset($data['bpo']) ||
        true != is_numeric($data['bpo'])
    ) {
        $data['bpo'] = 0;
    }

    // offcanvas menu offset
    if (
        true != isset($data['omo']) ||
        true != is_numeric($data['omo'])
    ) {
        $data['omo'] = 0;
    }

    // offcanvas graph offset
    if (
        true != isset($data['ogo']) ||
        true != is_numeric($data['ogo'])
    ) {
        $data['ogo'] = 0;
    }

    Flight::set("bpo", $data['bpo']); // big-picture
    Flight::set("omo", $data['omo']); // journal
    Flight::set("ogo", $data['ogo']);
    return true;
}, true);

Flight::route('GET /(home|index)', function () {
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
        Debugger::log($e->getMessage());
        return Flight::json([]);
    }
});

Flight::route('GET /modals/go-to-date-modal/@date', function ($date) {
    try {
        $controller = new App\Controllers\GotoDateModalController(Flight::request(), $date);
        $controller();
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
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
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
    }
});

Flight::route('GET /home/right-canvas', function () {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }
    Flight::render("partials/offcanvas-graphs", [
    ]);
});

Flight::route('GET /home/left-canvas/rel/@offset', function ($offset) {
    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $offset,
            Flight::get('bpo')
        );
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
    }
});

Flight::route('GET /home/big-picture/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            Flight::get('omo'),
            $offset
        );
        $controller->useOtherRoute("partials/big-picture");
        $controller();
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
    }
});

Flight::route('DELETE /journal-entry/@id', function ($id) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\JournalEntryRemoveController($id);
        Debugger::log($controller->journal_entry_id);
        $controller->deleteEntry($id);
        Flight::hxheader("Deleted.");
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Something went wrong. Contact Chris!!", "error");
        Flight::halt(204);
    }
});

Flight::route('POST /drop-food-log', function () {
    if (false === DEBUG) {
        return;
    }
    try {
        Flight::journalItem()::truncate();
        Flight::hxheader("Food log emptied");
        Flight::stop();
    } catch (\Exection $e) {
        Tracy\Debugger::log($e->getMessage());
        Flight::hxheader("Unable to dump food log table. Contact Chris!");
        Flight::stop();
    }
});

Flight::route('POST /exercised/rel/@offset', function ($offset) {
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
        Debugger::log($e->getMessage());
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
        Flight::hxheader($e->getMessage(), "error");
        return;
    }

    try {
        $controller->saveEntry();
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Sorry, your progress was not recorded. Ask chris for help.", "error");
        return;
    }

    Flight::hxheader("Success!");
});

Flight::route("GET /bootstrap", function () {
    Flight::render("bootstrap", []);
});

if (true === DEBUG) {
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
    if (DEBUG) {
        $bs = new Tracy\BlueScreen();
        $bs->render($ex);
    } else {
        Flight::halt("404", "BRB");
    }
    Debugger::log($ex);
    exit;
});

Flight::start();
