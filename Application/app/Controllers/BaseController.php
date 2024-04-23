<?PHP

namespace App\Controllers;

use flight\Engine;
use Exception;

abstract class BaseController
{
    public function __construct(public Engine $app)
    {
    }

    final public function useOtherRoute(string $route)
    {
        $this->setRoute($route);
    }

    final public function setRoute(string $route)
    {
        if (true != $this->app->view()->exists($route)) {
            throw new Exception("template not found: {$route}");
        }
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

        if (true != $this->app->view()->exists($this->route)) {
            throw new Exception("template not found: {$this->route}");
        }

        return $this->app->render(
            $this->route,
            get_object_vars($this),
        );
    }
}
