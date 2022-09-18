<?PHP

error_reporting(error_reporting() & ~E_DEPRECATED);

date_default_timezone_set('US/Eastern');

use Spatie\Ignition\Ignition;
use Carbon\Carbon;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;

use Spatie\UrlSigner\MD5UrlSigner;
use Delight\Cookie\Session;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\ErrorHandler;

use App\Models\User;
use App\Controllers\{
    BeefController,
    ExerciseController,
    GotoDateModalController,
    RedirectDateController,
    UserSettingsController,
};

use Respect\Validation\Exceptions\ValidationException;

const FILE_ROOT = __DIR__ . "/../";

require_once FILE_ROOT . "/vendor/autoload.php";

session_start();

$app = new flight\Engine();

$monolog = new Logger('main-channel');

$monolog->pushHandler(new StreamHandler(Config::get('app.log_file'), Logger::DEBUG));

ErrorHandler::register($monolog);

if ("DEBUG" == Config::get("app.run_mode")) {
    $app->set("debug_mode", true);
} else {
    $app->set("debug_mode", false);
}

$ignition = Ignition::make()
    ->applicationPath("/users/ramos/www/")
    ->shouldDisplayException($app->get("debug_mode"))
    ->useDarkMode()
    ->returnAsString(true)
    ->register();

// prevent interference with signing
$app->request()->query->__unset("DEBUG");

$app->map("log", function () use ($monolog) {
    return $monolog;
});

$app->set("domain", Config::get("domain"));

$app->set('flight.log_errors', true);
$app->set('flight.views.path', Config::get("app.view_root"));
$app->set('flight.views.extension', ".phtml");

$connection = \Delight\Db\PdoDatabase::fromDsn(new \Delight\Db\PdoDsn(Config::get('app.cnx.dsn')));


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
    'App\Controllers\UserSettingsControlle',
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
    header("X-Powered-By: Meep");
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
        $app->log()->info("Signature invalid");
        return false;
    }
    return true;
});

