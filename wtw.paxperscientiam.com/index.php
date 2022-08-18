<?PHP

error_reporting(error_reporting() & ~E_DEPRECATED);

date_default_timezone_set('US/Eastern');

use Tracy\Debugger;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;

use Carbon\Carbon;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

use Spatie\UrlSigner\MD5UrlSigner;
use Delight\Base64\Throwable\Exception;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use App\Validations\{UserVitalsFormValidator, UserSettingsFormValidator};
use App\Models\User;
use App\Controllers\{
BeefController,
    ExerciseController,
    GotoDateModalController,
    JournalEntryRemoveController,
    JournalEntryCreateController,
    RedirectDateController,
    UserSettingsModalController,
    UserSettingsController,
    };

use Respect\Validation\Exceptions\ValidationException;

const FILE_ROOT = __DIR__ . "/../";

session_start();

require_once FILE_ROOT . "/vendor/autoload.php";

$app = new flight\Engine();

Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::$strictMode = true;
Debugger::$showLocation = true;
Debugger::getBar()->addPanel(new App\TracyExtension());

$monolog = new Logger('main-channel');

$monolog->pushHandler(new StreamHandler(Config::get('app.log_file'), Logger::DEBUG));

$tracyLogger = new PsrToTracyLoggerAdapter($monolog);

Debugger::setLogger($tracyLogger);

Debugger::setSessionStorage(new Tracy\NativeSession());

Debugger::$showBar = true;

if ("DEBUG" == Config::get("app.run_mode")) {
    $app->set("debug_mode", true);
    Debugger::enable(Debugger::DETECT);
} else {
    $app->set("debug_mode", false);
    Debugger::enable(Debugger::PRODUCTION);
}

// prevent interference with signing
$app->request()->query->__unset("DEBUG");

$app->map("log", ['Tracy\Debugger', 'log']);

$app->set("domain", Config::get("domain"));

$app->set('flight.log_errors', true);
$app->set('flight.views.path', Config::get("app.view_root"));
$app->set('flight.views.extension', ".phtml");

$connection = \Delight\Db\PdoDatabase::fromDsn(new \Delight\Db\PdoDsn(Config::get('app.cnx.dsn')));

$app->register(
    'clockwork',
    'Clockwork\Support\Vanilla\Clockwork::init',
    [
        [
            'storage_files_path' => FILE_ROOT . '/storage/clockwork',
            'register_helpers' => true,
        ]
    ]
);

$app->register(
    'auth',
    'Delight\Auth\Auth',
    [$connection, null, null, false]
);

$app->register(
    'validate',
    'App\Validate'
);

$app->register(
    'url',
    'App\SignUrl',
    [(new MD5UrlSigner(Config::get("app.url_sign_key")))]
);

// Register controllers
$app->register(
    'TestController',
    'App\Controllers\TestController',
    [$app]
);

$app->register(
    'AuthenticationController',
    'App\Controllers\AuthenticationController',
    [$app]
);

$app->register(
    'HomeController',
    'App\Controllers\HomeController',
    [$app]
);

$app->register(
    'UserSettingsController',
    'App\Controllers\UserSettingsController',
    [$app, App\Validations\ValidatorStore::userSettingsValidator()]
);

$app->register(
    'UserVitalsCreateController',
    'App\Controllers\UserVitalsCreateController',
    [$app, App\Validations\ValidatorStore::userWeightValidator()]
);

$app->register(
    'UserVitalsModalController',
    'App\Controllers\UserVitalsModalController',
    [$app]
);

$app->register(
    'JournalEntryRemoveController',
    'App\Controllers\JournalEntryRemoveController',
    [$app]
);

$app->register(
    'JournalEntryCreateController',
    'App\Controllers\JournalEntryCreateController',
    [$app]
);

$app->register(
    'UserVitalsLogController',
    'App\Controllers\UserVitalsLogController',
    [$app]
);

$app->register(
    'UserSettingsModalController',
    'App\Controllers\UserSettingsModalController',
    [$app]
);

