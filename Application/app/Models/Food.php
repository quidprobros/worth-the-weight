<?PHP

namespace App\Models;


class Food extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $primaryKey = "id";
    protected $table = "food_records";
    protected $fillable = ["food_name", "green_plan_points", "blue_plan_points", "purple_plan_points"];

    //    const BLUE =

    public function plan()
    {
        return $this->belongsTo(Plans::class, "plan_id");
    }
}
