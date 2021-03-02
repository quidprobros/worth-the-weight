<?PHP

date_default_timezone_set('US/Eastern');
use Tracy\Debugger;

require_once __DIR__ . "/vendor/autoload.php";

const WEB_ROOT = __DIR__;

session_start();
Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::enable(Debugger::DETECT, __DIR__ . '/tracy/');


define("DEBUG", true);

Flight::map('now', function ($format = 'Y-m-d') {
    $tz = 'America/New_York';
    $timestamp = time();
    $dt = new DateTime("now", new \DateTimeZone($tz)); //first argument "must" be a string
    $dt->setTimestamp($timestamp); //adjust the object to correct timestamp
    return $dt->format($format);
});

Flight::register(
    'db',
    'PDO',
    array('sqlite:db/wtw.db', '', ''),
    function ($db) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
);

Flight::map("getFood", function ($index) {
    return Flight::db()->query("SELECT food FROM food_records WHERE id={$index} LIMIT 1")->fetch()["food"];
});

Flight::map("getRelativeRecords", function ($index = 0) {
    $records = Flight::db()
             ->query("SELECT `id`, * FROM `points_records` WHERE DATE(`date`) = DATE('now', 'localtime', '{$index} days') ORDER BY date DESC")
             ->fetchAll()
             ;

    $points = (int) Flight::db()
                  ->query("SELECT sum(points) as today_points, date(date) as th, date('now', 'localtime', '{$index} days') as tt from points_records where th = tt")
                  ->fetch()["today_points"]
                  ;
    $exercised = (int) Flight::db()
               ->query("SELECT `exercised` from `day_records` WHERE DATE(`date`) = DATE('NOW', 'localtime', '{$index} days')")
               ->fetch()
               ;
    return [
        "points" => $points,
        "exercised" => $exercised,
        "records" => $records,
    ];
});

Flight::map("fractionToDecimal", function ($input) {
    list($n, $d) = explode("/", $input);
    if (empty($d)) {
        return (float) $n;
    }
    if (!is_numeric($n) || is_numeric($d)) {
        return 0;
    }
    return $n / $d;
});

Flight::set('flight.log_errors', true);
Flight::set('flight.views.extension', ".phtml");

Flight::route('GET /', function () {
    Flight::render('index', []);
});

Flight::route('GET /journal-yesterday', function () {
    $offset = ((int) Flight::request()->query['day_offset']) - 1;
    $records = Flight::db()
             ->query("SELECT `id`, * FROM `points_records` WHERE DATE(`date`) = DATE('now', 'localtime', '$offset day') ORDER BY date DESC")
             ->fetchAll();

    Flight::render("partials/offcanvas-menu", [
    ]);
});

Flight::route('GET /journal-tomorrow', function () {
    $offset = ((int) Flight::request()->query['day_offset']) + 1;
    $records = Flight::db()
             ->query("SELECT `id`, * FROM `points_records` WHERE DATE(`date`) = DATE('now', 'localtime', '$offset day') ORDER BY date DESC")
             ->fetchAll();

    Flight::render("partials/offcanvas-menu", [
    ]);
});

Flight::route('POST /big-picture-prev', function () {
    Flight::render("partials/big-picture", [
        "journal_day_offset" => (Flight::request()->data['journal_day_offset'] - 1),
    ]);
});

Flight::route('POST /big-picture-next', function () {
    Flight::render("partials/big-picture", [
        "journal_day_offset" => (Flight::request()->data['journal_day_offset'] + 1),
    ]);
});


Flight::route('POST /search', function () {
    $searchTerm = "%" . Flight::request()->data["searchvalue"] . "%";

    $statement = <<<SQL
SELECT id,food from food_records where food LIKE :searchTerm LIMIT 1000
SQL;

    try {
        $stmt = Flight::db()->prepare($statement);

        $stmt->bindValue(":searchTerm", $searchTerm, PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo Flight::json([
            "error" => 0,
            "response" => [
                "data" => $results
            ]
        ]);
    } catch (\Exception $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => $e->getMessage(),
            ]
        ]);
        exit;
    }
});

Flight::route('POST /submit-delete-row', function () {
    $rowID = Flight::request()->data['rowID'];
    $statement = <<<SQL
DELETE FROM points_records
WHERE rowid=:rowID
SQL;


    try {
        Flight::db()->prepare($statement)->execute(["rowID" => $rowID]);
        echo Flight::json([
            "error" => 0,
            "response" => [
                "message" => "Record deleted",
            ]
        ]);
        exit;
    } catch (\Exception $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => "no delete",
            ]
        ]);
        exit;
    }
});

Flight::route("POST /submit-edit-cell", function () {
    $rowID = Flight::request()->data['rowID'];
    $colID = Flight::request()->data['colID'];
    $value = Flight::request()->data['value'];

    switch ($colID) {
        case "date":
            $statement = <<<SQL
UPDATE points_records
SET date = :value
WHERE rowid = :rowID
SQL;
            break;
        case "amount":
        case "quantity":
            $statement = <<<SQL
UPDATE points_records
SET quantity = :value
WHERE rowid = :rowID
SQL;
            break;
        default:
            return;
    }
    
    try {
        $stmt = Flight::db()->prepare($statement);
        $stmt->bindValue(":value", $value, PDO::PARAM_STR);
        $stmt->bindValue(":rowID", $rowID);

        $stmt->execute();

        //Debugger::log($stmt->debugDumpParams());

        echo Flight::json([
            "error" => 0,
            "response" => [
                "message" => "Record updated",
            ]
        ]);
        exit;
    } catch (\Exception $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => $e->getMessage(),
            ]
        ]);
        exit;
    }
});

