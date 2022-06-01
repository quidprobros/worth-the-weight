<?PHP

namespace App\Controllers;

use flight\Engine;

class UserVitalsLogController extends BaseController
{
    public $route = "partials/modals/vitals-log";

    public $vitals_log;
    public $request;

    public function __contruct(Engine $app)
    {
        $this->request = $this->app->request();
        $this->vitals_log = $this->app->get("ActiveUser")->vitals_log;
    }
}
