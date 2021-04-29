<?PHP

namespace App\Controllers;

use App\Exceptions\FormException;
use App\Models\JournalItem;
use Exception;
use Flight;
use flight\net\Request;
use Tracy\Debugger;

class JournalEntryCreateController
{
    private $active_user;

    public function __construct(Request $request)
    {
        $formData = $request->data;
        $this->active_user = Flight::get('ActiveUser');

        if (false == is_numeric($formData['amount'])) {
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
            $this->food_model = Flight::food()::findOrFail($formData['food-selection']);
        } catch (\Exception $e) {
            throw new FormException("Sorry, this food item is not recognized.");
        }

        $this->food_id = $formData['food-selection'];

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
            "userID" => $this->active_user->id,
            "date" => $this->date,
            "food_id" => $this->food_id,
            "quantity" => $this->amount,
            "points" => $this->amount * $this->food_model->points,
        ]);

        return $this->active_user->journal()->save($entry);
    }
}
