<?PHP

namespace App\Controllers;

use Tracy\Debugger;
use flight\net\Request;
use Flight;
use Exception;
use Delight\Auth\InvalidPasswordException;
use App\Exceptions\InvalidUsernameException;
use App\Exceptions\FormException;
use Mailgun\Mailgun;

class AuthenticationController
{
    private $data;

    // one year
    private $rememberDuration = 31557600;

    public function __construct(Request $req, Mailgun $client = null)
    {
        $this->data = $req->data;
        $this->mailgun = $client;
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

    public function resetPassword()
    {
        $email = $this->data['reset_email'];

        if (empty($email)) {
            throw new FormException("Email cannot be blank.");
        }

        Flight::auth()->forgotPassword($email, function ($selector, $token) {
            $this->sendVerificationEmail($selector, $token, $email, "Worth the Weight: Password reset");
        });
    }

    public function registerUser($immediateLogin = false)
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

        Flight::auth()->registerWithUniqueUsername(
            $email,
            $password,
            $username,
        );

        if (true == $immediateLogin) {
            Flight::auth()->login($email, $password, $this->rememberDuration);
        }
    }

    private function sendVerificationEmail($selector, $token, $email, $subject)
    {
        $url = 'http://wtw.paxperscientiam.com.lan/verify-email?selector='
             . \urlencode($selector)
             . '&token='
             . \urlencode($token);
        $this->mailgun->messages()->send(\App\APP_DOMAIN, [
            'from'    => \App\MAILGUN_SANDBOX_EMAIL,
            'to'      => $email,
            'subject' => $subject,
            'text'    => 'You are truly awesome! Here is your verification link: ' . $url
            ]);
    }

    public function logoutUser()
    {
        Flight::auth()->logOutEverywhere();
        Flight::auth()->destroySession();
    }
}
