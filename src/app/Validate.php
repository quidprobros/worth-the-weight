<?PHP

namespace App;

class Validate
{
    public function isPasswordAllowed(string $password)
    {
        if (\strlen($password) < MIN_PASSWORD_LENGTH) {
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
