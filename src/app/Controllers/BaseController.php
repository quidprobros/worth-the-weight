<?PHP

namespace App\Controllers;

use Flight;
use Exception;

abstract class BaseController
{
    final public function useOtherRoute(string $route)
    {
        $this->setRoute($route);
    }

    final public function setRoute(string $route)
    {
        $this->route = $route;
    }

    final public function render()
    {
        $this->__invoke();
    }

    final public function __invoke()
    {
        if (empty($this->route)) {
            throw new Exception("Route must be defined!");
        }

        if (true != Flight::view()->exists($this->route)) {
            throw new Exception("template not found: {$this->route}");
        }

        return Flight::render(
            $this->route,
            get_object_vars($this),
        );
    }
}
