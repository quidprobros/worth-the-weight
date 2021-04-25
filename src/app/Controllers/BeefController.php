<?PHP

namespace App\Controllers;

use App\Payload;
use Carbon\Carbon;
use Aura\Payload_Interface\PayloadStatus;
use flight\net\Request;
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

        $payload = new Payload();

        if (false === strtotime($min)  || false == strtotime($max)) {
            $payload->setStatus(PayloadStatus::ERROR);
            $payload->setMessages(["One or both dates are invalid"]);
            throw new \Exception("One or both dates are invalid");
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
        $payload->setStatus(PayloadStatus::SUCCESS);
        $payload->setOutput($return);

        $this->payload = $payload;
    }

    public function getPayload()
    {
        return $this->payload->getAll();
    }
}
