<?PHP
date_default_timezone_set('US/Eastern');
use Tracy\Debugger;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\UrlSigner\MD5UrlSigner;
use App\Models\ActiveUser;
//
use function League\Uri\parse;
use function League\Uri\build;

const WEB_ROOT = __DIR__;
const FILE_ROOT = __DIR__ . "/..";
define("DEBUG", "development" === $_SERVER['APPLICATION_ENV']);

require_once FILE_ROOT . "/vendor/autoload.php";

// sets headers and stuff
App\Config::init();

if (!file_exists(FILE_ROOT . '/tracy')) {
    mkdir(FILE_ROOT . '/tracy', 0755, true);
}

session_start();

Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::$strictMode = true;
Debugger::$showLocation = true;
Debugger::getBar()->addPanel(new App\TracyExtension());

Flight::set("debug_mode", true === DEBUG && "true" === Flight::request()->query['debug']);

if (true == Flight::get("debug_mode")) {
    Debugger::enable(Debugger::DETECT, FILE_ROOT . '/tracy');
} else {
    Debugger::enable(Debugger::PRODUCTION, FILE_ROOT . '/tracy');
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
    'mail',
    'Mailgun\Mailgun::create',
    [App\MAILGUN_API_KEY]
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
        Debugger::log("Signature invalid");
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
        Debugger::log($message);
    } else {
        Debugger::log($exception->getMessage());
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

Flight::route("/*[^login]", function () {
    if (false == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/login", 302);
    }
    // carry on
    return true;
}, true);


Flight::route("GET /login", function () {
    if (true == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/home", 302);
    }
    Flight::render("login", []);
});

Flight::route("POST /login", function () {
    if (true == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/home", 302);
    }

    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());
        $controller->loginUser();
        header("HX-Redirect: /home");
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Unrecognized email address. Have you registered yet?', 'error');
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Wrong password', 'error');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Email not verified', 'error');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader('Too many requests', 'error');
    } catch (\App\Exceptions\FormException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Unable to login at this time. Please contact Chris.", "error");
    } catch (\Error $er) {
        Debugger::log($er->getMessage());
        Flight::hxheader("Unable to login at this time. PLease contact Chris.", "error");
    }

    // for iframe
    echo 'success';
});

Flight::route("POST /register", function () {
    if (true == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/home", 302);
    }

    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());
        $controller->registerUser();
        Flight::hxtrigger([
            "action" => [
                "xpath" => "resetForms",
            ],
            "showMessage" => [
                "message" => "Success! You may now login",
                "level" => "success"
            ]
        ]);
        Flight::redirect("/home");
    } catch (\App\Exceptions\FormException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Invalid email address", "error");
    } catch (\Delight\Auth\DuplicateUsernameException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("That username is already taken", "error");
        echo "That username is already taken";
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Invalid password", "error");
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("User already registered", "error");
        echo "User already registered";
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

Flight::route("*", function () {
    if (false == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/login", 302);
    }

    try {
        Flight::set("ActiveUser", ActiveUser::init());
        bdump(Flight::get("ActiveUser")->id);
    } catch (ModelNotFoundException $e) {
        Flight::set("ActiveUser", null);
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
        Flight::stop();
    }
    return true;
});

Flight::route('GET *', function () {

    $data = Flight::request()->query;

    // debug
    if (true == isset($data['debug'])) {
        $data['debug'] = true;
    }

    Flight::set("debug_mode", $data['debug']);

    return true;
}, true);

Flight::route("GET|POST /logout", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());
        $controller->logoutUser();
        header("HX-Redirect: /login");
    } catch (\Delight\Auth\NotLoggedInException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Not logged in", "info");
    } catch (Exception $e) {
        Flight::hxheader("There was an error logging out. Oops!", "error");
    }
});

Flight::route('GET /(home|index)', function () {
    Flight::redirect(Flight::url()->sign("/home/0/0"));
});

Flight::route('GET /home/(@omo(/@bpo))', function ($omo, $bpo) {
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

// Flight::route('GET /home/rel/@index', function ($index) {
//  Debugger::log('rel me');
//     if (!Flight::verifySignature()) {
//         Flight::notFound();
//     }

//     $query = Flight::request()->query;
//     $query->bpo = $index;

//     $new_query = http_build_query($query->getData(), "?", "&", PHP_QUERY_RFC3986);

//     $components = parse(Flight::request()->url);
//     $components['path'] = "/home";
//     $components['query'] = $new_query;

//     $url = build($components);

//     Flight::redirect($url);
//     return false;
// });


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
        $controller->useOtherRoute("partials/journal");
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

Flight::route('GET /home/left-canvas/rel/@omo/@bpo', function ($omo, $bpo) {
    Debugger::log("/home/left-canvas/rel/@offset routed with {$omo}");
    try {
        $controller = new App\Controllers\HomeController(
            Flight::request(),
            $omo,
            $bpo
        );
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
    }
});

Flight::route('GET /home/big-picture/rel/@omo/@bpo', function ($omo, $bpo)  {
    Debugger::log("/home/big-picture/rel/@offset routed with {$bpo}");
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
        Debugger::log($e->getMessage());
    }
});

Flight::route('DELETE /journal-entry/@id', function ($id) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }

    try {
        $controller = new App\Controllers\JournalEntryRemoveController($id);
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
        Debugger::log($e->getMessage());
        Flight::hxheader("Unable to dump food log table. Contact Chris!");
        Flight::stop();
    }
});

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
        echo '<div>Something went wrong!:(</div>';
        Flight::hxheader($e->getMessage(), "error");
        return;
    }

    try {
        $controller->saveEntry();
        echo "<div>Successly journaled {$controller->amount} units of this food (+{$controller->points} points)!</div>";
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
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
    Flight::route('GET /bootstrapper', function () {
        Flight::render("bootstrapper", []);
    });
}

Flight::map('notFound', function () {
    $message = "<p>That thing you were looking for ... it's not here. Click <a href='/'>here</a> to head home.</p>";
    Flight::halt('404', $message);
});

Flight::map('error', function ($ex) {
    Debugger::log($ex->getMessage());
    if (true == Flight::get("debug_mode")) {
        $bs = new Tracy\BlueScreen();
        $bs->render($ex);
    } else {
        throw $ex;
    }
});

Flight::start();
