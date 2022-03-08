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

class UserSettingsController
{
    public function __construct(Request $req)
    {
        $formData = $req->data;
        
        $this->active_user = Flight::get('ActiveUser');

        $this->plan_id = $formData['plan-selection'];

        try {
            (new \App\Models\Plans())->find($this->plan_id);
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
