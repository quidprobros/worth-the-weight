<?PHP

namespace App\Controllers;

use App\Payload;
use App\Models\UserSettings;
use Carbon\Carbon;
use Aura\Payload_Interface\PayloadStatus;
use flight\net\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\{FormException, FormInlineException};
use Flight;
use Exception;
use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

class UserSettingsController extends FormController
{
    protected $filters = [
        'plan-selection' => 'trim|empty_string_to_null',
        'plan-points-goal' => 'trim|empty_string_to_null',
    ];

    public function saveUpdate()
    {
        $result = Flight::get("ActiveUser")
                ->settings()->upsert([
                    "user_id" => Flight::get("ActiveUser")->id,
                    "plan_id" => $this->data['plan-selection'],
                ], ["user_id"], ["plan_id"])
                ;
        if (false == $result) {
            throw new FormException("Something went wrong!");
        }
    }
}
