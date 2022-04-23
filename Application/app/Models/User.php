<?PHP

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = "id";
    protected $table = "users";


    protected $guarded = ['email'];

    public function journal()
    {
        return $this->hasMany(JournalItem::class, "user_id");
    }

    public function exercises()
    {
        return $this->hasMany(Exercise::class, "user_id");
    }

    public function today()
    {
        return $this->journal()
                    ->where(function (Builder $query) {
                        return $query->whereDate("date", "=", date("Y-m-d"));
                    })
                   ;
    }

    public function onDate($date)
    {
        return $this->journal()
                    ->where(function (Builder $query) use ($date) {
                        return $query->whereDate("date", "=", $date);
                    })
            ;
    }

    public function settings()
    {
        return $this->hasOne(UserSettings::class, "user_id")->withDefault();
    }

    public function exercisedOnDate($date)
    {
        return $this->hasMany(Exercise::class, "user_id")
                    ->where(function (Builder $query) use ($date) {
                        return $query->whereDate("date", "=", $date);
                    })
            ;
    }


    public function currentDailyPointsGoal()
        // https://laravel.com/docs/9.x/eloquent-relationships#advanced-has-one-of-many-relationships
    {
        return $this->hasOne(Goals::class, 'user_id')
                    ->ofMany([
                        'created_at' => 'max',
                    ], function ($query) {
                        $query
                            // ->where('created_at', '<', Carbon::now())
                            ->whereNotNull('daily_points');
                    });
    }

    public function goals()
    {
        return $this->hasMany(Goals::class, 'user_id');
    }

    public function point_goals()
    {
        return $this->goals()->whereNotNull("point_goals_id");
    }

    public function current_point_goal()
    {
        return $this->point_goals()
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->first();
    }

    public function vitals_log()
    {
        bdump($this->username);
        return $this->hasMany(UserVitals::class, "user_id");
    }
}
