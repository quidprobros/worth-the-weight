<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use Exception;
use flight\net\Request;

class UserSettingsModalController extends BaseController
{
    public $route = "partials/modals/user-settings";

    public function __construct(Request $request)
    {
        
    }
}