$app->before('route', function () {
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

if (true == $app->get("debug_mode")) {
    Capsule::enableQueryLog();
}

Session::put('key', 'value');
exit;



$app->register(
    'stats',
    'App\Stats',
    [$app]
);

$app->register(
    'payload',
    'Aura\Payload\Payload'
);

$app->register(
    'journalItem',
    'App\Models\JournalItem'
);

$app->register(
    'food',
    'App\Models\Food'
);

$app->map("offset2date", function ($offset) {
    return Carbon::now()->addDays($offset);
});

$app->map("verifySignature", function () use ($app) {
    if (
        empty($app->request()->query->signature) ||
        true != $app->url()->validate($app->request()->url)
    ) {
        $app->log("Signature invalid");
        return false;
    }
    return true;
});

$app->map("hxheader", function ($message, $status = "success", $exception = null) use ($app) {
    $x = json_encode([
        "showMessage" => [
            "level" => $status,
            "message" => $message,
        ],
    ]);

    header('HX-Trigger: ' . $x);

    if (empty($exception)) {
        $app->log($message);
    } else {
        $app->log($exception->getMessage());
    }
});

$app->map("hxtrigger", function ($actions) {
    $z = json_encode($actions);
    header('HX-Trigger: ' . $z);
});

// end routing after redirect
$app->after("redirect", function () {
    exit;
});

/*
 * routes begin!
 */

// routes for debugging
if (true == $app->get("debug_mode")) {

    $app->route("/__clockwork/request", function () use ($app) {

        echo new Illuminate\Http\JsonResponse($app->clockwork()->getMetadata([]));
    });

    $app->route("GET /info", function () {
        phpinfo();
    });
}

$app->route("GET /login", function () use ($app) {
    if (true == $app->auth()->isLoggedIn()) {
        $app->redirect("/home", 302);
    }
    $app->render("login", ['app' => $app]);
});

$app->route("POST /login", function () use ($app) {
    if (true == $app->auth()->isLoggedIn()) {
        $app->redirect("/home", 302);
    }

    try {
        $controller = $app->AuthenticationController();
        $controller->loginUser();
        header("HX-Redirect: /home");
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $app->log($e->getMessage());
        $app->hxheader('Unrecognized email address. Have you registered yet?', 'error');
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $app->log($e->getMessage());
        $app->hxheader('Wrong password', 'error');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $app->log($e->getMessage());
        $app->hxheader('Email not verified', 'error');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $app->log($e->getMessage());
        $app->hxheader('Too many requests', 'error');
    } catch (\App\Exceptions\FormException $e) {
        $app->log($e->getMessage());
        $app->hxheader($e->getMessage(), 'error');
    } catch (\Exception $e) {
        $app->log($e->getMessage());
        $app->hxheader("Unable to login at this time. Please contact Chris.", "error");
    } catch (\Error $er) {
        $app->log($er->getMessage());
        $app->hxheader("Unable to login at this time. PLease contact Chris.", "error");
    }

    // for iframe
    echo 'success';
});

$app->route("POST /register", function () use ($app) {
    try {
        $controller = $app->AuthenticationController();
        $controller->registerUser(true); // argument: true means login immediately after successful registration
        $app->hxtrigger([
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
        $app->log($e->getMessage());
        $app->hxheader($e->getMessage(), 'error');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $app->log($e->getMessage());
        $app->hxheader("Invalid email address", "error");
    } catch (\Delight\Auth\DuplicateUsernameException $e) {
        $app->log($e->getMessage());
        $app->hxheader("That username is already taken", "error");
        echo "That username is already taken";
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $app->log($e->getMessage());
        $app->hxheader("Invalid password", "error");
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $app->log($e->getMessage());
        $app->hxheader("User already registered", "error");
        echo "User already registered";
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $app->log($e->getMessage());
        $app->hxheader("You have done that too many times. Try again later", "error");
    } catch (\Exception $e) {
        $app->log($e->getMessage());
        $app->hxheader("Unknown error. Contact Chris.", "error");
    } catch (\Error $er) {
        $app->log($er->getMessage());
        $app->hxheader($er->getMessage(), "error");
    }
});

$app->route("GET /reset-pw", function () use ($app) {
    $app->render("reset-pw", ["app" => $app]);
});

$app->route("POST /reset-password", function () use ($app) {
    try {
        $controller = $app->AuthenticationController();
        $controller->resetPassword();
        $controller->useOtherRoute("partials/pw-reset");
        $controller();
        //        sleep(1);
        //header("HX-Redirect: /");
    } catch (Delight\Auth\InvalidEmailException $e) {
        $app->render("partials/pw-reset", []);
        //$app->hxheader('Unknown email address. Are you registered?', 'error');
        Debugger::log("Attempt to reset password for unknown email address", Tracy\ILogger::EXCEPTION);
    } catch (Exception $e) {
        $app->log("exception: " . $e->getMessage());
    }
});

$app->route("GET /verify_email", function () use ($app) {

    $data = $app->request()->query->getData();

    if (true !== (isset($data['selector']) && isset($data['token']))) {
        Debugger::log("Someone attempted to verify with invalid credentials");
        $app->redirect("/home", 302);
    }

    $app->render("login", [
        "app" => $app,
        "selector" => $data['selector'],
        "token" => $data['token'],
        "show" => false,
        "pw_reset" => true,
    ]);
    exit;
});

$app->route("POST /verify_email", function () use ($app) {
    try {
        $controller = $app->AuthenticationController();
        $controller->setNewPassword();
        $controller->useOtherRoute();
    } catch (\App\Exceptions\FormException $e) {
        $app->log($e->getMessage());
        $app->hxheader($e->getMessage(), 'error');
    } catch (Exception $e) {
        $app->log($e->getMessage());
    }
});

$app->route("*", function () use ($app) {
    if (false == $app->auth()->isLoggedIn()) {
        $app->redirect("/login", 302);
    }

    try {
        $app->set("ActiveUser", (new User())->findOrFail($app->auth()->getUserId()));
    } catch (ModelNotFoundException $e) {
        $app->log($e->getMessage());
        $controller = $app->AuthenticationController();
        $controller->logoutUser();
        $app->redirect("/login");
    } catch (Exception $e) {
        $app->log($e->getMessage());
        $app->stop();
    }
    return true;
});

$app->route('GET *', function () {
    return true;
}, true);

// middleware kinda
// $app->before("start", function () {

// });
// $app->route('POST *', function () {
//     bdump("MIDDLE");
// }, true);

$app->route("GET|POST /logout", function () use ($app) {
    try {
        $controller = $app->AuthenticationController();
        $controller->logoutUser();
        header("HX-Redirect: /login");
    } catch (\Delight\Auth\NotLoggedInException $e) {
        $app->log($e->getMessage());
        $app->hxheader("Not logged in", "info");
    } catch (Exception $e) {
        $app->hxheader("There was an error logging out. Oops!", "error");
    }
});

$app->route('GET /(home|index)', function () use ($app) {
    $app->redirect($app->url()->sign("/home/0/0"));
});

$app->route('GET /home/(@omo:-?[0-9]+(/@bpo:-?[0-9]+))', function ($omo, $bpo) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
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

    $app->set("omo", $omo); // journal
    $app->set("bpo", $bpo); // big-picture
    $controller = $app->HomeController();
    $controller();
});

$app->route('GET /goto/@date', function ($date) use ($app) {
    (new RedirectDateController($app, $date))();
});

$app->route('GET /beef/@min/@max', function ($min, $max) use ($app) {
    try {
        $controller = new BeefController(
            $app,
            $min,
            $max
        );
        return $app->json($controller->getPayload());
    } catch (Exception $e) {
        $app->log($e->getMessage());
        return $app->json([]);
    }
});

$app->route('GET /modals/go-to-date-modal/@date', function ($date) use ($app) {
    try {
        $controller = new GotoDateModalController($app, $date);
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /modals/user-settings', function () use ($app) {
    try {
        $controller = $app->UserSettingsModalController();
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /modals/user-vitals', function () use ($app) {
    try {
        $controller = $app->UserVitalsModalController();
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /modals/vitals-log', function () use ($app) {
    try {
        $controller = $app->UserVitalsLogController();
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /journal/rel/@offset', function ($offset) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }
    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/journal");
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
    }
});

$app->route('GET /home/right-canvas/rel', function () use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }

    $app->render("partials/offcanvas-graphs", ['app' => $app]);
});

$app->route("GET /home/title-bar/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+", function ($omo, $bpo) use ($app) {
    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/title-bar");
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
    }
});

$app->route('GET /home/left-canvas/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+', function ($omo, $bpo) use ($app) {

    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
    }
});

$app->route('GET /home/big-picture/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+', function ($omo, $bpo) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }

    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/big-picture");
        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
    }
});

