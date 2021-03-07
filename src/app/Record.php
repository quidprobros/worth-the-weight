<?PHP

namespace App;

use Flight;

class Record
{
    public function setFoodEntry($data)
    {
        $statement = <<<SQL
INSERT INTO points_records
(date, food, quantity, points)
VALUES (:date, :food, :quantity, :points)
SQL;
        Flight::db()->prepare($statement)->execute($data);
    }

    public function getFood(int $foodID)
    {
        $result = Flight::db()
                ->query("SELECT points from food_records WHERE id={$foodID} LIMIT 1");

        return [
            "item_points" => $result->fetch()["points"],
            "item_name" => $result->fetch()["food"],
        ];
    }
}
