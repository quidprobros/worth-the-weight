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

    public function journalEntries(int $index)
    {
        return \Flight::db()
                 ->query("SELECT `id`, * FROM `points_records` WHERE DATE(`date`) = DATE('now', 'localtime', '{$index} days') ORDER BY date DESC")
                 ->fetchAll()
                 ;
    }

    public function exercised(int $index)
    {
        return (int) \Flight::db()
            ->query("SELECT `exercised` from `day_records` WHERE DATE(`date`) = DATE('NOW', 'localtime', '{$index} days')")
            ->fetch()
            ;
    }

    public function points(int $index)
    {
        return (int) \Flight::db()
            ->query("SELECT sum(points) as today_points, date(date) as th, date('now', 'localtime', '{$index} days') as tt from points_records where th = tt")
            ->fetch()["today_points"]
            ;
    }
}
