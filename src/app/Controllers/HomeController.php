<?PHP

namespace App\Controllers;

use Flight;
use flight\net\Request;
use Carbon\Carbon;

class HomeController extends BaseController
{
    protected $route = "index";

    public $date;

    public function __construct(Request $req, $offset)
    {
        $this->query = $req->query;
        $this->offset = (int) $offset;
        $this->journal_day_offset = &$this->offset;
        $this->date = date_create()->add(date_interval_create_from_date_string("{$this->offset} days"));

        $this->records = Flight::get("ActiveUser")->onDate($this->date);
    }

    // main area 
    public function index()
    {

        $journal_view_date = $this->date->format("D M j, Y");
        $journal_date = $this->date->format("Y-m-d 00:00:00");

        switch (strtotime($journal_date)) {
        case (strtotime("yesterday")):
            $title = "yesterday";
            break;
        case (date("Y-m-d")):
            $title = "today";
            break;
        default:
            $title = "Count for {$journal_view_date}";
            break;
        };

        $this->exercised = 0;
        $this->foods = Flight::food()::all();
        $this->title = $title;
        $this->stats = Flight::stats();
        $this->today_points = $this->stats->points($this->offset);
    }

    public function useOtherRoute(string $route)
    {
        $this->route = $route;
    }
}
