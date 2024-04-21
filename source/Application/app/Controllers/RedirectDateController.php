<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use flight\Engine;

class RedirectDateController
{
    private $diff = 0;

    public function __construct(public Engine $app, string $date)
    {
        if (false === strtotime($date)) {
            $this->app->halt(404);
        }

        $date1 = new Carbon($date);
        $now = new Carbon("today");

        $this->diff = $now->diffInDays($date1, false);
    }

    public function __invoke()
    {
        $this->app->redirect($this->app->url()->sign("/home/{$this->diff}/{$this->diff}"));
    }
}
