<?PHP

namespace App\Controllers;

use Carbon\Carbon;
use Exception;
use flight\Engine;

class GotoDateModalController extends BaseController
{
    public $route = "partials/modals/go-to-date-modal";
    public $date;
    public $displayDate;

    public function __construct(public Engine $app, string $date)
    {
        if (false === strtotime($date)) {
            throw new Exception("Bad date value: ${$date}");
        }
        $this->date = $date;
        $this->displayDate = (new Carbon($this->date))->format("D M j, Y");
    }
}
