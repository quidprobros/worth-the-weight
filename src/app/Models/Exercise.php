<?PHP

namespace App\Models;

class Exercise extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "exercise_records";
    protected $fillable = ["user_id", "date", "exercised"];
}
