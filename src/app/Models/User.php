<?PHP

namespace App\Models;

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

    public function exercisedOnDate($date)
    {
        return $this->hasMany(Exercise::class, "user_id")
                    ->where(function (Builder $query) use ($date) {
                        return $query->whereDate("date", "=", $date);
                    })
            ;
    }
}
