<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use flight\net\Request;
use Flight;
use App\Models\Exercise;

class ExerciseController
{
    public $route;

    public function __construct(Request $request, $offset)
    {
        $this->request = $request;
        $this->offset = $offset;
        $this->exercised = (int) $request->data['exercised'];
    }

    public function saveUpdate()
    {
        return Exercise::updateOrCreate([
            "date" => Carbon::now()->addDays($this->offset)->format("Y-m-d"),
        ], [
            "exercised" => $this->exercised,
            "user_id" => Flight::get("ActiveUser")->id,
        ]);
    }
}
