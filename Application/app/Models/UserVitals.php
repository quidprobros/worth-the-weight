<?PHP

namespace App\Models;

class UserVitals extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = "body_vitals_log";

    protected $fillable = ["user_id", "body_weight_id"];
}
