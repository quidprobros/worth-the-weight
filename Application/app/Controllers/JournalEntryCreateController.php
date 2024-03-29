<?PHP

namespace App\Controllers;

use App\Exceptions\FormException;
use App\Models\JournalItem;
use Exception;
use Flight;
use flight\Engine;

class JournalEntryCreateController
{
    private $active_user;

    public function __construct(public Engine $app)
    {
        $formData = $this->app->request()->data;
        $this->active_user = $this->app->get('ActiveUser');

        if (false == is_numeric($formData['amount'])) {
            \Delight\Cookie\Session::set("journalcreate-response", "Amount must be numeric");
            throw new FormException("Amount must be numeric");
        }
        if (!isset($formData['amount']) || 0 >= $formData['amount']) {
            throw new FormException("Must enter food amount");
        }
        $this->amount = (float) $formData['amount'];

        if (false == strtotime($formData['date'])) {
            throw new FormException("Must enter valid date");
        }
        $this->date = $formData['date'] . " " . date("H:i:s");

        if (empty($formData['food-selection']) || false == is_numeric($formData['food-selection'])) {
            throw new FormException("Must enter food name");
        }

        try {
            $this->food_model = $this->app->food()::findOrFail($formData['food-selection']);
        } catch (\Exception $e) {
            throw new FormException("Sorry, this food item is not recognized.");
        }

        $this->food_id = $formData['food-selection'];
        $this->points = $this->amount * $this->food_model->points;

        $this->formData = $formData;
    }

    public function getEntriesByOffset($offset)
    {
        return $this->active_user
            ->journal()
            ->whereDate("date", "=", Carbon::now()->addDays($offset))
            ->get();
    }

    public function saveEntry()
    {
        $entry = (new JournalItem())->create([
            "user_id" => $this->active_user->id,
            "date" => $this->date,
            "food_id" => $this->food_id,
            "quantity" => $this->amount,
            "points" => $this->points,
            "plan_id" => $this->active_user->settings->plan->id
        ]);

        return $this->active_user->journal()->save($entry);
    }
}
