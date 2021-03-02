```
$statement = <<<SQL
CREATE TABLE points_records (
    id                INTEGER          PRIMARY KEY AUTOINCREMENT,
    food              INTEGER          NOT NULL
                                       REFERENCES food_records (id),
    quantity          [DATA_TYPE REAL] DEFAULT 0.0,
    points            [DATA_TYPE REAL] DEFAULT 0.0,
    table_constraints,
    date,
    time
);
SQL;

```
