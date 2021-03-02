<?
try {
    $pdo = Flight::db();
} catch (\Exception $e) {
    dump($e);
}

$statement = <<<SQL
    CREATE TABLE IF NOT EXISTS points_records (
	id data_type INTEGER PRIMARY KEY,
   	food data_type TEXT NOT NULL,
	quantity data_type REAL DEFAULT 0.0,
    points data_type REAL DEFAULT 0.0,
    date data_type TEXT
	table_constraints
)
SQL;

$sql = $pdo->prepare($statement);
$sql->execute();


