<?PHP

namespace App\Validations;

use Respect\Validation\Validator as V;
use App\Controllers\FormController;

class UserSettingsFormValidator extends FormController
{
    public readonly V $rules;

    public function __construct()
    {
        $this->pointsRule = (new V())->intVal();
        $this->rules = (new V())
                     ->key("plan-selection", $this->pointsRule);
    }

    public function getInlineError($data)
    {
        try {
            $this->rules->check($data);
        } catch (Respect\Validation\Exceptions\NestedValidationException $e) {
            return $e->getMessage();
        } catch (Respect\Validation\Exceptions\ValidationException $e) {
            return $e->getMessage();
        } catch (Respect\Validation\Exceptions\Exception $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
}
