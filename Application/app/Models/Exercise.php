<?PHP

namespace App\Models;

use Flight;

class Exercise extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "exercise_records";
    protected $fillable = ["user_id", "date", "exercised"];

    public function entryOnDate($date)
    {
        return $this->belongsTo(User::class)
                    ->where("user_id", $this->app->get('ActiveUser'))
                    ->whereDate("date", "=", $date);
    }
}
