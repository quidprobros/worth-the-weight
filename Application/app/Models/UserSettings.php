<?PHP

namespace App\Models;

class UserSettings extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "users_settings";

    protected $fillable = [
        "plan_id",
        "user_id",
        "weight_unit_id",
        "height_unit_id"
    ];

    public function plan()
    {
        return $this->belongsTo(Plans::class, "plan_id")->withDefault();
    }

    public function height_unit()
    {
        return $this->belongsTo(MeasurementUnits::class, "height_unit_id");
    }

    public function weight_unit()
    {
        return $this->belongsTo(MeasurementUnits::class, "weight_unit_id");
    }
}
