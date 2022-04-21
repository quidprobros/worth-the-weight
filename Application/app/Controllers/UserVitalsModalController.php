<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use Exception;
use flight\net\Request;
use Flight;
use App\Models\MeasurementUnits;

class UserVitalsModalController extends BaseController
{
    public $route = "partials/modals/user-vitals";

    public function __construct()
    {
        $this->weight_units_collection = (new MeasurementUnits())->weights();
        $this->settings = Flight::get("ActiveUser")->settings;
    }
}
