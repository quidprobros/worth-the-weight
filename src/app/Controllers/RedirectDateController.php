<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use Flight;
use Tracy\Debugger;

class RedirectDateController
{
    private $diff;

    public function __construct(string $date)
    {
        if (false === strtotime($date)) {
            Flight::halt(404);
        }

        $date1 = new Carbon($date);
        $now = new Carbon("today");

        $this->diff = $now->diffInDays($date1, false);
    }

    public function __invoke()
    {
        return Flight::redirect("/?omo={$this->diff}&bpo={$this->diff}");
    }
}