$app->map("notify", function ($message, $status = "success", $exception = null) use ($app) {
    $x = json_encode([
        "showMessage" => [
            "level" => $status,
            "message" => $message,
        ],
    ]);

    header('HX-Trigger: ' . $x);

    if (empty($exception)) {
        $app->log()->info($message);
    } else {
        $app->log()->error($exception->getMessage());
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
    $app->route('*', function () {
        return true;
    }, true);

    $app->route("GET /info", function () {
        phpinfo();
    });
}

$app->route("GET /login", function () use ($app) {
    if (true == $app->auth()->isLoggedIn()) {
        Session::set("flash-greeting", "Already logged in!");
        $app->redirect("/home", 302);
    }
    if (true == $app->get("debug_mode")) {
        Session::set("flash-greeting", "Happy Today!");
    } else {
        Session::set("login-greeting", "Please note, this is just a demo; your data WILL be deleted.");
    }

    $app->render("login", [
        'app' => $app
    ]);
});

$app->route("POST /login", function () use ($app) {
    if (true == $app->auth()->isLoggedIn()) {
        $app->redirect("/home", 302);
    }

    try {
        $controller = $app->AuthenticationController();
        $controller->loginUser();
        Session::set("flash-greeting", "Welcome {$app->auth()->getUsername()}!");
        header("HX-Redirect: /home");
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $app->log()->info($e->getMessage());
        $app->notify('Unrecognized email address. Have you registered yet?', 'error');
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $app->log()->info($e->getMessage());
        $app->notify('Wrong password', 'error');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $app->log()->info($e->getMessage());
        $app->notify('Email not verified', 'error');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $app->log()->info($e->getMessage());
        $app->notify('Too many requests', 'error');
    } catch (\App\Exceptions\FormException $e) {
        $app->log()->info($e->getMessage());
        $app->notify($e->getMessage(), 'error');
    } catch (\Exception $e) {
        $app->log()->info($e->getMessage());
        $app->notify("Unable to login at this time. Please contact Chris.", "error");
    } catch (\Error $er) {
        $app->log()->info($er->getMessage());
        $app->notify("Unable to login at this time. Please contact Chris.", "error");
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
        $app->log()->info($e->getMessage());
        $app->notify($e->getMessage(), 'error');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $app->log()->info($e->getMessage());
        $app->notify("Invalid email address", "error");
    } catch (\Delight\Auth\DuplicateUsernameException $e) {
        $app->log()->info($e->getMessage());
        $app->notify("That username is already taken", "error");
        echo "That username is already taken";
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $app->log()->info($e->getMessage());
        $app->notify("Invalid password", "error");
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $app->log()->info($e->getMessage());
        $app->notify("User already registered", "error");
        echo "User already registered";
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $app->log()->info($e->getMessage());
        $app->notify("You have done that too many times. Try again later", "error");
    } catch (\Exception $e) {
        $app->log()->info($e->getMessage());
        $app->notify("Unknown error. Contact Chris.", "error");
    } catch (\Error $er) {
        $app->log()->info($er->getMessage());
        $app->notify($er->getMessage(), "error");
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
        //$app->notify('Unknown email address. Are you registered?', 'error');
        $app->log()->error("Attempt to reset password for unknown email address");
    } catch (Exception $e) {
        $app->log()->error("exception: " . $e->getMessage());
    }
});

$app->route("GET /verify_email", function () use ($app) {

    $data = $app->request()->query->getData();

    if (true !== (isset($data['selector']) && isset($data['token']))) {
        $app->log()->notice("Someone attempted to verify with invalid credentials");
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
        $app->log()->error($e->getMessage());
        $app->notify($e->getMessage(), 'error');
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
    }
});

$app->route("*", function () use ($app) {
    if (false == $app->auth()->isLoggedIn()) {
        $app->redirect("/login", 302);
    }

    try {
        $app->set("ActiveUser", (new User())->findOrFail($app->auth()->getUserId()));
    } catch (ModelNotFoundException $e) {
        $app->log()->error($e->getMessage());
        $controller = $app->AuthenticationController();
        $controller->logoutUser();
        $app->redirect("/login");
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
        $app->stop();
    }
    return true;
});



// middleware kinda
// $app->before("start", function () {

// });
// $app->route('POST *', function () {

// }, true);

$app->route("GET|POST /logout", function () use ($app) {
    Session::set("flash-greeting", "See ya soon!");
    try {
        $controller = $app->AuthenticationController();
        $controller->logoutUser();
        if ("POST" == $app->request()->method) {
            Session::set("flash-greeting", "See ya soon!");
        }
        header("HX-Redirect: /login");
    } catch (\Delight\Auth\NotLoggedInException $e) {
        $app->log()->error($e->getMessage());
        if ("POST" == $app->request()->method) {
            Session::set("flash-greeting", "See ya soon!");
        }
    } catch (Exception $e) {
        if ("POST" == $app->request()->method) {
            Session::set("flash-greeting", "There was an error logging out.");
        }
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
        $app->log()->error($e->getMessage());
        return $app->json([]);
    }
});

$app->route('GET /modals/go-to-date-modal/@date', function ($date) use ($app) {
    try {
        $controller = new GotoDateModalController($app, $date);
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /modals/user-settings', function () use ($app) {
    try {
        $controller = $app->UserSettingsModalController();
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /modals/user-vitals', function () use ($app) {
    try {
        $controller = $app->UserVitalsModalController();
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /modals/vitals-log', function () use ($app) {
    try {
        $controller = $app->UserVitalsLogController();
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
        $app->halt(404);
    }
});

$app->route('GET /journal/rel/@offset', function () use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }
    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/journal");
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
    }
});

$app->route('GET /home/right-canvas/rel', function () use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }

    $app->render("partials/offcanvas-graphs", ['app' => $app]);
});

$app->route("GET /home/title-bar/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+", function ($omo, $bpo) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }
    $app->set("omo", $omo);
    $app->set("bpo", $bpo);
    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/title-bar");
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
    }
});

$app->route('GET /home/left-canvas/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+', function ($omo, $bpo) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }

    $app->set("omo", $omo);
    $app->set("bpo", $bpo);
    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/offcanvas-menu");
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
    }
});

$app->route('GET /home/big-picture/rel/@omo:-?[0-9]+/@bpo:-?[0-9]+', function ($omo, $bpo) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }
    $app->set("omo", $omo);
    $app->set("bpo", $bpo);
    try {
        $controller = $app->HomeController();
        $controller->useOtherRoute("partials/big-picture");
        $controller();
    } catch (Exception $e) {
        $app->log()->error($e->getMessage());
    }
});

