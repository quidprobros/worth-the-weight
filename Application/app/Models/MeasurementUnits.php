<?PHP

namespace App\Models;

class MeasurementUnits extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "measurement_unit_type";

    public function heights()
    {
        return $this->where("measurement_parameter", "=", "height")->get();
    }

    public function weights()
    {
        return $this->where("measurement_parameter", "=", "weight")->get();
    }
}