$app->route('DELETE /journal-entry/@id', function ($id) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }

    try {
        $controller = $app->JournalEntryRemoveController();
        $controller->deleteEntry($id);
        $app->hxheader("Entry removed.");
    } catch (\Exception $e) {
        $app->log($e->getMessage());
        $app->hxheader("Something went wrong. Contact Chris!!", "error");
        $app->halt(204);
    }
});

if (true == $app->get("debug_mode")) {
    $app->route('POST /drop-food-log', function () use ($app) {
        try {
            $controller = $app->JournalEntryRemoveController();
            $controller->deleteAll();
            $app->stop();
        } catch (Exception $e) {
            $app->log()->info($e->getMessage());
            $app->hxheader("Unable to dump food log table. Contact Chris!");
            $app->stop();
        }
    });
}

$app->route('POST /journal-entry/exercised/rel/@offset', function ($offset) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }
    try {
        $controller = new ExerciseController(
            $app,
            $offset
        );
        $controller->setRoute("partials/exercised-statement");
        $controller->saveUpdate();

        $controller();
    } catch (Exception $e) {
        $app->log($e->getMessage());
    }
});

$app->map("welcome", function () {
    if (6 <= date("H") && 12 > date("H")) {
        return "good morning";
    } elseif (12 <= date("H") && 18 > date("H")) {
        return "good afternoon";
    } else {
        return "good evening";
    }
});

