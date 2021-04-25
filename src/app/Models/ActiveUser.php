<?PHP

namespace App\Models;

use Flight;

class ActiveUser
{
    public static function init()
    {
        return User::findOrFail(Flight::auth()->getUserId());
    }
}
