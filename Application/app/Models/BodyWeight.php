<?PHP

namespace App\Models;

class BodyWeight extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = "body_weight";

    protected $fillable = ["weight_value", "measurement_unit_id"];
}
