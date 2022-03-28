<?PHP

namespace App\Controllers;

use flight\net\Request;
use Flight;
use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

class UserVitalsCreateController
{
    private $active_user;


    public function __construct(Request $request, Validator $validator)
    {
        $formData = $request->data->getData();
        $this->active_user = Flight::get('ActiveUser');
        try {
            bdump($formData);
            $r = $validator->check($formData);
            bdump($r);
        } catch (ValidationException $e) {
            // handle this.
            bdump(['valex' => $e->getMessage()]);
        } catch (\Error $e) {
            bdump($e);
        }
    }
}
