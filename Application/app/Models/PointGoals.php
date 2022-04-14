<?PHP

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointGoals extends Model
{
    protected $primaryKey = "id";
    protected $table = "point_goals";

    protected $fillable = [
        "plan_id",
        "points_value",
    ];

    public function goal()
    {
        return $this->hasOne(Goals::class, 'point_goals_id');
    }
}

