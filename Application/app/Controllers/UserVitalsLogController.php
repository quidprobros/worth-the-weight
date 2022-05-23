<?PHP

namespace App\Controllers;

use flight\net\Request;
use Flight;

class UserVitalsLogController extends BaseController
{
    public $route = "partials/modals/vitals-log";

    public $vitals_log;
    public $request;

    public function __contruct(Request $request)
    {
        $this->request = $request;
        $this->vitals_log = Flight::get("ActiveUser")->vitals_log;
    }
}
