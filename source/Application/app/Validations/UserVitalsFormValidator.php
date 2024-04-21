<?PHP

namespace App\Validations;

use Respect\Validation\Validator as V;

class UserVitalsFormValidator
{
    public readonly V $rules;

    public function __construct()
    {
        $this->rules = (new V())
            ->key('weight_log', (new V())->key('date', (new V())->date())
                ->key('amount', (new V())->number())
                ->key('unit_id', (new V())->intVal()));
    }
}
