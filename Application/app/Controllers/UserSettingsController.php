<?PHP

namespace App\Controllers;

use App\Payload;
use Carbon\Carbon;
use Aura\Payload_Interface\PayloadStatus;
use flight\net\Request;
use Illuminate\Support\Facades\Config;
use App\Exceptions\FormException;
use Flight;
use Exception;

class UserSettingsController
{
    public function __construct(Request $req)
    {
        $formData = $req->data;
        bdump($formData);
        $this->active_user = Flight::get('ActiveUser');
    }
}
