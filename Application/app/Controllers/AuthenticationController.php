<?PHP

namespace App\Controllers;

use flight\Engine;
use Exception;
use Delight\Auth\InvalidPasswordException;
use App\Exceptions\InvalidUsernameException;
use App\Exceptions\FormException;
use Mailgun\Mailgun;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\{SendmailTransport,NullTransport,Transport};
use Symfony\Component\Mime\Email;

class AuthenticationController extends BaseController
{
    private $data;

    private $rememberDuration = null;

    public function __construct(public Engine $app, Mailgun $client = null)
    {
        $this->data = $this->app->request()->data;
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

        $this->app->auth()->login($email, $password, $this->rememberDuration);
    }

    public function resetPassword()
    {
        $email_address = $this->data['reset_email'];

        if (empty($email_address)) {
            throw new FormException("Email cannot be blank.");
        }

        $this->app->auth()->forgotPassword($email_address, function ($selector, $token) use ($email_address) {
            $url = "http://" . $this->app->get('domain') . '/verify_email?selector=' . \urlencode($selector) . '&token=' . \urlencode($token);

            try {
                $html = $this->app->view()
                      ->fetch("emails/password-reset-link", ["url" => $url]);

                $email = (new Email())
                       ->sender(Config::get('app.email.sender'))
                       ->to($email_address)
                       ->subject('Password Reset')
                       ->text("To reset your Worth the Weight password, please click here: {$url}")
                       ->html($html);

                $transport = \Symfony\Component\Mailer\Transport::fromDsn('sendmail://default?command=/usr/sbin/sendmail%20-oi%20-t');

                $mailer = new Mailer($transport);
                $r = $mailer->send($email);
                $this->app->log(['email' => $r]);
            } catch (TransportExceptionInterface $e) {
                $this->app->log($e->getMessage());
                throw new FormException("Unable to send a password-reset email at this time");
            } catch (\Exception $e) {
                $this->app->log($e->getMessage());
                throw new FormException("Something went wrong :(");
            }

            $this->app->log("Sent email to {$email_address} without error");
        });
    }

    public function setNewPassword()
    {
        $selector = $this->data['selector'];
        $token = $this->data['token'];

        if (empty($selector)) {
            throw new FormException("Email cannot be blank.");
        }

        if (empty($token)) {
            throw new FormException("Password cannot be blank.");
        }

        try {
            $this->app->auth()->canResetPasswordOrThrow($selector, $token);
        } catch (\Exception $e) {
            $this->app->log($e->getMessage());
            throw new FormException("Something went wrong!");
        }
    }

    private function handleNewPasswordSubmission()
    {
    }

    public function registerUser($immediateLogin = false)
    {
        $email = $this->data['register_email'];
        $username = $this->data['register_username'];
        $password = $this->data['register_password'];

        if (empty($username)) {
            throw new InvalidUsernameException("Username cannot be blank.");
        }

        if (false === $this->app->validate()->isUsernameAllowed($username)) {
            throw new InvalidUsernameException("Your username should contain numbers and letters only.");
        }

        if (false === $this->app->validate()->isPasswordAllowed($password)) {
            throw new InvalidPasswordException();
        }

        $this->app->auth()->registerWithUniqueUsername(
            $email,
            $password,
            $username,
        );

        if (true == $immediateLogin) {
            $this->app->auth()->login($email, $password, $this->rememberDuration);
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
        $this->app->auth()->logOutEverywhere();
        $this->app->auth()->destroySession();
    }
}
