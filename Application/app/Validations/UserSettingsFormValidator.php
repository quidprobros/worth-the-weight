<?PHP

namespace App\Validations;

use Respect\Validation\Validator as V;

class UserSettingsFormValidator
{
    public readonly V $rules;

    public function __construct()
    {
        $this->pointsRule = (new V())->intVal();
        $this->rules = (new V())
            ->key("plan-selection", $this->pointsRule)
            ->key("plan-points-goal", (new V())->number());
    }
}
