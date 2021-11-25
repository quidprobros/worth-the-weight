<?PHP

namespace App\Controllers;

use App\Payload;
use Carbon\Carbon;
use Aura\Payload_Interface\PayloadStatus;
use flight\net\Request;
use Illuminate\Support\Facades\Config;
use Flight;
use Exception;

class BeefController
{
    public $payload;

    public function __construct(Request $request, $min, $max)
    {
        $dates = [$min, $max];
        usort($dates, "strcmp");

        list($min, $max) = $dates;

        $this->payload = new Payload();

        if (false === strtotime($min) || false == strtotime($max)) {
            $this->payload->setStatus(PayloadStatus::ERROR);
            $this->payload->setMessages(["One or both dates are invalid"]);
            throw new Exception("One or both dates are invalid");
        }

        $absoluteDiff = abs((new Carbon($min))->diffInDays(new Carbon($max)));

        if (Config::get('app.max_data_request_range') < $absoluteDiff) {
            throw new Exception("Too much data requested");
        }

        $query = Flight::get("ActiveUser")->journal()
                                            ->whereDate("date", ">=", $min)
                                            ->whereDate("date", "<=", $max);

        $records = $query->orderBy('date')
                         ->get()
                         ->groupBy(function ($val) {
                             return Carbon::parse($val->date)->format('Y-m-d');
                         });

        $return = [];
        foreach ($records as $date => $item) {
            $return[] = ["date" => $date, "points" => $item->sum('points')];
        }
        $this->payload->setStatus(PayloadStatus::SUCCESS);
        $this->payload->setOutput($return);
    }

    public function getPayload()
    {
        return $this->payload->getAll();
    }
}
