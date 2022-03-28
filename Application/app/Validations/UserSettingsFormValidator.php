<?PHP

namespace App\Validations;

use Respect\Validation\Validator as V;

class UserSettingsFormValidator
{
    public readonly V $rules;

    public function __construct()
    {
        $this->rules = (new V())
            ->key("plan-selection", (new V())->intVal())
            ->key("plan-points-goal", (new V())->number());
    }
}
