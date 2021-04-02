<?PHP
date_default_timezone_set('US/Eastern');
use Tracy\Debugger;
use Carbon\Carbon;
use Aura\Payload\Payload;
use Aura\Payload_Interface\PayloadStatus;
use Illuminate\Database\Capsule\Manager as Capsule;
use Spatie\UrlSigner\MD5UrlSigner;

const WEB_ROOT = __DIR__;
const FILE_ROOT = __DIR__ . "/..";
const DEBUG = true;

require_once FILE_ROOT . "/vendor/autoload.php";

App\Config::init();

if (!file_exists(FILE_ROOT . '/tracy')) {
    mkdir(FILE_ROOT . '/tracy', 0755, true);
}


session_start();
Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::enable(Debugger::DETECT, FILE_ROOT . '/tracy');

if (true != DEBUG) {
    Debugger::$showBar = false;
}

Flight::set('flight.log_errors', true);
Flight::set('flight.views.path', '../views');
Flight::set('flight.views.extension', ".phtml");

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

Flight::map("verifySignature", function () {
    if (
        empty(Flight::request()->query->signature) ||
        true != Flight::url()->validate(Flight::request()->url)
    ) {
        return false;
    }
    return true;
});

Flight::route('*', function ($route) {
    return true;
}, true);


Flight::route('GET /(home|index)', function () {
    $foods = Flight::food()::all();

    $today_points = Flight::journalItem()->getSum(date("Y-m-d"));

    $checkbox_date = date("Y-m-d");

    $daily_model = \App\Models\Daily::firstWhere("date", $checkbox_date);
    if (null == $daily_model) {
        $daily_model = \App\Models\Daily::create([
            "date" =>  $checkbox_date
        ]);
    }

    header('HX-Trigger-After-Settle: {"showMessage":{"level" : "info", "message" : "Here Is A Message"}}');

    Flight::render('index', [
        "foods" => $foods,
        "today_points" => $today_points,
        "checkbox_date" => $checkbox_date,
        "exercised" => $daily_model->exercised,
    ]);
});

Flight::route('GET /journal/date/@min(/@max)', function ($min, $max) {
    $dates = [$min, $max];
    usort($dates, "strcmp");

    list($min, $max) = $dates;

    if (false === strtotime($min)) {
        return 'error';
    }

    $query = Flight::journalItem()
           ->whereDate("date", ">=", $min);

    if (true === strtotime($max)) {
        $query->whereDate("date", "<=", $max);
    }

    $records = $query->orderBy('date')
                     ->get()
                     ->groupBy(function ($val) {
                         return Carbon::parse($val->date)->format('Y-m-d');
                     });
    // this is now ready to use in a chart or something
    foreach ($records as $date => $item) {
        Debugger::log([$date, $item->sum('points')]);
    }
    echo 'ok';
});

Flight::route('GET /journal-total/@date', function ($date) {
    $sum = Flight::journalItem()->getSum($date);

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
    $offset = (int) $offset;
    Flight::render("partials/offcanvas-menu", [
        "journal_day_offset" => $offset
    ]);
});

Flight::route('GET /right-canvas', function () {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }
    Flight::render("partials/offcanvas-graphs", [
    ]);
});

Flight::route('GET /off-canvas-left/rel/@offset', function ($offset) {
    return Flight::render("partials/offcanvas-menu", [
        "journal_day_offset" => $offset
    ]);
});

Flight::route('GET /big-picture/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }
    $offset = (int) $offset;
    Flight::render("partials/big-picture", [
        "journal_day_offset" => $offset,
    ]);
});

Flight::route('GET /example', function () {
    echo '<div>example</div>';
});

Flight::route('DELETE /journal-entry/@id', function ($id) {
    Flight::halt(204);

    if (false == is_numeric($id)) {
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Unknown deletion candidate"}}');
        Flight::stop();
    }

    try {
        $item = Flight::journalItem()::findOrFail($id);
        $item->delete();
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "success", "message" : "Deleted"}}');
        Flight::stop();
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Something went wrong. Contact Chris."}}');
        Flight::stop();
    }
});

Flight::route('POST /drop-food-log', function () {
    if (false === DEBUG) {
        return;
    }
    try {
        Flight::journalItem()::truncate();
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "info", "message" : "Food log emptied"}}');
        Flight::stop();
    } catch (\Exection $e) {
        Tracy\Debugger::log($e->getMessage());
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Unable to dump food log table"}}');
        Flight::stop();
    }
});

Flight::route('POST /exercised/rel/@offset', function ($offset) {
    if (!Flight::verifySignature()) {
        Flight::notFound();
    }
    $offset = (int) $offset;
    $exercised = Flight::request()->data['exercised'];

    if (isset($exercised)) {
        $x = App\Models\Daily::updateOrCreate([
            "date" => Carbon::now()->addDays($offset)->format("Y-m-d"),
        ], [
            "exercised" => 1
        ]);
        return Flight::render("partials/exercised-statement", ["exercised" => 1]);
    } else {
        $x = App\Models\Daily::updateOrCreate([
            "date" => Carbon::now()->addDays($offset)->format("Y-m-d"),
        ], [
            "exercised" => 0
        ]);
        return Flight::render("partials/exercised-statement", ["exercised" => 0]);
    }
});

Flight::map("welcome", function () {
    if (6 <= date("H") && 12 > date("H")){
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
    $formData = Flight::request()->data;

    if (false == is_numeric($formData['amount'])) {

        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "success", "message" : "Amount must be numeric"}}');

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
        ]);
    }

    if (!isset($formData['amount']) || 0 >= $formData['amount']) {
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Must enter food amount"}}');

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
        ]);
    }

    if (false == strtotime($formData['date'])) {
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Must enter food amount"}}');

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
        ]);
    }

    if (empty($formData['food-selection']) || false == is_numeric($formData['food-selection'])) {
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Must enter food name"}}');

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
    }

    try {
        $food_model = Flight::food()::findOrFail($formData['food-selection']);
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Sorry, this food item is not recognized."}}');
        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
        ]);
    }

    $amount = (float) $formData['amount'];

    $food = (int) $formData['food-selection'];
    $date = $formData['date'];
    $date = $date . " " . date("H:i:s");


    $item_points = $food_model->points;
    $total_points = $item_points * $amount;


    $data = [
        "date" => $date,
        "food" => $food,
        "quantity" => $amount,
        "points" => $total_points,
    ];

    try {
        Flight::journalItem()->create($data);
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        header('HX-Trigger-After-Settle: {"showMessage":{"level" : "error", "message" : "Sorry, your progress was not recorded. Ask chris for help."}}');
        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
        ]);
    }

    // offset of submitted value
    $earlier = new DateTime($formData['date']);
    $later = new DateTime("now");
    $interval = $later->diff($earlier);
    $days = $interval->format("%a") * (1 == $interval->invert ? -1 : 1);

    header('HX-Trigger-After-Settle: {"showMessage":{"level" : "success", "message" : "Success"}}');

    Flight::render("partials/big-picture", [
        "journal_day_offset" => $days,
    ]);
});

Flight::route("GET /bootstrap", function () {
    Flight::render("bootstrap", []);
});

Flight::map('notFound', function () {
    Debugger::log("Not found called. Possible fuzzer");
    echo "<p>That thing you were looking for ... it's not here. Click <a href='/'>here</a> to head home.</p>";
    exit;
});

Flight::map('error', function ($ex) {
    Debugger::log($ex);
});

Flight::start();
