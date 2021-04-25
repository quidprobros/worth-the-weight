<?PHP

namespace App\Controllers;

use Flight;

abstract class BaseController
{
    public function __invoke()
    {
        return Flight::render(
            $this->route,
            get_object_vars($this),
        );
    }
}
