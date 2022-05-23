<?PHP

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goals extends Model
{
    protected $primaryKey = "id";
    protected $table = "goals";

    protected $fillable = [
        "goal_short_name",
        "goal_short_description",
        "goal_description",
    ];
}
