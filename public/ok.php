<?PHP

const WEB_ROOT = __DIR__;
const FILE_ROOT = __DIR__ . "/..";
const DEBUG = true;

require_once FILE_ROOT . "/vendor/autoload.php";

App\Config::init();

$db = \Delight\Db\PdoDatabase::fromDsn(new \Delight\Db\PdoDsn(\App\DB_DSN));
$auth = new \Delight\Auth\Auth($db);

$email = 'chrisdavidramos@gmail.com';
$pw = 'asdfg';

// login
try {
    $auth->login($email, $pw);

    echo 'User is logged in';
}
catch (\Delight\Auth\InvalidEmailException $e) {
    die('Wrong email address');
}
catch (\Delight\Auth\InvalidPasswordException $e) {
    die('Wrong password');
}
catch (\Delight\Auth\EmailNotVerifiedException $e) {
    die('Email not verified');
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    die('Too many requests');
}

if ($auth->isLoggedIn()) {
    echo 'User is signed in';
}
else {
    echo 'User is not signed in yet';
}


// auth
// try {
//     $userId = $auth->register($email, $pw, null);
//     // function ($selector, $token) {
//     //     echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email)';
//     // });

//     // echo 'We have signed up a new user with the ID ' . $userId;
// }
// catch (\Delight\Auth\InvalidEmailException $e) {
//     die('Invalid email address');
// }
// catch (\Delight\Auth\InvalidPasswordException $e) {
//     die('Invalid password');
// }
// catch (\Delight\Auth\UserAlreadyExistsException $e) {
//     die('User already exists');
// }
// catch (\Delight\Auth\TooManyRequestsException $e) {
//     die('Too many requests');
// }
