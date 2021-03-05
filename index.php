<?PHP

date_default_timezone_set('US/Eastern');
use Tracy\Debugger;
use Aura\Payload\Payload;
use Aura\Payload_Interface\PayloadStatus;

require_once __DIR__ . "/vendor/autoload.php";

App\Config::init();

const WEB_ROOT = __DIR__;

if (!file_exists(WEB_ROOT.'/tracy')) {
    mkdir(WEB_ROOT . '/tracy', 0755, true);
}

session_start();
Debugger::$dumpTheme = 'dark';
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::enable(Debugger::DETECT, __DIR__ . '/tracy/');

define("DEBUG", true);

Flight::set('flight.log_errors', true);
Flight::set('flight.views.extension', ".phtml");

Flight::map('now', function ($format = 'Y-m-d') {
    $tz = 'America/New_York';
    $timestamp = time();
    $dt = new DateTime("now", new \DateTimeZone($tz));
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

Flight::register(
    'stats',
    'App\Stats'
);

Flight::register(
    'payload',
    'Aura\Payload\Payload'
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

Flight::route('GET *', function () {
    echo 'shit';
    return true;
});

Flight::route('GET /', function () {
    Flight::render('index', []);
});

Flight::route('GET /journal/rel/@offset', function ($offset) {
    $offset = (int) $offset;
    $records = Flight::db()
             ->query("SELECT `id`, * FROM `points_records` WHERE DATE(`date`) = DATE('now', 'localtime', '$offset day') ORDER BY date DESC")
             ->fetchAll();
    Flight::render("partials/offcanvas-menu", [
        "journal_day_offset" => $offset
    ]);
});

Flight::route('GET /big-picture/rel/@offset', function ($offset) {
    $offset = (int) $offset;
    Flight::render("partials/big-picture", [
        "journal_day_offset" => $offset,
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

Flight::route('DELETE /journal-entry/@id', function ($id) {
    if (false == is_numeric($id)) {
        return Flight::render("partials/message", [
            "status" => "error",
            "message" => "A non-existant resouce was requested. Contact Chris."
        ]);
    }

    $rowID = $id;
    $statement = <<<SQL
DELETE FROM points_records
WHERE rowid=:rowID
SQL;

    try {
        Flight::db()->prepare($statement)->execute(["rowID" => $rowID]);
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
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
    if (false === DEBUG) {
        return;
    }
    $statement = "DELETE FROM points_records";
    try {
        Flight::db()->prepare($statement)->execute();
        echo Flight::json([
            "error" => 0,
            "response" => [
                "message" => "Food log emptied",
            ]
        ]);
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
        Flight::render("partials/exercised-statement", [
            "exercised" => 1
        ]);
    } catch (\Exception $e) {
        Debugger::log($e->getMessage());
        return Flight::view("ROFL");
    }
});

Flight::route('POST /submit-food-log', function () {
    $formData = Flight::request()->data;

    $payload = new Payload();

    if (false == is_numeric($formData['amount'])) {
        $payload->setStatus(PayloadStatus::FAILURE);
        $payload->setMessages([
            "Amount must be numeric, but you entered " . $formData['amount'],
        ]);

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
    }

    if (!isset($formData['amount']) || 0 >= $formData['amount']) {
        $payload->setStatus(PayloadStatus::FAILURE);
        $payload->setMessages(["Must enter food amount"]);

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
    }

    if (false == strtotime($formData['date'])) {
        $payload->setStatus(PayloadStatus::FAILURE);
        $payload->setMessages(["Date value is unrecognized: " . $formData['date']]);

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
    }

    if (empty($formData['food-selection']) || false == is_numeric($formData['food-selection'])) {
        $payload->setStatus(PayloadStatus::FAILURE);
        $payload->setMessages(["Must enter food name"]);

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
    }

    try {
        $food_exists = Flight::db()
                     ->query("SELECT EXISTS(SELECT 1 FROM food_records WHERE id=" . $formData['food-selection'] . " )")
                     ->fetchColumn()
                     ;
    
        if (false == $food_exists) {
            throw new \Exception("Sorry, this food item is not recognized.");
        }
    } catch (\Exception $e) {
        $payload->setStatus(PayloadStatus::FAILURE);
        $payload->setMessages([$e->getMessage()]);

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
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
        $payload->setStatus(PayloadStatus::FAILURE);
        $payload->setMessages([$e->getMessage()]);

        return Flight::render("partials/big-picture", [
            "journal_day_offset" => 0,
            "payload" => $payload,
        ]);
    }

    // offset of submitted value
    $earlier = new DateTime($formData['date']);
    $later = new DateTime("now");
    $interval = $later->diff($earlier);
    $days = $interval->format("%a") * (1 == $interval->invert ? -1 : 1);

    $payload->setStatus(PayloadStatus::SUCCESS);
    $payload->setMessages(["Success"]);

    Flight::render("partials/big-picture", [
        "journal_day_offset" => $days,
        "payload" => $payload,
    ]);
});

Flight::route("GET /bootstrap", function () {
    Flight::render("bootstrap", []);
});

Flight::map('error', function ($ex) {
    Debugger::log($ex);
    Debugger::dump($ex);
});


// need ...
// validate numeric



Flight::before('start', function (&$params) {
    $query_data = Flight::request()->query->getData();

    $sanitized_query_data = [];

    foreach ($query_data as $k => $v) {
        switch ($k) {
            case "day_offset":
                $sanitized_query_data[$k] = (int) $v;
            case "journal_day_offset":
                $sanitized_query_data[$k] = (int) $v;
            case "searchvalue":
                ;
            default:
                // Debugger::log([
                //     "d" => $query_data,
                //     "c" => $sanitized_query_data,
                //     "p" => $params
                // ]);
        }
    }

    //    Debugger::log(Flight::request()->query);


});


Flight::start();
