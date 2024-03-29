<?PHP

namespace App\Models;

class JournalItem extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $primaryKey = "id";
    protected $table = "points_records";
    protected $fillable = ["user_id", "food_id", "points", "quantity", "date", "plan_id"];

    public function getSum($date)
    {
        return $this->whereDate("date", "=", $date)->sum("points");
    }

    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
