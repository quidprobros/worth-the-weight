#!/usr/bin/env php
<?PHP
date_default_timezone_set('US/Eastern');
use Tracy\Debugger;
use Ahc\Cli\Input\Command;
use Ahc\Cli\Application;
use Carbon\Carbon;
use Aura\Payload\PayloadFactory;
use App\Payload;
use Aura\Payload_Interface\PayloadStatus;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\UrlSigner\MD5UrlSigner;
use App\Models\User;
use App\Models\ActiveUser;
use App\Config;

const WEB_ROOT = __DIR__;
const FILE_ROOT = __DIR__ . "/..";

require_once FILE_ROOT . "/vendor/autoload.php";

// $command = new Command('bootstrap', 'Create your starter database file.');
// $command
//     ->version('0.0.1-dev')
//     ->arguments('<source_db>', 'path to source database')
//     ->option('-o|--output <new_db>');

// //d($command->values());

$app = new Application('App', 'i mean come on');
//$app->add($command, 'k');
$app->logo('Ascii art logo of your app');

// $interactor = new Ahc\Cli\IO\Interactor;

// $confirm = $interactor->confirm('Are you happy?', 'n'); // Default: n (no)
// exit;


// function red($text) {
//     $str = <<<HTML
// <p style='color:red;'>{$text}</p>
// HTML;
//     return $str;
// }
// Config::init(FILE_ROOT . "/db/test.db");

// $shouldContinue = (1 == Flight::request()->query['build']) ? true : false;

// $defaults = [
//     "dbname" => "wtw",
// ];

// $custom = [];

// foreach (Flight::request()->query as $k => $v) {
//     switch ($k) {
//     case "dbname":
//         if (false == ctype_alpha($v)) {
//             echo red('dbname must be alphabetic, using default dbname "wtw"');
//             break;
//         }
//         $custom['dbname'] = $v;
//     }
// }


// $data = array_merge($defaults, $custom);

// $root = FILE_ROOT;
// $dbpathrel = "./db/" . $data['dbname'] . ".db";
// $dbpath = FILE_ROOT . "/db/" . $data['dbname'] . ".db";

// $_dbpathrel = "/db/" . $defaults['dbname'] . ".db";
// $_dbpath = FILE_ROOT . "/db/" . $defaults['dbname'] . ".db";

// $dsn = "sqlite" . ":" . $dbpath;

// if (true !== $shouldContinue) {
//     echo <<<TXT
// This script will create a sqlite database with the structures required to run this application.
// <h1>Settings</h1>
// <ul>
//   <li>Project root is <strong>{$root}</strong></li>
//   <li>The database will be saved as <strong>{$dbpathrel}</strong> (default: {$_dbpathrel})</li>
//   <li>An existing database will <strong>not</strong> be overriden</li>
// </ul>
// <br>
// <h1>Actions</h1>
// <ul>
//   <li>To continue, load this page again with <code>build=1</code> to the query string.</li>
//   <li>To customize database name (not path), add <code>db=name</code> to the query string.</li>
// </ul>
// TXT;
// }

// try {
//     // check file permissions and check if exists


//     $db = new PDO($dsn);

//     // import and execute script for auth users
//     $auth_script = FILE_ROOT . '/vendor/delight-im/auth/Database/SQLite.sql';
//     $sql = file_get_contents($auth_script);
//     $db->exec($sql);

//     // create exercise tracking table
//     $exercise_records = <<<SQLite3
//         CREATE TABLE exercise_records (
//     id         INTEGER  PRIMARY KEY AUTOINCREMENT
//                         UNIQUE,
//     user_id    INTEGER  NOT NULL
//                         REFERENCES users (id),
//     exercised  INTEGER  DEFAULT (0),
//     date,
//     created_at DATETIME,
//     updated_at DATETIME
// );
// SQLite3;

//     echo "<p>created exercise_records table</p>";

//     $result = $db->exec($exercise_records);

//     // create food consumption table
//     $points_records = <<<SQLite3
// CREATE TABLE points_records (
//     id                INTEGER          PRIMARY KEY AUTOINCREMENT
//                                        UNIQUE,
//     user_id            INTEGER          NOT NULL
//                                        REFERENCES users (id),
//     food_id           INTEGER          REFERENCES food_records (id),
//     quantity          [DATA_TYPE REAL] DEFAULT 0.0,
//     points            [DATA_TYPE REAL] DEFAULT 0.0,
//     table_constraints,
//     date,
//     time,
//     created_at        DATETIME,
//     updated_at        DATETIME
// );
// SQLite3;

//     $db->exec($points_records);

//     echo "<p>created points_records table</p>";

//     // create food data table
//     $food_records = <<<SQLite3
// CREATE TABLE food_records (
//     id         INTEGER  PRIMARY KEY AUTOINCREMENT
//                         UNIQUE ON CONFLICT ABORT,
//     food_name  TEXT,
//     points     REAL,
//     created_at DATETIME,
//     updated_at DATETIME
// );
// SQLite3;

//     $db->exec($food_records);
//     echo "<p>created food_records table</p>";
// } catch (Exception $e) {
//     s($e->getMessage());
// }
