<?php

namespace App;

class Stats
{
    public function avgDaily()
    {
        return \Flight::db()
                      ->query("SELECT sum(`points`)/count(`date`) from `points_records` where `points` > 0 ")
                      ->fetchColumn();
    }

    public function avgDailyTrailing7()
    {
        return \Flight::db()
            ->query("SELECT sum(`points`)/count(`date`) from `points_records` where `points` > 0 and `date` >= date('now', 'localtime', '-7 days')")
            ->fetchColumn();
    }
}
