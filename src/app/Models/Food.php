<?PHP

namespace App\Models;

class Food extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "food_records";
    protected $fillable = ["food", "points"];
}
