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
use Mailgun\Mailgun;
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


Flight::map("hxheader", function ($message, $status = "success", $exception = null) {
    $x = json_encode([
        "showMessage" => [
            "level" => $status,
            "message" => $message,
        ],
        "action" => [
            "xpath" => "resetForms",
            "zpath" => "ok"
        ]
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

// Flight::route("POST /forgot", function () {
//     $data = Flight::request()->data;
//     $email = $data['reset_email'];

//     try {
//         $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());

//         Flight::hxheader('Request has been generated');
//     }
//     catch (\Delight\Auth\InvalidEmailException $e) {
//         Flight::hxheader('Invalid email address');
//     }
//     catch (\Delight\Auth\EmailNotVerifiedException $e) {
//         Flight::hxheader('Email not verified');
//     }
//     catch (\Delight\Auth\ResetDisabledException $e) {
//         Flight::hxheader('Password reset is disabled');
//     }
//     catch (\Delight\Auth\TooManyRequestsException $e) {
//         Flight::hxheader('Too many requests');
//     } catch (\App\Exceptions\FormException $e) {
//         Flight::hxheader($e->getMessage(), 'error');
//     } catch (Exception $e) {
//         Flight::hxheader("There was an error", "error", $e);
//     }
// });

Flight::route("GET /login", function () {
    /* This route MUST come FIRST */
    
    Flight::render("login", []);
});

Flight::route("POST /login", function () {
    /* This route MUST come SECOND */

    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());
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
});

Flight::route("POST /register", function () {
    try {
        $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());
        $x = $controller->registerUser();
        Flight::hxtrigger([
            "action" => [
                "xpath" => "resetForms",
            ],
            "showMessage" => [
                "message" => "Success! You may now login",
                "level" => "success"
            ]
        ]);
    } catch (\App\Exceptions\FormException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader($e->getMessage(), 'error');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("Invalid email address", "error");
    } catch (\Delight\Auth\DuplicateUsernameException $e) {
        Debugger::log($e->getMessage());
        Flight::hxheader("That username is already taken", "error");
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
        $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());
        $controller->logoutUser();
        Flight::hxheader("Logging out ...");
        header("HX-Redirect: /login");
    } catch (\Delight\Auth\NotLoggedInException $e) {
        Flight::hxheader("Not logged in", "info");
    } catch (Exception $e) {
        Flight::hxheader("There was an error logging out. Oops!", "error");
    }
});

Flight::route(' GET /verify-email', function () {
    $data = Flight::request()->query;
    $selector = $data['selector'];
    $token = $data['token'];
    Debugger::log(['let us verify', $selector, $token]);

    try {
        Flight::auth()->confirmEmail($selector, $token);
        echo 'Email address has been verified';
        $message = "success";
    } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
        Flight::hxheader('Invalid token');
        $message = "invalid";
    } catch (\Delight\Auth\TokenExpiredException $e) {
        Flight::hxheader('Token expired');
        $message = "invalid";
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        Flight::hxheader('Email address already exists');
        $message = "Email address already exists";
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        Flight::hxheader('Too many requests');
        $message = "Try again later";
    } catch (Exception $e) {
        $message = "Something went wrong.";
        Debugger::log($e->getMessage());
    }

    Flight::redirect(Flight::url()->sign("/login?message={$message}"));
});

Flight::route('*', function ($route) {
    /* This route MUST come SECOND */

    if (false == Flight::auth()->isLoggedIn()) {
        Flight::redirect("/login", 302);
        return false;
    }
    bdump('passed login test');
    try {
        Flight::set("ActiveUser", \App\Models\ActiveUser::init());
        bdump(Flight::get("ActiveUser")->id);
    } catch (ModelNotFoundException $e) {
        Flight::set("ActiveUser", null);
    } catch (Exception $e) {
        Debugger::log($e->getMessage());
        Flight::stop();
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

    // debug
    if (true == isset($data['debug'])) {
        $data['debug'] = true;
    }

    Flight::set("bpo", $data['bpo']); // big-picture
    Flight::set("omo", $data['omo']); // journal
    Flight::set("ogo", $data['ogo']);
    Flight::set("debug_mode", $data['debug']);

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

Flight::route('GET /home/next', function () {
    $dump = [];
    $query = Flight::request()->query;

    $query->bpo = $query->bpo + 13;

    $new_query = http_build_query($query->getData(), "?", "?", PHP_QUERY_RFC3986);

    $dump[] = $new_query;

    $components = parse(Flight::request()->url);
    $components['path'] = "/home";
    $components['query'] = $new_query;

    Debugger::log($components);

    $url = build($components);
    $dump[] = $url;



    Flight::redirect($url);
});

Flight::route('GET /home/prev', function () {
    $query = Flight::request()->query;
    $query['bpo'] -= 1;

    $new_query = http_build_query($query->getData(), "?", "?", PHP_QUERY_RFC3986);

    $components = parse(Flight::request()->url);
    $components['path'] = "/home";
    $components['query'] = $new_query;

    $url = build($components);

    Flight::redirect($url);
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
