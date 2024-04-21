<?PHP

namespace App\Controllers;

use Flight;
use flight\Engine;
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
        public Engine $app
    ) {

        $this->journal_day_offset = (int) $this->app->get('omo');
        $this->big_picture_day_offset = (int) $this->app->get('bpo');

        $this->query = $this->app->request()->query;

        $this->date_omo = date_create()
                        ->add(date_interval_create_from_date_string("{$this->journal_day_offset} days"));

        $this->date_bpo = date_create()
                        ->add(date_interval_create_from_date_string("{$this->big_picture_day_offset} days"));

        $this->records = $this->app->get("ActiveUser")->onDate($this->date_omo);

        $bpo_view_date = $this->date_bpo->format("D M j, Y");
        $bpo_date = $this->date_bpo->format("Y-m-d 00:00:00");

        switch (strtotime($bpo_date)) {
            case (strtotime("yesterday")):
                $title = "{$bpo_view_date} (yesterday)";
                break;
            case (strtotime('tomorrow')):
                $title = "{$bpo_view_date} (tomorrow)";
                break;
            case (strtotime('today')):
                $title = "today";
                break;
            default:
                $title = "{$bpo_view_date}";
                break;
        };

        if (null == $this->app->get("ActiveUser")->settings) {
            $this->foods = (new Illuminate\Database\Eloquent\Collection());
        } else {
            $this->foods = $this->app->get("ActiveUser")->settings->plan->foods()->get();
        }

        $this->title = $title;
        $this->stats = $this->app->stats();
        $this->today_points = $this->stats->points($this->big_picture_day_offset);
        $this->journal_points = $this->stats->points($this->journal_day_offset);

        // $bpoExercisedModel = $this->app->get("ActiveUser")
        //                    ->exercises()
        //                    ->where(function ($query) {
        //                        $query->whereDate("date", "=", $this->date_bpo)
        //                              ->orWhere(function($query) {
        //                                  $query->whereDate("date", "=", $this->date_omo);
        //                              });
        //                    });

        $bpoExercisedModel = $this->app->get("ActiveUser")
                           ->exercises()->whereDate("date", "=", $this->date_bpo)->get()->first();
        $this->exercised_bpo = empty($bpoExercisedModel) ? 0 : $bpoExercisedModel->exercised;

        $omoExercisedModel = $this->app->get("ActiveUser")
                           ->exercises()->whereDate("date", "=", $this->date_omo)->get()->first();
        $this->exercised_omo = empty($omoExercisedModel) ? 0 : $omoExercisedModel->exercised;

        $this->count = $this->records->count();
    }
}
