<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use Flight;
use Tracy\Debugger;

class RedirectDateController
{
    private $diff = 0;

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
        Flight::redirect(Flight::url()->sign("/home/{$this->diff}/{$this->diff}"));
    }
}