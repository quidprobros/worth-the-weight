<?PHP

namespace App\Validations;

use Respect\Validation\Validator;

class ValidatorStore
{
    public static function userValidator(): Validator
    {
        return (new Validator())->key('name', Validator::alnum()->noWhitespace()->length(1, 15));
    }

    public static function userWeightValidator(): Validator
    {
        return (new Validator())
            ->key("weight_log_amount", (new Validator())->numericVal())
            ->key("weight_log_date", (new Validator())->date())
            ->key("weight_unit_id", (new Validator())->intVal())
            ;
    }

    public static function userSettingsValidator(): Validator
    {
        $pointsIdRule = (new Validator())->intVal();
        $weightUnitIdRule = (new Validator())->intVal();
        $heightUnitIdRule = (new Validator())->intVal();
        return (new Validator())
            ->key("plan-selection", $pointsIdRule)
            ->key("weight-unit-selection", $weightUnitIdRule)
            ->key("height-unit-selection", $heightUnitIdRule)
            ;
    }

    public static function userGoalsValidator(): Validator
    {
        $planPointsRule = (new Validator())
                        ->optional((new Validator())
                                   ->number());
        return (new Validator())
            ->key("plan-points-goal", $planPointsRule);
    }
}
