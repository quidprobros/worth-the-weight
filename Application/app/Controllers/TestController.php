<?PHP

namespace App\Controllers;

use Flight;

class TestController extends BaseController
{
    public $user;
    public $foods;
    public $route = 'test';

    public function ok()
    {
        return 69;
    }
}
