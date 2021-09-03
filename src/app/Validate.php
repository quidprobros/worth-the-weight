<?PHP

namespace App;

use Illuminate\Support\Facades\Config;

class Validate
{
    public function isPasswordAllowed(string $password)
    {
        if (\strlen($password) < Config::get('app.min_password_length')) {
            return false;
        }
        return true;
    }

    public function isUsernameAllowed(string $username)
    {
        if (false == ctype_alnum($username)) {
            return false;
        }
        return true;
    }
}
