<?PHP

namespace App\Models;

class MeasurementUnits extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "measurement_unit_type";

    protected $guarded = [
        "id",
        "measurement_parameter",
        "unit_name",
        "unit_name_singular",
        "unit_name_abbreviation",
    ];

    protected $fillable = [];



    public function heights()
    {
        return $this->where("measurement_parameter", "=", "height")->get();
    }

    public function weights()
    {
        return $this->where("measurement_parameter", "=", "weight")->get();
    }
}
