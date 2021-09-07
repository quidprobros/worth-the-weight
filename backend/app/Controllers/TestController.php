<?PHP

namespace App\Controllers;

use Flight;

class TestController extends BaseController
{
    public $user;
    public $foods;
    public $route;

    public function __construct($route)
    {
        $this->route = $route;
        $this->user = \App\Models\ActiveUser::init();
        $this->foods = \App\Models\Food::all();
    }
    public function ok() {
        return 69;
    }
}
