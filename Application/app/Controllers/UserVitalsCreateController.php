<?PHP

namespace App\Controllers;

use flight\net\Request;
use Flight;
use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

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
        $measurement = (new \App\Models\MeasurementUnits())->findOrFail($this->data['weight_unit_id']);

        $weight_record = (new \App\Models\BodyWeight([
            "weight_value" => $this->data['weight_log_amount'],
            "measurement_unit_id" => $measurement->id,
        ]));
        $weight_record->save();

        (new \App\Models\UserVitals())->create([
            "user_id" => Flight::get("ActiveUser")->id,
            "body_weight_id" => $weight_record->id,
        ]);
    }
}