$app->route('DELETE /journal-entry/@id', function ($id) use ($app) {
    if (!$app->verifySignature()) {
        $app->notFound();
    }

    try {
        $controller = $app->JournalEntryRemoveController();
        $controller->deleteEntry($id);
        $app->notify("Entry removed.");
    } catch (\Exception $e) {
        $app->log()->error($e->getMessage());
        $app->notify("Something went wrong. Contact Chris!!", "error");
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
            $app->log()->error($e->getMessage());
            $app->notify("Unable to dump food log table. Contact Chris!", 'error');
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
        $app->log()->error($e->getMessage());
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
        $app->notify("Success!");
        $form->saveUpdate();
        header("HX-Refresh:true");
    } catch (ValidationException $e) {
        echo $app->json(['message' => $e->getMessage()]);
        $app->notify($e->getMessage(), "error");
        $app->log()->error($e->getMessage(), "error");
    } catch (\App\Exceptions\FormException $e) {
        $app->notify($e->getMessage(), "error");
        $app->log()->error($e->getMessage(), "error");
    } catch (\Exception $e) {
        $app->notify("Something went wrong", "error");
        $app->log()->error($e->getMessage(), "error");
    }
});

$app->route('POST /user-goals', function () use ($app) {
    try {
        $form = new UserSettingsController(
            $app,
            App\Validations\ValidatorStore::userGoalsValidator()
        );
        $form->validate(1);
        $app->notify("Success!");
        $form->saveUpdate();
        header("HX-Refresh:true");
    } catch (ValidationException $e) {
        echo $app->json(['message' => $e->getMessage()]);
        $app->notify($e->getMessage(), "error");
        $app->log()->error($e->getMessage(), "error");
    } catch (\App\Exceptions\FormException $e) {
        $app->notify($e->getMessage(), "error");
        $app->log()->error($e->getMessage(), "error");
    } catch (\Exception $e) {
        $app->notify("Something went wrong", "error");
        $app->log()->error($e->getMessage(), "error");
    }
});

$app->route('POST /user-vitals/weight', function () use ($app) {
    try {
        $form = $app->UserVitalsCreateController();
        $form->validate(1);
        $app->notify("Success!");
        $form->saveWeight();
    } catch (ValidationException $e) {
        echo $app->json(['message' => $e->getMessage()]);
        $app->notify($e->getMessage(), "error");
        $app->log()->error($e->getMessage(), "error");
    } catch (\App\Exceptions\FormException $e) {
        $app->notify($e->getMessage(), "error");
        $app->log()->error($e->getMessage(), "error");
    } catch (\Exception $e) {
        $app->notify("Something went wrong", "error");
        $app->log()->error($e->getMessage(), "error");
    }
});

$app->route('POST /journal-entry', function () use ($app) {
    try {
        if (!$app->verifySignature()) {
            throw new \App\Exceptions\FormException("Sorry, your progress was not recorded.");
        }
        $controller = $app->JournalEntryCreateController();
    } catch (\App\Exceptions\FormException $e) {
        $app->log()->error($e->getMessage());
        $app->notify($e->getMessage(), "error");
        exit;
    } catch (\Exception $e) {
        $app->log()->error($e->getMessage(), "error");
        $app->notify("Sorry, your progress was not recorded.", "error");
        exit;
    }

    try {
        $controller->saveEntry();
        $app->notify("Success!");
        echo "<div>Successly journaled {$controller->amount} units of this food (+{$controller->points} points)!</div>";
    } catch (\Exception $e) {
        $app->log()->error($e->getMessage());
        echo '<div>Something went wrong!:(</div>';
        $app->notify("Sorry, your progress was not recorded.", "error");
        return;
    }
});

$app->route("PUT /ui-journal/open", function () {
    Session::set("ui-journal-open", 1);
});

if (true == $app->get("debug_mode")) {
    $app->route('GET /test', function () use ($app) {
        $app->response()
            ->status(400)
            ->write("ROFL")
            ->send();
    });

    $app->route('GET /form', function () use ($app) {
        $app->render("form");
    });

    $app->route('POST /form-test', function () {
        // $controller = new App\Controllers\ProcessFormController($app->request()->data->getData());

        // 1. Filter data.
        // 2. Validate data.
        // 3. Process data
    });
}

$app->map('notFound', function () use ($app) {
    $app->response()
        ->status(404)
        ->write(
            "<p>That thing you were looking for ... it's not here. Click <a href='/'>here</a> to head home.</p>"
        )
        ->send();
});

$app->route("GET /ignition-error", function () use ($app) {
    if ($ignition_html = Session::take("ignition-error")) {
        echo $ignition_html;
    } else {
        $app->notFound();
    }
});

$app->map('error', function ($ex) use ($app, $ignition) {
    if (true == $app->get("debug_mode")) {
        if ("GET" === $app->request()->method) {
            $app->response()->write($ignition->renderException($ex))->send();
        } else {
            $html = $ignition->renderException($ex);
            Session::set("ignition-error", $html);
            $app
                ->response()
                ->status(200)
                ->header('hx-redirect', "/ignition-error")
                ->send();
        }
    }
});

$app->start();
