<?PHP

namespace App\Models;

class JournalItem extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "points_records";
    protected $fillable = ["userID", "food_id", "points", "quantity", "date"];

    public function getSum($date)
    {
        return $this->whereDate("date", "=", $date)->sum("points");
    }

    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
