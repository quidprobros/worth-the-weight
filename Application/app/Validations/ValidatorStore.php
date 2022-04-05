<?PHP

namespace App\Validations;

use Respect\Validation\Validator;

class ValidatorStore
{
    public static function userValidator(): Validator
    {
        return (new Validator())->key('name', Validator::alnum()->noWhitespace()->length(1, 15));
    }

    public static function userSettingsValidator(): Validator
    {
        $pointsRule = (new Validator())->intVal();
        $planPointsRule = (new Validator())->number();
        return (new Validator())
                     ->key("plan-selection", $pointsRule)
                     ->key("plan-points-goal", $planPointsRule, false);
    }
}
