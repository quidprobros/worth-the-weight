<?PHP

namespace App\Controllers;

use App\Exceptions\FormException;
use Flight;
use Exception;

class UserSettingsController extends FormController
{
    protected $filters = [
        'plan-selection' => 'trim|empty_string_to_null',
        'weight-unit-selection' => 'trim|empty_string_to_null',
        'height-unit-selection' => 'trim|empty_string_to_null',
    ];

    public function saveUpdate()
    {
        Flight::log($this->data);
        $result = Flight::get("ActiveUser")
                ->settings()->upsert([
                    "user_id" => Flight::get("ActiveUser")->id,
                    "plan_id" => $this->data['plan-selection'],
                    "weight_unit_id" => $this->data['weight-unit-selection'],
                    "height_unit_id" => $this->data['height-unit-selection'],
                ], ["user_id"], ["plan_id", "weight_unit_id", "height_unit_id"])
                ;
        if (false == $result) {
            throw new FormException("Something went wrong!");
        }
    }
}