Flight::route('POST /drop-food-log', function () {
    $statement = "DELETE FROM points_records";
    try {
        Flight::db()->prepare($statement)->execute();
        echo Flight::json([
            "error" => 0,
            "response" => [
                "message" => "Food log emptied",
            ]
        ]);
        exit;
    } catch (\Exection $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => $e->getMessage(),
            ]
        ]);
    }
});

Flight::route('POST /exercised-today', function () {
    $data = Flight::request()->data;

    $day_offset = $data['journal_day_offset'];
    if (empty($data['exercised'])) {
        $statement = <<<MYSQL
REPLACE INTO day_records(`date`, `exercised`)
values(DATE('NOW', 'localtime', "{$day_offset} days"), 0)
MYSQL;
    } else {
        $statement = <<<MYSQL
REPLACE INTO day_records(`date`, `exercised`)
values(DATE('NOW', 'localtime', "{$day_offset} days"), 1)
MYSQL;  
    }

    try {
        Flight::db()->prepare($statement)->execute();
        echo Flight::json([
            "error" => 0,
            "response" => [
                "message" => "Record updated",
            ]
        ]);
        exit;
    } catch (\Exception $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => "Record not updated",
            ]
        ]);
        exit;
    }
});

Flight::route('POST /submit-food-log', function () {
    $formData = Flight::request()->data;

    // validate amount input
    if (false == is_numeric($formData['amount'])) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => "Amount must be numeric, but you entered " . $formData['amount'],
            ]
        ]);
    }
    if (!isset($formData['amount']) || 0 >= $formData['amount']) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => "Must enter food amount",
            ]
        ]);
        exit;
    }

    if (false == strtotime($formData['date'])) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => "Date value is unrecognized: " . $formData['date'],
            ]
        ]);
    }

    if (empty($formData['food-selection']) || false == is_numeric($formData['food-selection'])) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => "Must enter food name",
            ]
        ]);
        exit;
    }

    try {
        $food_exists = Flight::db()
                     ->query("SELECT EXISTS(SELECT 1 FROM food_records WHERE id=" . $formData['food-selection'] . " )")
                     ->fetchColumn()
                     ;
    
        if (false == $food_exists) {
            echo Flight::json([
                "error" => 1,
                "response" => [
                    "message" => "Sorry, this food item is not recognized."
                ]
            ]);
            exit;
        }
    } catch (\Exception $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => $e->getMessage()
            ]
        ]);
        exit;
    }


    $amount = (float) $formData['amount'];
    $food = (int) $formData['food-selection'];
    $date = $formData['date'];
    $date = $date . " " . date("H:i:s");

    $item_points = Flight::db()->query("SELECT points from food_records WHERE id={$food} LIMIT 1")->fetch()["points"];
    $item_name = Flight::db()->query("SELECT food from food_records WHERE id={$food} LIMIT 1")->fetch()["food"];
    $total_points = $item_points * $amount;

    $statement = <<<SQL
INSERT INTO points_records
(date, food, quantity, points)
VALUES (:date, :food, :quantity, :points)
SQL;
    $data = [
        "date" => $date,
        "food" => $food,
        "quantity" => $amount,
        "points" => $total_points,
    ];

    try {
        
        Flight::db()->prepare($statement)->execute($data);

        $today_points = Flight::db()
                      ->query("SELECT sum(points) as today_points, date(date) as th, date('now') as tt from points_records where th = tt")
                      ->fetch()["today_points"];


        $journal_dates = Flight::db()
                       ->query("SELECT strftime(\"%Y-%m-%d\", \"date\") as thisDate from points_records group by thisDate")
                       ->fetchAll();
        $journaling_days = count($journal_dates);

        $avg_points_daily = Flight::db()
                          ->query("SELECT strftime(\"%d\", \"date\") as day, avg(points) as average from points_records group by day")
                          ->fetch()["average"];

    } catch (\Exception $e) {
        echo Flight::json([
            "error" => 1,
            "response" => [
                "message" => $e->getMessage()
            ]
        ]);
        exit;
    }

    echo Flight::json([
        "error" => 0,
        "response" => [
            "message" => "Food added",
            "data" => [
                "id" => 0,
                "date" => $date,
                "food" => $food,
                "food_name" => $item_name,
                "quantity" => $amount,
                "points" => $total_points,
                "today_points" => $today_points,
                "stats_sentence" => "<p>Your journal is <strong>{$journaling_days}</strong> days long. You are averaging <strong>{$avg_points_daily}</strong> points/day."
            ]
        ]
    ]);
});

Flight::route("GET /bootstrap", function () {
    Flight::render("bootstrap", []);
});


Flight::map('error', function ($ex) {
    Debugger::log($ex);
    Debugger::dump($ex);
});

Flight::start();
