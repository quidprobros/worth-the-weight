<?PHP

namespace App\Controllers;

use Flight;
use flight\net\Request;
use Carbon\Carbon;
use App\Models\Exercise;

class HomeController extends BaseController
{
    protected $route = "index";

    public $date;
    public $journal_day_offset;
    public $big_picture_day_offset;
    public $records;
    public $exercised_omo;
    public $exercised_bpo;
    public $foods;
    public $title;
    public $stats;
    public $today_points;
    public $journal_points;

    public function __construct(
        Request $req,
        $journal_day_offset,
        $big_picture_day_offset
    ) {

        $this->journal_day_offset = (int) $journal_day_offset;
        $this->big_picture_day_offset = (int) $big_picture_day_offset;

        $this->query = $req->query;

        $this->date_omo = date_create()
                        ->add(date_interval_create_from_date_string("{$this->journal_day_offset} days"));

        $this->date_bpo = date_create()
                        ->add(date_interval_create_from_date_string("{$this->big_picture_day_offset} days"));

        $this->records = Flight::get("ActiveUser")->onDate($this->date_omo);

        $bpo_view_date = $this->date_bpo->format("D M j, Y");
        $bpo_date = $this->date_bpo->format("Y-m-d 00:00:00");

        switch (strtotime($bpo_date)) {
            case (strtotime("yesterday")):
                $title = "Count for {$bpo_view_date}<br>yesterday";
                break;
            case (date("Y-m-d")):
                $title = "today";
                break;
            default:
                $title = "Count for {$bpo_view_date}";
                break;
        };

        $this->foods = Flight::food()::all();
        $this->title = $title;
        $this->stats = Flight::stats();
        $this->today_points = $this->stats->points($this->big_picture_day_offset);
        $this->journal_points = $this->stats->points($this->journal_day_offset);

        $bpoExercisedModel = Flight::get("ActiveUser")
                           ->exercises()->whereDate("date", "=", $this->date_bpo)->get()->first();
        $this->exercised_bpo = empty($bpoExercisedModel) ? 0 : $bpoExercisedModel->exercised;

        $omoExercisedModel = Flight::get("ActiveUser")
                           ->exercises()->whereDate("date", "=", $this->date_omo)->get()->first();
        $this->exercised_omo = empty($omoExercisedModel) ? 0 : $omoExercisedModel->exercised;

        $this->count = $this->records->count();
    }
}
