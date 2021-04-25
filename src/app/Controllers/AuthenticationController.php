<?PHP

namespace App\Controllers;

use flight\net\Request;
use Flight;
use Exception;
use Delight\Auth\InvalidPasswordException;
use App\Exceptions\InvalidUsernameException;
use App\Exceptions\FormException;

class AuthenticationController
{
    public $userID;

    private $data;

    // one year
    private $rememberDuration = 31557600;

    public function __construct(Request $req)
    {
        $this->data = $req->data;
    }

    public function loginUser()
    {
        $email = $this->data['login_email'];
        $password = $this->data['login_password'];

        if (empty($email)) {
            throw new FormException("Email cannot be blank.");
        }

        if (empty($password)) {
            throw new FormException("Password cannot be blank.");
        }

        Flight::auth()->login($email, $password, $this->rememberDuration);
    }

    public function registerUser()
    {
        $email = $this->data['register_email'];
        $username = $this->data['register_username'];
        $password = $this->data['register_password'];

        if (empty($username)) {
            throw new InvalidUsernameException("Username cannot be blank.");
        }

        if (false === Flight::validate()->isUsernameAllowed($username)) {
            throw new InvalidUsernameException("Your username should contain numbers and letters only.");
        }

        if (false === Flight::validate()->isPasswordAllowed($password)) {
            throw new InvalidPasswordException();
        }

        return Flight::auth()->register(
            $email,
            $password,
            $username,
        );
    }

    public function logoutUser()
    {
        Flight::auth()->logOutEverywhere();
        Flight::auth()->destroySession();
    }
}
