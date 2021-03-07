<?PHP

namespace App\Models;

class JournalItem extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "points_records";
    protected $fillable = ["food", "points", "quantity", "created_at", "date"];

    public function getFood(int $foodID)
    {
        return (new Food())->where("id", $foodID)->first()->food;
    }
}
