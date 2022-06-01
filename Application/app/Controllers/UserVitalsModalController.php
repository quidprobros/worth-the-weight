<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use Exception;
use flight\Engine;
use Flight;
use App\Models\MeasurementUnits;

class UserVitalsModalController extends BaseController
{
    public $route = "partials/modals/user-vitals";

    public function __construct(public Engine $app)
    {
        $this->weight_units_collection = (new MeasurementUnits())->weights();
        $this->settings = $this->app->get("ActiveUser")->settings;
    }
}