$app->route('GET /food-support-message', function () {
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

$app->route('POST /user-settings', function () use ($app) {
    try {
        $form = $app->UserSettingsController();

        $form->validate(1);
        $app->hxheader("Success!");
        $form->saveUpdate();
        header("HX-Refresh:true");
    } catch (ValidationException $e) {
        echo $app->json(['message' => $e->getMessage()]);
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
    } catch (\App\Exceptions\FormException $e) {
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
    } catch (\Exception $e) {
        $app->hxheader("Something went wrong", "error");
        $app->log($e->getMessage(), "error");
    }
});

$app->route('POST /user-goals', function () use ($app) {
    try {
        $form = new UserSettingsController(
            $app,
            App\Validations\ValidatorStore::userGoalsValidator()
        );
        $form->validate(1);
        $app->hxheader("Success!");
        $form->saveUpdate();
        header("HX-Refresh:true");
    } catch (ValidationException $e) {
        echo $app->json(['message' => $e->getMessage()]);
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
    } catch (\App\Exceptions\FormException $e) {
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
    } catch (\Exception $e) {
        $app->hxheader("Something went wrong", "error");
        $app->log($e->getMessage(), "error");
    }
});

$app->route('POST /user-vitals/weight', function () use ($app) {
    try {
        $form = $app->UserVitalsCreateController();
        $form->validate(1);
        $app->hxheader("Success!");
        $form->saveWeight();
    } catch (ValidationException $e) {
        echo $app->json(['message' => $e->getMessage()]);
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
    } catch (\App\Exceptions\FormException $e) {
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
    } catch (\Exception $e) {
        $app->hxheader("Something went wrong", "error");
        $app->log($e->getMessage(), "error");
    }
});


$app->route('POST /journal-entry', function () use ($app) {
    try {
        if (!$app->verifySignature()) {
            throw new \App\Exceptions\FormException("Sorry, your progress was not recorded.");
        }
        $app->log($app->request()->data->getData());

        $controller = $app->JournalEntryCreateController();
    } catch (\App\Exceptions\FormException $e) {
        echo '<div>Something went wrong!:(</div>';
        $app->hxheader($e->getMessage(), "error");
        $app->log($e->getMessage(), "error");
        exit;
    } catch (\Exception $e) {
        echo '<div>Something went wrong!:(</div>';
        $app->hxheader("Sorry, your progress was not recorded.", "error");
        $app->log($e->getMessage(), "error");
        exit;
    }

    try {
        $controller->saveEntry();
        echo "<div>Successly journaled {$controller->amount} units of this food (+{$controller->points} points)!</div>";
    } catch (\Exception $e) {
        $app->log($e->getMessage());
        echo '<div>Something went wrong!:(</div>';
        $app->hxheader("Sorry, your progress was not recorded.", "error");
        return;
    }

    $app->hxheader("Success!");
});

if (true == $app->get("debug_mode")) {
    $app->route('GET /test', function () use ($app) {
        $controller = $app->TestController();
        $controller();
    });

    $app->route('GET /form', function () use ($app) {
        $app->render("form");
    });

    $app->route('POST /form-test', function () use ($app) {
        $controller = new App\Controllers\ProcessFormController($app->request()->data->getData());

        // 1. Filter data.
        // 2. Validate data.
        // 3. Process data
    });
}

$app->map('notFound', function () use ($app) {
    $message = "<p>That thing you were looking for ... it's not here. Click <a href='/'>here</a> to head home.</p>";
    $app->halt(404, $message);
});

$app->map('error', function ($ex) use ($app) {
    $app->log($ex->getMessage(), "error");
    if (true == $app->get("debug_mode")) {
        $app->log('bluescreen in debug mode');
        $bs = new Tracy\BlueScreen();
        $bs->render($ex);
        exit;
    } else {
        $app->log('bluescreen in production mode');
        throw $ex;
    }
});

$app->start();
