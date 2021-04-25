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
const DEBUG = true;

require_once FILE_ROOT . "/vendor/autoload.php";

$whoops = new \Whoops\Run();
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());

App\Config::init();

if (!file_exists(FILE_ROOT . '/tracy')) {
    mkdir(FILE_ROOT . '/tracy', 0755, true);
}

session_start();
Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;

Debugger::enable(Debugger::DETECT, FILE_ROOT . '/tracy');
Debugger::$strictMode = true;


if (true != DEBUG) {
    Debugger::$showBar = false;
}

Tracy\Debugger::getBar()->addPanel(new App\TracyExtension());

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

Capsule::enableQueryLog();

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

Flight::route("POST /signin", function () {
    /* This route MUST come SECOND */

    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request());
        $controller->loginUser();
        Flight::hxheader('Logging in ...', 'error');
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

Flight::route("/logout", function () {
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

    Flight::set("bpo", $data['bpo']);
    Flight::set("omo", $data['omo']);
    Flight::set("ogo", $data['ogo']);
    return true;
}, true);

Flight::route('*', function ($route) {
    /* This route MUST come SECOND */
    if (false == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/login", 302);
        return false;
    }
    return true;
}, true);

Flight::route('GET /(home|index)', function () {
    $controller = new App\Controllers\HomeController(
        Flight::request(),
        Flight::get('omo')
    );
    $controller->index();
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
    }
});

Flight::route('GET /modals/(@modal)', function ($modal) {
    if (true != preg_match('/^[a-z0-9\-]+$/i', $modal)) {
        Debugger::log("Bad modal name: {$modal}");
        Flight::halt(404);
    }

    $date = Flight::request()->query['date'];

    if (false === strtotime($date)) {
        Debugger::log("Bad date value: ${$date}");
        Flight::halt(404);
    }

    $displayDate = (new Carbon($date))->format("D M j, Y");

    $modalPath = "partials/modals/{$modal}";

    try {
        if (true != Flight::view()->exists($modalPath)) {
            throw new Exception("template not found: ${modalPath}");
        }
        Flight::render($modalPath, [
            "date" => $date,
            "displayDate" => $displayDate,
        ]);
    } catch (Exception $e) {
        dump($e);
        Flight::halt(404);
    }
});

Flight::route('GET /journal-total/@date', function ($date) {
    $sum = Flight::get("ActiveUser")->onDate($date)->sum("points");

    $payload = new Payload();

    $payload->setStatus(PayloadStatus::SUCCESS);
    $payload->setOutput([
        "has_entries" => (0 < $sum ? true : false),
        "total" => $sum
    ]);

    return Flight::json($payload->getOutput());
});

Flight::route('GET /journal/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }
    try {
        $controller = new App\Controllers\HomeController(Flight::request(), $offset);
        $controller->index();
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
        $controller = new App\Controllers\HomeController(Flight::request(), $offset);
        $controller->index();
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
    }
});

Flight::route('GET /big-picture/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $offset
        );
        $controller->index();
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

    if (false == is_numeric($id)) {
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Unknown deletion candidate"}}');
        Flight::halt(204);
    }

    try {
        $item = User::journalItem()::findOrFail($id);
        $item->delete();
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "success", "message" : "Deleted"}}');
        Flight::halt(204);
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Something went wrong. Contact Chris."}}');
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
    $offset = (int) $offset;
    $exercised = (int) Flight::request()->data['exercised'];

    $x = App\Models\JournalItem::updateOrCreate([
        "date" => Carbon::now()->addDays($offset)->format("Y-m-d"),
    ], [
        "exercised" => 1,
        "userID" => Flight::get("ActiveUser")->id,
    ]);
    return Flight::render("partials/exercised-statement", ["exercised" => $exercised]);
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
        $controller = new App\Controllers\JournalEntryController(Flight::request());
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
        $controller->index();
    });
}

Flight::map('notFound', function () {
    echo "<p>That thing you were looking for ... it's not here. Click <a href='/'>here</a> to head home.</p>";
    return;
});

Flight::map('error', function ($ex) use ($whoops) {
    if (Flight::request()->method === "GET") {
        $whoops->handleException($ex);
    }
    Debugger::log($ex);
});

Flight::start();
