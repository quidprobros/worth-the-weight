<?PHP

namespace App\Controllers;

use Flight;
use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

use App\Models\MeasurementUnits;
use App\Models\BodyWeight;
use App\Models\UserVitals;

class UserVitalsCreateController extends FormController
{
    protected $filters = [
        'weight_log_date' => 'trim|empty_string_to_null',
        'weight_log_amount' => 'trim|empty_string_to_null',
        'weight_unit_id' => 'trim',
    ];

    public function saveWeight()
    {
        // check if measurement id exists;
        $measurement = (new MeasurementUnits())->findOrFail($this->data['weight_unit_id']);

        $weight_record = (new BodyWeight([
            "weight_value" => $this->data['weight_log_amount'],
            "measurement_unit_id" => $measurement->id,
        ]));
        $weight_record->save();

        (new UserVitals())->create([
            "user_id" => $this->app->get("ActiveUser")->id,
            "body_weight_id" => $weight_record->id,
        ]);
    }
}
