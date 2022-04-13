<?PHP

namespace App\Controllers;

use App\Exceptions\FormException;
use Flight;
use Exception;

class UserGoalsController extends FormController
{
    protected $filters = [
        'plan-points-goal' => 'trim|empty_string_to_null',
    ];

    public function saveUpdate()
    {
        $result = Flight::get("ActiveUser")
                ->goals()->upsert([
                    "user_id" => Flight::get("ActiveUser")->id,
                    "plan_id" => $this->data['plan-selection'],
                ], ["user_id"], ["plan_id"])
                ;
        if (false == $result) {
            throw new FormException("Something went wrong!");
        }
    }
}
