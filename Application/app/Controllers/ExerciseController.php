<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use flight\Engine;
use App\Models\Exercise;

class ExerciseController extends BaseController
{
    public $route;

    public function __construct(public Engine $app, $offset)
    {
        $this->request = $this->app->request;
        $this->offset = $offset;
        $this->exercised = isset($this->request->data['exercised']) ? 1 : 0; // from checkbox
        $this->exercised_bpo = &$this->exercised;
    }

    public function saveUpdate()
    {
        return Exercise::updateOrCreate([
            "date" => Carbon::now()->addDays($this->offset)->format("Y-m-d"),
        ], [
            "exercised" => $this->exercised,
            "user_id" => $this->app->get("ActiveUser")->id,
        ]);
    }
}
