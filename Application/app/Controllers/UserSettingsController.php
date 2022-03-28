<?PHP

namespace App\Controllers;

use App\Payload;
use App\Models\UserSettings;
use Carbon\Carbon;
use Aura\Payload_Interface\PayloadStatus;
use flight\net\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\FormException;
use Flight;
use Exception;
use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

class UserSettingsController
{
    public function __construct(Request $req, Validator $validator)
    {
        $formData = $req->data;

        $this->active_user = Flight::get('ActiveUser');

        $this->plan_id = $formData['plan-selection'];

        // Validate form
        try {
            $validator->check($formData);
        } catch (ValidationException $e) {
            bdump(['valex' => $e->getMessage()]);
        } catch (\Error $e) {
            bdump($e);
        }


        // verify data integrity
        try {
            (new \App\Models\Plans())->findOrFail($this->plan_id);
            header("HX-Refresh: true");
        } catch (ModelNotFoundException $e) {
            throw new FormException("Sorry, this meal plan is not recognized");
        }
    }

    public function saveUpdate()
    {
        $settings_modal = $this->active_user->settings;
        $settings_modal->update([
            "plan_id" => $this->plan_id,
        ]);

    }
}
