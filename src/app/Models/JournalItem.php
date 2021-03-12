<?PHP

namespace App\Models;

class JournalItem extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "points_records";
    protected $fillable = ["food", "points", "quantity", "date"];

    public function getFood(int $foodID)
    {
        return (new Food())->where("id", $foodID)->first()->food;
    }
    public function getSum($date)
    {
        return $this->whereDate("date", "=", $date)->sum("points");
    }
}
