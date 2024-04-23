<?PHP

namespace App\Controllers;

use flight\Engine;

class UserVitalsLogController extends BaseController
{
    public $route = "partials/modals/vitals-log";

    public $request;

    public $weight_log;
    public $vitals_log;

    public function __construct(public Engine $app)
    {
        $this->request = $this->app->request();
        $this->vitals_log = $this->app
                          ->get("ActiveUser")
                          ->vitals_log;

        $this->weight_log = $this->app
                          ->get("ActiveUser")
                          ->weight_log;
    }
}
