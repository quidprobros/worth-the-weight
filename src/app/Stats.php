<?php

namespace App;

use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

class Stats
{
    public function avgDaily()
    {
        $aggregateDays = \Flight::journalItem()
                       ->select("date", DB::raw('sum(points) points'), DB::raw('count(date) quantity'))
                       ->where("points", ">", 0)
                       ->groupBy(DB::raw('date(date)'))
                       ->get()
                       ;

        $sum = 0;
        $days = $aggregateDays->count();

        foreach ($aggregateDays as $model) {
            $sum += $model->points;
        }

        if (0 == $days) {
            return 0;
        }

        return $sum / $days;
    }

    public function avgDailyTrailing7()
    {
        $aggregateDays = \Flight::journalItem()
                       ->select("date", DB::raw('sum(points) points'), DB::raw('count(date) quantity'))
                       ->where("points", ">", 0)
                       ->whereDate("date", ">=", Carbon::now()->subDays(7))
                       ->groupBy(DB::raw('date(date)'))
                       ->get()
                       ;

        $sum = 0;
        $days = $aggregateDays->count();

        foreach ($aggregateDays as $model) {
            $sum += $model->points;
        }

        if (0 == $days) {
            return 0;
        }

        return $sum / $days;
    }

    public function points(int $index): int
    {
        return (int) \Flight::journalItem()->whereDate("date", "=", Carbon::now()->addDays($index))->sum('points');
    }

    public function getPointsByDate(string $journalDate)
    {
        return \Flight::journalItem()->getSum($journalDate);
    }
}
